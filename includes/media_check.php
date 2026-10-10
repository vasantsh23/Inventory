<?php
declare(strict_types=1);

/**
 * Checks that media / certificate URLs can really be opened, so
 * Results and View Cart only draw an icon for a link that works.
 *
 * - All URLs for a page are checked in parallel (curl_multi), HEAD first,
 *   then a 1-byte GET for servers that refuse HEAD.
 * - OK = HTTP 2xx after redirects, and (for .mp4 / .pdf / images) a
 *   matching content type, so an HTML "not found" page served with
 *   status 200 doesn't count as a video or PDF.
 * - Results are cached on disk (storage/cache/media/): good links for
 *   12 hours, failed links for 30 minutes, so repeat page views are
 *   instant and a newly uploaded file shows up soon after.
 * - Set MEDIA_VALIDATE to false (config.php / env) to switch checking
 *   off; the icons then only need a syntactically valid URL.
 */

const MEDIA_CHECK_OK_TTL   = 43200; // 12 h
const MEDIA_CHECK_FAIL_TTL = 1800;  // 30 min
const MEDIA_CHECK_PARALLEL = 24;

function media_check_cache_dir(): ?string
{
    static $dir = false;
    if ($dir === false) {
        $dir = dirname(__DIR__) . '/storage/cache/media';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $dir = null;
        } elseif (!is_writable($dir)) {
            $dir = null;
        }
    }
    return $dir;
}

/** @return ?bool cached verdict, or null when unknown / expired */
function media_check_cache_get(string $url): ?bool
{
    $dir = media_check_cache_dir();
    if ($dir === null) {
        return null;
    }
    $file = $dir . '/' . sha1($url) . '.txt';
    $mtime = @filemtime($file);
    if ($mtime === false) {
        return null;
    }
    $ok = @file_get_contents($file) === '1';
    $ttl = $ok ? MEDIA_CHECK_OK_TTL : MEDIA_CHECK_FAIL_TTL;
    return (time() - $mtime) <= $ttl ? $ok : null;
}

function media_check_cache_put(string $url, bool $ok): void
{
    $dir = media_check_cache_dir();
    if ($dir !== null) {
        @file_put_contents($dir . '/' . sha1($url) . '.txt', $ok ? '1' : '0', LOCK_EX);
    }
}

/** Does the response content type fit what the URL claims to be? */
function media_check_type_ok(string $url, ?string $contentType): bool
{
    $ct = strtolower(trim((string)$contentType));
    if ($ct === '') {
        return true;
    }
    $path = strtolower((string)parse_url($url, PHP_URL_PATH));
    $generic = str_starts_with($ct, 'application/octet-stream') || str_starts_with($ct, 'binary/octet-stream');
    if (preg_match('/\.(mp4|webm|mov|m4v)$/', $path)) {
        return $generic || str_starts_with($ct, 'video/') || str_starts_with($ct, 'application/mp4');
    }
    if (str_ends_with($path, '.pdf')) {
        return $generic || str_starts_with($ct, 'application/pdf') || str_starts_with($ct, 'application/x-pdf');
    }
    if (preg_match('/\.(jpe?g|png|webp|gif)$/', $path)) {
        return $generic || str_starts_with($ct, 'image/');
    }
    return true;
}

/**
 * One parallel round of requests.
 * @param array<string,bool> $urls  url => true (use GET instead of HEAD)
 * @return array<string,array{code:int,type:?string}>
 */
function media_check_round(array $urls, bool $useGet): array
{
    $results = [];
    $mh = curl_multi_init();
    $queue = array_keys($urls);
    $active = [];
    $start = static function (string $url) use ($mh, $useGet, &$active): void {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; StockMediaCheck/1.0)',
        ];
        if ($useGet) {
            $opts[CURLOPT_HTTPHEADER] = ['Range: bytes=0-0'];
            // Stop after the first chunk — we only need the status line + headers.
            $opts[CURLOPT_WRITEFUNCTION] = static fn($c, $d) => 0;
        } else {
            $opts[CURLOPT_NOBODY] = true;
        }
        curl_setopt_array($ch, $opts);
        curl_multi_add_handle($mh, $ch);
        $active[(int)$ch] = [$ch, $url];
    };
    while ($queue !== [] && count($active) < MEDIA_CHECK_PARALLEL) {
        $start((string)array_shift($queue));
    }
    do {
        curl_multi_exec($mh, $running);
        while (($info = curl_multi_info_read($mh)) !== false) {
            $ch = $info['handle'];
            [, $url] = $active[(int)$ch];
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $results[$url] = [
                'code' => (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                'type' => is_string($type) ? $type : null,
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[(int)$ch]);
            if ($queue !== []) {
                $start((string)array_shift($queue));
            }
        }
        if ($active !== []) {
            curl_multi_select($mh, 0.5);
        }
    } while ($active !== [] || $queue !== []);
    curl_multi_close($mh);
    return $results;
}

/**
 * @param string[] $urls
 * @return array<string,bool> url => can be opened
 */
function media_urls_reachable(array $urls): array
{
    $urls = array_values(array_unique($urls));
    $verdict = [];
    $todo = [];
    foreach ($urls as $u) {
        $cached = media_check_cache_get($u);
        if ($cached !== null) {
            $verdict[$u] = $cached;
        } else {
            $todo[$u] = true;
        }
    }
    if ($todo === []) {
        return $verdict;
    }
    if (!function_exists('curl_multi_init')) {
        // No curl on this server: can't check, so fall back to "valid URL is enough".
        foreach ($todo as $u => $_) {
            $verdict[$u] = true;
        }
        return $verdict;
    }

    $isGood = static fn(array $r, string $u): bool =>
        $r['code'] >= 200 && $r['code'] < 300 && media_check_type_ok($u, $r['type']);

    $first = media_check_round($todo, false);
    $retry = [];
    foreach ($todo as $u => $_) {
        $r = $first[$u] ?? ['code' => 0, 'type' => null];
        if ($isGood($r, $u)) {
            $verdict[$u] = true;
        } elseif (in_array($r['code'], [400, 401, 403, 405, 406, 416, 501], true)) {
            $retry[$u] = true; // HEAD refused — try a ranged GET
        } else {
            $verdict[$u] = false;
        }
    }
    if ($retry !== []) {
        $second = media_check_round($retry, true);
        foreach ($retry as $u => $_) {
            $verdict[$u] = $isGood($second[$u] ?? ['code' => 0, 'type' => null], $u);
        }
    }
    foreach ($todo as $u => $_) {
        media_check_cache_put($u, (bool)($verdict[$u] ?? false));
    }
    return $verdict;
}

/**
 * Media links for every row of a Results / View Cart page, with each
 * link that can't be opened set to null.
 *
 * @param array<int,array<string,mixed>> $rows rows with id, StockNo, Lab, CertificateNo
 * @return array<string,array{video:?string,handvideo:?string,infovideo:?string,cert:?string}> keyed by row id
 */
function build_result_media_links_for_rows(array $rows): array
{
    $links = [];
    $probe = []; // [rowId][key] => url actually requested for the check
    foreach ($rows as $row) {
        $id = (string)($row['id'] ?? '');
        $l = build_result_media_links($row['StockNo'] ?? null, $row['Lab'] ?? null, $row['CertificateNo'] ?? null);
        $links[$id] = $l;
        foreach ($l as $key => $url) {
            if ($url === null) {
                continue;
            }
            // The built-in 360 viewer page answers 200 for any stock number,
            // so check that stock's still image instead.
            $p = parse_url($url);
            if ($key === 'video' && ($p['host'] ?? '') === 'v3601425.v360.in'
                && str_ends_with((string)($p['path'] ?? ''), 'vision360.html')) {
                parse_str((string)($p['query'] ?? ''), $q);
                $probe[$id][$key] = 'https://v3601425.v360.in/imaged/' . rawurlencode((string)($q['d'] ?? '')) . '/still.jpg';
            } else {
                $probe[$id][$key] = $url;
            }
        }
    }

    $validate = !defined('MEDIA_VALIDATE') || MEDIA_VALIDATE;
    if (!$validate) {
        return $links;
    }
    $all = [];
    foreach ($probe as $byKey) {
        foreach ($byKey as $u) {
            $all[] = $u;
        }
    }
    $ok = media_urls_reachable($all);
    foreach ($probe as $id => $byKey) {
        foreach ($byKey as $key => $u) {
            if (empty($ok[$u])) {
                $links[$id][$key] = null;
            }
        }
    }
    return $links;
}
