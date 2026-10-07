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
    // the bespoke block below (positioned right after Color, gated on
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
    // Intensity — are inserted right after Color, sourced from the
    // fancycolor / fancyint lookup tables (each ordered by its own value
    // column, per spec, not a separate sort column).
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
        try {
            $natColorRows = get_db()->query("SELECT id, fncycolor FROM `fancycolor` WHERE active IS NULL OR active = '' OR active = 'yes' ORDER BY `fncycolor` ASC")->fetchAll();
        } catch (Throwable $e) {
            try {
                $natColorRows = get_db()->query("SELECT id, fncycolor FROM `fancycolor` ORDER BY `fncycolor` ASC")->fetchAll();
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

        $fancyExtraSections = [
            [
                'kind'    => 'pill',
                'fldname' => 'NatFancyColor',
                'label'   => 'Nat Fancy Color',
                'options' => array_map(fn($r) => ['id' => $r['id'], 'label' => (string)$r['fncycolor'], 'value' => (string)$r['fncycolor']], $natColorRows),
            ],
            [
                'kind'    => 'pill',
                'fldname' => 'NatFancyColorIntensity',
                'label'   => 'Nat Fancy Color Intensity',
                'options' => array_map(fn($r) => ['id' => $r['id'], 'label' => (string)$r['fncyint'], 'value' => (string)$r['fncyint']], $natIntRows),
            ],
        ];

        if ($colorIndex !== null) {
            array_splice($sections, $colorIndex + 1, 0, [$fancyExtraSections[0]]);
            array_splice($sections, $colorIndex + 2, 0, [$fancyExtraSections[1]]);
        } else {
            // Color isn't active in the main search at all — still show
            // both sections rather than silently dropping the feature.
            array_push($sections, ...$fancyExtraSections);
        }
    }

    return [$sections, $advancedSections];
}
