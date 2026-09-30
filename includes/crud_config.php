<?php
/**
 * crud_config.php
 * Declares which database tables are exposed through the generic
 * admin CRUD screens, and any special handling per column:
 *   - encrypted: value is stored via encrypt_value()/decrypt_value()
 *                (auto-detected for VARBINARY columns, can be overridden)
 *   - type: text | textarea | email | select | color | date | datetime
 *           | readonly | password | secret | hidden
 *   - options: choices for a 'select' column
 *
 * Column metadata not explicitly listed here is inferred automatically
 * from information_schema (see crud_engine.php: get_table_meta()).
 */

declare(strict_types=1);

/** Tables editable from the admin dashboard. Acts as a hard allowlist. */
const CRUD_TABLES = [
    'user'           => 'Users',
    'user_types'     => 'User Types',
    'setup'          => 'Site Setup',
    'rsetup'         => 'Results/View Cart Sort Settings',
    'uploadref'      => 'Upload Field Mapping',
    'path'           => 'File Paths',
    'timings'        => 'Business Hours',
    'upload'         => 'API / Upload Config',
    'rounding_rules' => 'Amount Rounding Rules',
    'fancycolor'     => 'Fancy Colors',
    'fancyint'       => 'Fancy Color Intensities',
    'upload_date'    => 'Upload History',
    'maindata'       => 'Main Data',
    'diamond_search' => 'Diamond Search Fields',
    'results'        => 'Results',
    'diamond_details' => 'Diamond Details',
    'adv_filter'      => 'Advanced Filter',
    'customer'        => 'Customers',
    'memo'            => 'Memo Details(Antwerp)',
    'dmemo'           => 'Memo Details(Dubai)',
    'cut'            => 'Cut',
    'fluorescence'   => 'Fluorescence',
    'polish'         => 'Polish',
    'symmetry'       => 'Symmetry',
    'shape'          => 'Shape',
    'location'       => 'Location',
    'size'           => 'Size',
    'lab'            => 'Lab',
    'clarity'        => 'Clarity',
    'color'          => 'Color',
    'availability'   => 'Availability',
];

/**
 * For these tables, importing from Excel first blanks out the listed
 * columns across EVERY existing row, before applying the uploaded
 * values. This means a row the uploaded file doesn't mention ends up
 * with these columns genuinely empty — a full replace rather than a
 * partial merge. Only applies to the columns listed, and only when
 * importing into that specific table.
 */
const CRUD_IMPORT_CLEAR_COLUMNS = [
    'diamond_search' => ['active', 'orderid'],
];

/** Per-column overrides, keyed by table then column name. */
const CRUD_COLUMN_OVERRIDES = [
    'user' => [
        'id'              => ['type' => 'readonly'],
        'emailid'         => ['type' => 'email', 'label' => 'Email'],
        'emailid_hash'    => ['type' => 'hidden'],
        'password'        => ['type' => 'password', 'label' => 'Password'],
        'usertype'        => ['type' => 'lookup', 'label' => 'User Type',
                               'lookup_table' => 'user_types', 'lookup_value' => 'id', 'lookup_display' => 'usertype'],
        'approval'        => ['type' => 'select', 'label' => 'Approval',
                               'options' => ['pending', 'approved', 'rejected', 'disabled']],
        'creation_date'   => ['type' => 'readonly', 'label' => 'Created'],
        'last_login'      => ['type' => 'readonly', 'label' => 'Last Login'],
        'locked_until'    => ['type' => 'readonly', 'label' => 'Locked Until'],
        'failed_attempts' => ['type' => 'readonly', 'label' => 'Failed Attempts'],
        'ipadd'           => ['type' => 'readonly', 'label' => 'Last IP'],
    ],
    'user_types' => [
        'id'       => ['type' => 'readonly'],
        'usertype' => ['label' => 'Type Name'],
        'level'    => ['type' => 'select', 'label' => 'Level',
                       'options' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                       'hint' => 'Levels 0-7 are custom user roles (user module only). 8 = Admin (user + admin modules). 9 = Super Admin (all modules).'],
    ],
    'setup' => [
        'id'         => ['type' => 'readonly'],
        'Page Desc'  => ['type' => 'textarea'],
        'Meta Desc'  => ['type' => 'textarea'],
        'OG Desc'    => ['type' => 'textarea'],
        'Reservation'    => ['type' => 'select', 'options' => ['Yes', 'No']],
        'Take away'      => ['type' => 'select', 'options' => ['Yes', 'No']],
        'Marquee active' => ['type' => 'select', 'options' => ['Yes', 'No']],
        'loginscrn' => [
            'type' => 'select',
            'label' => 'Show Login Screen for Inventory',
            'options' => ['yes' => 'Yes — require login', 'no' => 'No — go straight to Diamond Search'],
            'hint' => 'If set to No, the "Inventory" link on the home page skips login entirely and goes straight into the user module (public browsing). Admin/Super Admin logins are unaffected — once this is No, the admin login page is no longer linked anywhere on the public site, so log in directly at yoursite.com/login.php.',
        ],
        'Memo' => [
            'type' => 'select',
            'label' => 'Memo Feature',
            'options' => ['yes' => 'Yes — show on Results', 'no' => 'No — hide everywhere'],
            'hint' => 'When Yes, the customer picker plus Memo-1 and Memo-3 buttons appear on the Results page, but only for logged-in users whose role level is 4 or 5. When No, those are hidden for everyone regardless of level.',
        ],
        'Fancyfilter' => [
            'type' => 'select',
            'label' => 'Fancy Filter',
            'options' => ['no' => 'No — "Fancy" pill in Color', 'yes' => 'Yes — dedicated Nat Fancy Color sections'],
            'hint' => 'When Yes, Diamond Search removes the "Fancy" option from Color and instead shows two dedicated sections right after it — Nat Fancy Color and Nat Fancy Color Intensity — sourced from the Fancy Colors / Fancy Color Intensities tables. When No (default), Color keeps its single "Fancy" pill as before.',
        ],
    ],
    'path' => [
        'id' => ['type' => 'readonly'],
    ],
    'timings' => [
        'id'      => ['type' => 'readonly'],
        'Day'     => ['type' => 'select', 'options' => [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
        ]],
        'holiday' => ['type' => 'select', 'options' => ['Yes', 'No']],
    ],
    'upload' => [
        'id'              => ['type' => 'readonly'],
        'API'             => ['type' => 'select', 'options' => ['Yes', 'No']],
        'Excel'           => ['type' => 'select', 'options' => ['Yes', 'No']],
        'CSV'             => ['type' => 'select', 'options' => ['Yes', 'No']],
        'API key'         => ['type' => 'secret', 'label' => 'API Key'],
        'API secret key'  => ['type' => 'secret', 'label' => 'API Secret Key'],
    ],
    'maindata' => [
        // `id` is intentionally left without an override: it is NOT
        // auto-increment (ids come from your data source), so it must
        // stay an editable required field on both add and edit.
    ],
    'diamond_search' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'label' => 'Active', 'options' => ['yes', 'no']],
        'orderid' => ['label' => 'Order Id', 'hint' => 'Controls display order — lower numbers appear first.'],
    ],
    'results' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'label' => 'Active', 'options' => ['yes', 'no']],
        'orderid' => ['label' => 'Order Id', 'hint' => 'Controls display order — lower numbers appear first.'],
    ],
    'diamond_details' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'label' => 'Active', 'options' => ['yes', 'no']],
        'orderid' => ['label' => 'Order Id', 'hint' => 'Controls display order — lower numbers appear first.'],
        'data_type' => ['label' => 'Data Type', 'hint' => 'e.g. text, integer, numeric — matches the source field\'s data type.'],
    ],
    'adv_filter' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'label' => 'Active', 'options' => ['yes', 'no']],
        'orderid' => ['label' => 'Order Id', 'hint' => 'Controls display order — lower numbers appear first.'],
    ],
    'customer' => [
        'custid' => ['type' => 'readonly'],
    ],
    'rsetup' => [
        'id'         => ['type' => 'readonly'],
        'sortfld1'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 1'],
        'sortorder1' => ['type' => 'select', 'label' => 'Sort Order 1', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
        'sortfld2'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 2'],
        'sortorder2' => ['type' => 'select', 'label' => 'Sort Order 2', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
        'sortfld3'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 3'],
        'sortorder3' => ['type' => 'select', 'label' => 'Sort Order 3', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
        'sortfld4'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 4'],
        'sortorder4' => ['type' => 'select', 'label' => 'Sort Order 4', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
        'sortfld5'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 5'],
        'sortorder5' => ['type' => 'select', 'label' => 'Sort Order 5', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
        'sortfld6'   => ['type' => 'maindata_column_select', 'label' => 'Sort Field 6'],
        'sortorder6' => ['type' => 'select', 'label' => 'Sort Order 6', 'options' => ['A' => 'Ascending', 'D' => 'Descending']],
    ],
    'uploadref' => [
        'id'        => ['type' => 'readonly'],
        'colname'   => ['type' => 'maindata_column_select', 'label' => 'Maindata Column'],
        'excolname' => ['label' => 'CSV Header Name', 'hint' => 'The exact column header your supplier\'s CSV file uses for this field.'],
        'active'    => ['type' => 'select', 'label' => 'Active', 'options' => ['yes' => 'Yes', 'no' => 'No']],
    ],
    'rounding_rules' => [
        'id'         => ['type' => 'readonly'],
        'rule_name'  => ['label' => 'Rule Name'],
        'method'     => ['type' => 'select', 'label' => 'Method', 'options' => [
                            'ceil_whole', 'round_whole', 'floor_whole',
                            'nearest_increment', 'ceil_increment', 'floor_increment', 'none',
                         ],
                          'hint' => 'The actual math for each of these lives in includes/functions.php\'s round_amount_apply() — adding a genuinely new method (not just a new increment) needs a small code change there, then a new row here referencing it.'],
        'increment'  => ['label' => 'Increment', 'hint' => 'Only used by the "...increment" methods, e.g. 0.05 for nearest/round-up/round-down to 5 cents, 0.10 for 10 cents, 1 for whole dollars. Ignored by every other method.'],
        'active'     => ['type' => 'select', 'label' => 'Active', 'options' => ['no', 'yes'],
                          'hint' => 'Exactly one row should be "yes" — that\'s the rule actually used to round Amount (totamt) on Results and View Cart. If more than one is "yes", the lowest id wins; if none are, it safely falls back to "Round up to next whole dollar".'],
        'description' => ['type' => 'textarea', 'label' => 'Description'],
        'sort_order'  => ['label' => 'Sort Order'],
    ],
    'fancycolor' => [
        'id'        => ['type' => 'readonly'],
        'fncycolor' => ['label' => 'Fancy Color', 'hint' => 'e.g. Yellow, Orange, Pink — matched against maindata.NatFancyColor, which Diamond Data Upload derives automatically from the Color field.'],
        'active'    => ['type' => 'select', 'label' => 'Active', 'options' => ['yes' => 'Yes', 'no' => 'No']],
    ],
    'fancyint' => [
        'id'       => ['type' => 'readonly'],
        'fncyint'  => ['label' => 'Fancy Color Intensity', 'hint' => 'e.g. Light, Intense, Vivid, Dark, Deep — matched against maindata.NatFancyColorIntensity, which Diamond Data Upload derives automatically from the Color field.'],
        'active'   => ['type' => 'select', 'label' => 'Active', 'options' => ['yes' => 'Yes', 'no' => 'No']],
    ],
    'upload_date' => [
        'id'             => ['type' => 'readonly'],
        'upldfile_date'  => ['label' => 'File Date', 'hint' => 'The uploaded CSV file\'s own last-modified date, as reported by the browser at the time it was submitted.'],
        'upldfile_time'  => ['label' => 'File Time'],
        'data_uplddate'  => ['label' => 'Upload Run Date', 'hint' => 'The date the upload actually ran on this server (may differ from the file\'s own date/time above).'],
        'data_upldtime'  => ['label' => 'Upload Run Time'],
        'userid'         => ['label' => 'Uploaded By', 'hint' => 'Username of whoever ran the upload.'],
    ],
    'memo' => [
        'id' => ['type' => 'readonly'],
        'company' => ['label' => 'Company (masthead + "Firm" name)'],
        'address' => ['label' => 'Address (masthead line 1)'],
        'telno' => ['label' => 'Telephone (masthead line 2)'],
        'fax' => ['label' => 'Fax (masthead line 2)'],
        'gsm' => ['label' => 'GSM / Mobile (masthead line 2)'],
        'email' => ['label' => 'Email (masthead line 3)'],
        'web' => ['label' => 'Website (masthead line 3)'],
        'field1' => ['label' => 'Received-by label'],
        'field2' => ['label' => 'Company line (right)'],
        'field3' => ['label' => 'Signature label'],
        'field4' => ['label' => 'Extra line (right)'],
        'field5' => ['label' => 'Extra line (left)'],
        'field6' => ['type' => 'textarea', 'label' => 'Terms & conditions text'],
        'dated' => ['label' => 'Dated (unix timestamp)'],
    ],
    'dmemo' => [
        'id' => ['type' => 'readonly'],
        'company' => ['label' => 'Company (masthead + "Firm" name)'],
        'address' => ['label' => 'Address (masthead line 1)'],
        'telno' => ['label' => 'Telephone (masthead line 2)'],
        'fax' => ['label' => 'Fax (masthead line 2)'],
        'gsm' => ['label' => 'GSM / Mobile (masthead line 2)'],
        'email' => ['label' => 'Email (masthead line 3)'],
        'web' => ['label' => 'Website (masthead line 3)'],
        'field1' => ['label' => 'Received-by label'],
        'field2' => ['label' => 'Company line (right)'],
        'field3' => ['label' => 'Signature label'],
        'field4' => ['label' => 'Extra line (right)'],
        'field5' => ['label' => 'Extra line (left)'],
        'field6' => ['type' => 'textarea', 'label' => 'Terms & conditions text'],
        'dated' => ['label' => 'Dated (unix timestamp)'],
    ],
    'cut' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'fluorescence' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'polish' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'symmetry' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'shape' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
        'Display _nm' => ['label' => 'Display Name'],
    ],
    'location' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'availability' => [
        'id'      => ['type' => 'readonly'],
        'shortnm' => ['label' => 'Short Name', 'hint' => 'Matched against maindata\'s avail value to look up this row\'s color for the Stock No background on Results/View Cart.'],
        'color'   => ['type' => 'color', 'label' => 'Badge Color'],
        'active'  => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'size' => [
        // `id` is intentionally left without an override: it is NOT
        // auto-increment (ids come from your data source), so it must
        // stay an editable required field on both add and edit.
    ],
    'lab' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'clarity' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
    'color' => [
        'id'     => ['type' => 'readonly'],
        'active' => ['type' => 'select', 'options' => ['yes', 'no']],
    ],
];

/** Columns of type 'forecolor-N' / 'backcolor-N' render as color pickers. */
function crud_is_color_column(string $column): bool
{
    return (bool)preg_match('/^(forecolor|backcolor)-\d+$/', $column);
}

/**
 * Explicit column choice + order for the list view, per table.
 * Tables not listed here fall back to "first N visible columns".
 */
const CRUD_LIST_COLUMNS = [
    'user'           => ['id', 'username', 'emailid', 'company', 'usertype', 'approval', 'last_login'],
    'user_types'     => ['id', 'usertype', 'level'],
    'setup'          => ['id', 'company', 'Page title', 'emailid1', 'telno-1'],
    'path'           => ['id', 'description', 'path'],
    'uploadref'      => ['id', 'colname', 'excolname', 'active'],
    'rounding_rules' => ['id', 'rule_name', 'method', 'increment', 'active', 'sort_order'],
    'fancycolor'     => ['id', 'fncycolor', 'active'],
    'fancyint'       => ['id', 'fncyint', 'active'],
    'upload_date'    => ['id', 'upldfile_date', 'upldfile_time', 'data_uplddate', 'data_upldtime', 'userid'],
    'timings'        => ['id', 'Day', 'start time1', 'end time1', 'holiday'],
    'upload'         => ['id', 'API', 'API link', 'Excel', 'CSV'],
    'maindata'       => ['id', 'StockNo', 'Shape', 'Weight', 'Color', 'Clarity', 'CutGrade', 'Price', 'avail'],
    'diamond_search' => ['id', 'colname', 'fldname', 'active', 'orderid'],
    'results'        => ['id', 'colname', 'fldname', 'active', 'orderid'],
    'diamond_details' => ['id', 'colname', 'fldname', 'data_type', 'active', 'orderid'],
    'adv_filter'      => ['id', 'colname', 'fldname', 'active', 'orderid'],
    'customer'        => ['custid', 'custnm', 'shortnm', 'email', 'address'],
    'rsetup'          => ['id', 'sortfld1', 'sortorder1', 'sortfld2', 'sortorder2'],
    'memo'            => ['id', 'company', 'address', 'telno', 'email'],
    'dmemo'           => ['id', 'company', 'address', 'telno', 'email'],
    'cut'            => ['id', 'cut_id', 'cut', 'order', 'active'],
    'fluorescence'   => ['id', 'flu_id', 'flu', 'order', 'active'],
    'polish'         => ['id', 'pol_id', 'pol', 'order', 'active'],
    'symmetry'       => ['id', 'sym_id', 'sym', 'order', 'active'],
    'shape'          => ['id', 'shape_id', 'shape', 'order', 'Display _nm', 'imgpath', 'active'],
    'location'       => ['id', 'location', 'loc_id', 'shortnm', 'active', 'order'],
    'size'           => ['id', 'sizedesc', 'sizefr', 'sizeto'],
    'lab'            => ['id', 'lab', 'active'],
    'clarity'        => ['id', 'clarity', 'active'],
    'color'          => ['id', 'color', 'active'],
    'availability'   => ['id', 'avail', 'shortnm', 'color', 'active', 'order'],
];

