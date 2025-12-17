<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

// Features admin view control icons
// Open source SVG Material Icons retrieved from https://fonts.google.com/icons
define("SEARCH_ICON", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2f6fa7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>');
define("CANCEL_SEARCH", '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#2f6fa7"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M15.5,14h-0.79l-0.28-0.27C15.41,12.59,16,11.11,16,9.5C16,5.91,13.09,3,9.5,3C6.08,3,3.28,5.64,3.03,9h2.02 C5.3,6.75,7.18,5,9.5,5C11.99,5,14,7.01,14,9.5S11.99,14,9.5,14c-0.17,0-0.33-0.03-0.5-0.05v2.02C9.17,15.99,9.33,16,9.5,16 c1.61,0,3.09-0.59,4.23-1.57L14,14.71v0.79l5,4.99L20.49,19L15.5,14z"/><polygon points="6.47,10.82 4,13.29 1.53,10.82 0.82,11.53 3.29,14 0.82,16.47 1.53,17.18 4,14.71 6.47,17.18 7.18,16.47 4.71,14 7.18,11.53"/></g></g></svg>');
define("EMPTY_CHECKBOX", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2f6fa7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>');
define("CHECKED_CHECKBOX", '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2f6fa7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM17.99 9l-1.41-1.42-6.59 6.59-2.58-2.57-1.42 1.41 4 3.99z"/></svg>');
define("PREVIOUS_PAGE", '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.61 7.41L14.2 6l-6 6 6 6 1.41-1.41L11.03 12l4.58-4.59z"/></svg>');
define("NEXT_PAGE", '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M10.02 6L8.61 7.41 13.19 12l-4.58 4.59L10.02 18l6-6-6-6z"/></svg>');

/**
 * Get HTML of control panels and features table to be inserted into DOM.
 *
 * Requested via AJAX.  Requires 'page' number and 'search-token' via $_POST.
 * Search token should be an empty string when no search is requested.
 *
 * @return string HTML for control panels and features table.
 */
function func_get_page() {
    clean_post();
    switch (false) {
    case isset($_POST['page']) && ctype_digit($_POST['page']):
    case isset($_POST['search_token']):
        return "<span class='text-danger'><strong>Page view validation error</strong></span>";
    }

    $page = ctype_digit($_POST['page']) ? intval($_POST['page']) : 1;
    $search_token = $_POST['search_token'];
    $res = db_get_page_data($page, $search_token);
    $controls_html = func_get_controlpanel_html($page, $res['last_page'], $search_token);
    $table_html = func_get_features_table_html($res['features']);

    return $controls_html['top'] . $table_html . $controls_html['bottom'];
} // END function func_get_page()

/**
 * Get HTML for features table.  Requires feature's table result set from DB.
 *
 * @param array HTML tabvle of $feature_list
 */

function func_get_features_table_html($feature_list) {
    $table = new html_table(array('class' => "table alt-rows-bgcolor"));
    $headers = array("Name", "Label", "Show In Lists", "Is Tracked","");
    $table->add_row($headers, array(), "th");
    $table->update_cell($table->get_rows_count()-1, 2, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));

    // Add an "uncheck/check all" button for checkbox columns
    foreach (array('show_in_lists', 'is_tracked') as $col) {
        $res = in_array(1, array_column($feature_list, $col), false);
        $val = $res ? 0 : 1;
        $chk = $res ? "UNCHECK ALL" : "CHECK ALL";
        $aria_val = $res ? "uncheck entire column {$col}" : "check entire column {$col}";
        $html[$col] = <<<HTML
        <button type='button' value='{$val}' name='{$col}' class='btn btn-link column_checkboxes' style='font-size:inherit;' aria-label='{$aria_val}'>{$chk}</button>
        HTML;
    }
    $table->add_row(array("", "", $html['show_in_lists'], $html['is_tracked'], ""), array(), "td");
    $table->update_cell($table->get_rows_count()-1, 2, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-right"));

    // Build each feature row: `id`, `name`, `label`, `show_in_lists`, `is_tracked`, and EDIT control.
    $checked_checkbox = CHECKED_CHECKBOX;
    $empty_checkbox = EMPTY_CHECKBOX;
    foreach($feature_list as $feature) {
        foreach(array("show_in_lists", "is_tracked") as $col) {
            $checked = $feature[$col] ? $checked_checkbox : $empty_checkbox;
            $val = $feature[$col] ? 1 : 0;
            $aria_val = $feature[$col] ? "on" : "off";
            $html[$col] = <<<HTML
            <button type='button' id='{$col}-{$feature['id']}' value='{$val}' class='btn btn-link single_checkbox' aria-label='{$feature['name']} {$col} checkbox {$aria_val}'>{$checked}</button>
            HTML;
        }

        $html['edit_button'] = <<<HTML
        <form id='edit_form_{$feature['id']}' action='features_admin.php' method='POST'>
            <button type='submit' form='edit_form_{$feature['id']}' name='edit-feature' class='btn btn-link' value='{$feature['id']}' aria-label='edit {$feature['name']}'>EDIT</button>
        </form>
        HTML;

        $row = array(
            $feature['name'],
            $feature['label'],
            $html['show_in_lists'],
            $html['is_tracked'],
            $html['edit_button']
        );

        $table->add_row($row);
        $table->update_cell($table->get_rows_count()-1, 0, null, null, "th"); // feature name is a row header
        $table->update_cell($table->get_rows_count()-1, 2, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-right"));  // class referred by bootstrap
    }

    return $table->get_html();
} // END func_get_feature_table_html()

function func_get_controlpanel_html($current_page, $last_page, $search_token="") {
    // Setup page control properties.
    // Only show controls when there is more than one page
    if ($last_page === 1) {
        $page_navigation = "";
    } else {
        // formulae for $page_1q and $page_3q preserve equidistance from $page_mid in consideration to intdiv rounding.
        $page_mid = intval(ceil($last_page / 2));
        $page_1q = $page_mid - intdiv($page_mid, 2);
        $page_3q = $page_mid + intdiv($page_mid, 2);

        $next_page_sym = NEXT_PAGE;     // added inline to string.
        $next_page     = $current_page + 1;
        $prev_page_sym = PREVIOUS_PAGE; // added inline to string.
        $prev_page     = $current_page - 1;

        $disabled_prev_button = $current_page <= 1          ? " DISABLED" : "";
        $disabled_next_button = $current_page >= $last_page ? " DISABLED" : "";

        if ($last_page > 7) {
            $page_mid_controls_html = <<<HTML
            <button name='page' value='{$page_1q}' class='btn' aria-label='go to page {$page_1q}'>{$page_1q}</button>
            <button name='page' value='{$page_mid}' class='btn' aria-label='go to page {$page_mid}'>{$page_mid}</button>
            <button name='page' value='{$page_3q}' class='btn' aria-label='go to page {$page_3q}'>{$page_3q}</button>
            HTML;
        } else if ($last_page > 3) {
            $page_mid_controls_html = <<<HTML
            <button name='page' value='{$page_mid}' class='btn' aria-label='go to page {$page_mid}'>{$page_mid}</button>
            HTML;
        } else {
            $page_mid_controls_html = "";
        }

        $page_navigation = <<<HTML
        <button name='page' value='1' class='btn' aria-label='go to page 1'>1</button>
        <button name='page' value='{$prev_page}' class='btn' aria-label='go to page {$prev_page}'{$disabled_prev_button}>{$prev_page_sym}</button>
        {$page_mid_controls_html}
        <button name='page' value='{$next_page}' class='btn' aria-label='go to page {$next_page}'{$disabled_next_button}>{$next_page_sym}</button>
        <button name='page' value='{$last_page}' class='btn' aria-label='go to page {$last_page}'>{$last_page}</button>
        HTML;
    }

    // Setup search bar properties
    if ($search_token === "") {
        $search_icon = SEARCH_ICON;
        $search_value = "";
        $disabled_search_box = "";
        $aria_label_button = "show search results";
    } else {
        $search_icon = CANCEL_SEARCH;
        $search_value = "value='{$search_token}' ";
        $disabled_search_box = " disabled";
        $aria_label_button = "stop showing search results";
    }

    // Search box only appears on top control panel.
    $search_box['top'] = <<<HTML
    <input type="text" id='search_box' placeholder='Search' style='width: 365px;' {$search_value}aria-label='enter search text'{$disabled_search_box} />
    <button type='button' id='search_button' class='btn btn-link btn-search' aria-label='{$aria_label_button}'>{$search_icon}</button>
    HTML;

    $search_box['bottom'] = "";

    foreach (array("top", "bottom") as $loc) {
        $page_controls[$loc] = <<<HTML
        <!-- BEGIN Control Panel {$loc} -->
        <div style='width: 15%; margin-bottom: 10px;' class='inline-block'>
        <form id='new_feature_{$loc}' action='features_admin.php' method='POST'>
            <button type='submit' form='new_feature_top' name='edit-feature' class='btn' value='new'>New Feature</button>
        </form>
        </div>
        <div style='width: 44%;' class='inline-block'>
            {$search_box[$loc]}
        </div>
        <div style='width: 29%;' class='inline-block text-center'>
            {$page_navigation}
        </div>
        <div style='width: 10%' class='inline-block text-right'>Page {$current_page}</div>
        <!-- END Control Panel {$loc} -->
        HTML;
    }

    return $page_controls;
} // END func_get_controlpanel()

?>
