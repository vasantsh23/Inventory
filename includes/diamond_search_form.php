<?php
/**
 * diamond_search_form.php
 * Builds the Diamond Search filter screen's sections (main + Advanced
 * Filter), including the Fancy Filter special case. Extracted from
 * modules/user/diamond_search.php so the web page and the mobile JSON
 * API (api/search_form.php) render exactly the same set of filters,
 * submitting exactly the same values, from one implementation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

/**
 * The actual value submitted for a pill/shape checkbox. Weight/Carat
 * buckets encode their numeric range directly; an option labelled
 * "All" always becomes a reserved marker so the query builder can
 * detect "skip this criteria" regardless of whatever real value that
 * lookup row happens to store; an option labelled "Others" on the
 * Shape/Color sections specifically becomes another reserved marker
 * for the "not present in the lookup table" exclusion.
 */
function ds_option_submit_value(array $section, array $opt): string
{
    if ($section['fldname'] === 'Weight') {
        return ($opt['from'] ?? '') . '|' . ($opt['to'] ?? '');
    }
    if (strcasecmp($opt['label'], 'all') === 0) {
        return '__ALL__';
    }
    if (in_array($section['fldname'], ['Shape', 'Color'], true) && strcasecmp($opt['label'], 'others') === 0) {
        return '__OTHERS__';
    }
    return $opt['value'] ?? $opt['label'];
}

/**
 * [mainSections, advancedSections] exactly as the Diamond Search page
 * shows them.
 */
function ds_build_form_sections(): array
{
    $sections = get_diamond_search_sections();
    $advancedSections = get_diamond_search_sections('adv_filter');
    // NatFancyColor / NatFancyColorIntensity are never shown via the
    // generic active/orderid mechanism — they're only ever rendered by
    // the bespoke block below (positioned right after Clarity, gated on
    // Site Setup's Fancy Filter), so exclude them here regardless of
    // whatever "active" status they happen to have in the
    // diamond_search / adv_filter tables. Otherwise they could render
    // twice — once here, once below — with two inputs sharing the same
    // name attribute, which breaks form submission.
    $fancyDerivedFldnames = ['NatFancyColor', 'NatFancyColorIntensity'];
    $sections = array_values(array_filter($sections, fn($s) => !in_array($s['fldname'], $fancyDerivedFldnames, true)));
    $advancedSections = array_values(array_filter($advancedSections, fn($s) => !in_array($s['fldname'], $fancyDerivedFldnames, true)));
    // A field already shown in the main search must not also render in
    // the Advanced panel — duplicate `name` attributes on two different
    // inputs breaks form submission, and it's confusing to show the same
    // filter twice anyway.
    $mainFldnames = array_column($sections, 'fldname');
    $advancedSections = array_values(array_filter(
        $advancedSections,
        fn($s) => !in_array($s['fldname'], $mainFldnames, true)
    ));

    // Fancy Filter (Site Setup): when on, Color loses its "Fancy" pill
    // and two dedicated sections — Nat Fancy Color, then Nat Fancy Color
    // Intensity — are inserted right after Clarity, sourced from the
    // fancycolor / fancyint lookup tables (fancy colours in table order,
    // intensities alphabetically).
    $fancyFilterOn = strcasecmp((string)((get_setup() ?? [])['Fancyfilter'] ?? 'no'), 'yes') === 0;
    if ($fancyFilterOn) {
        $colorIndex = null;
        foreach ($sections as $i => $s) {
            if ($s['fldname'] === 'Color') {
                $colorIndex = $i;
                // Drop the "Fancy" pill from Color — its role is now
                // played by the two dedicated sections below instead.
                $sections[$i]['options'] = array_values(array_filter(
                    $s['options'],
                    fn($opt) => strcasecmp($opt['label'], 'fancy') !== 0
                ));
                break;
            }
        }

        $natColorRows = [];
        $natIntRows = [];
        // Prefer filtering out explicitly-inactive rows when the `active`
        // column exists (it's optional), falling back to every row when
        // it doesn't, rather than a fatal error either way.
        // Fancy colours are shown in the order of the fancycolor table:
        // its `order` column when it has one, otherwise the row order (id).
        $natColorOrder = '`id` ASC';
        try {
            if (get_db()->query("SHOW COLUMNS FROM `fancycolor` LIKE 'order'")->fetch()) {
                $natColorOrder = '`order` ASC, `id` ASC';
            }
        } catch (Throwable $e) {
            // table missing — handled below
        }
        try {
            $natColorRows = get_db()->query("SELECT id, fncycolor FROM `fancycolor` WHERE active IS NULL OR active = '' OR active = 'yes' ORDER BY $natColorOrder")->fetchAll();
        } catch (Throwable $e) {
            try {
                $natColorRows = get_db()->query("SELECT id, fncycolor FROM `fancycolor` ORDER BY $natColorOrder")->fetchAll();
            } catch (Throwable $e2) {
                // fancycolor table doesn't exist at all yet.
            }
        }
        try {
            $natIntRows = get_db()->query("SELECT id, fncyint FROM `fancyint` WHERE active IS NULL OR active = '' OR active = 'yes' ORDER BY `fncyint` ASC")->fetchAll();
        } catch (Throwable $e) {
            try {
                $natIntRows = get_db()->query("SELECT id, fncyint FROM `fancyint` ORDER BY `fncyint` ASC")->fetchAll();
            } catch (Throwable $e2) {
                // fancyint table doesn't exist at all yet.
            }
        }

        // Labels: the "colname" of these two rows in Admin > Manage Tables >
        // Diamond Search Fields, like every other section's label (their
        // Active setting is ignored — Site Setup's Fancy Filter decides
        // whether they show). Falls back to the original wording.
        $fancyLabels = ['NatFancyColor' => 'Nat Fancy Color', 'NatFancyColorIntensity' => 'Nat Fancy Color Intensity'];
        try {
            $labelRows = get_db()->query(
                "SELECT fldname, colname FROM `diamond_search` WHERE fldname IN ('NatFancyColor', 'NatFancyColorIntensity')"
            )->fetchAll();
            foreach ($labelRows as $r) {
                $colname = trim((string)$r['colname']);
                if ($colname !== '') {
                    $fancyLabels[$r['fldname']] = $colname;
                }
            }
        } catch (Throwable $e) {
            // diamond_search not reachable — keep the original wording.
        }

        $fancyExtraSections = [
            [
                'kind'    => 'pill',
                'fldname' => 'NatFancyColor',
                'label'   => $fancyLabels['NatFancyColor'],
                'options' => array_map(fn($r) => ['id' => $r['id'], 'label' => (string)$r['fncycolor'], 'value' => (string)$r['fncycolor']], $natColorRows),
            ],
            [
                'kind'    => 'pill',
                'fldname' => 'NatFancyColorIntensity',
                'label'   => $fancyLabels['NatFancyColorIntensity'],
                'options' => array_map(fn($r) => ['id' => $r['id'], 'label' => (string)$r['fncyint'], 'value' => (string)$r['fncyint']], $natIntRows),
            ],
        ];

        // Position: always right after Clarity (Nat Fancy Color, then
        // Nat Fancy Color Intensity). If Clarity isn't active in the main
        // search, right after Color; if neither is, at the end.
        $anchorIndex = null;
        foreach ($sections as $i => $sec) {
            if ($sec['fldname'] === 'Clarity') {
                $anchorIndex = $i;
                break;
            }
        }
        $anchorIndex ??= $colorIndex;
        if ($anchorIndex !== null) {
            array_splice($sections, $anchorIndex + 1, 0, $fancyExtraSections);
        } else {
            array_push($sections, ...$fancyExtraSections);
        }
    }

    return [$sections, $advancedSections];
}

/**
 * Extra controls printed under a section's pills by every search
 * template (<?= ds_section_extras($section, $priorFilters) ?>).
 *
 * Nat Fancy Color: an "Exact" tick box, on by default.
 *   on  -> only stones whose fancy colour IS the chosen colour
 *          (NatFancyColor = Pink)
 *   off -> every fancy stone whose colour description CONTAINS the
 *          chosen colour (Color LIKE '%Pink%': also "Pinkish Brown",
 *          "Orangy Pink" …)
 * Submitted as f[NatFancyColor_exact] = 1 / 0 (a hidden 0 comes first,
 * so an unticked box still sends 0); applied in
 * build_maindata_search_where().
 */
function ds_section_extras(array $section, array $priorFilters): string
{
    if (($section['fldname'] ?? '') !== 'NatFancyColor') {
        return '';
    }
    $exact = (string)($priorFilters['NatFancyColor_exact'] ?? '1') !== '0';
    return '<div class="ds-exact-row">'
        . '<input type="hidden" name="f[NatFancyColor_exact]" value="0">'
        . '<label class="ds-exact-toggle" title="Ticked: only the exact colour. Unticked: every fancy colour that contains it (e.g. Green also finds Greenish Blue).">'
        . '<input type="checkbox" name="f[NatFancyColor_exact]" value="1" data-flag="1" data-default-checked="1"' . ($exact ? ' checked' : '') . '> Exact'
        . '</label>'
        . '<span class="ds-exact-hint">Untick to also find colours that contain it — e.g. Green → Greenish Blue</span>'
        . '</div>';
}
