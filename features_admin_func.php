<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

function func_get_features_table_html($feature_list) {
    $table = new html_table(array('class' => "table alt-rows-bgcolor"));
    $headers = array("ID", "Name", "Label", "Show In Lists", "Is Tracked", "");
    $table->add_row($headers, array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    // Add an "uncheck/check all" button for checkbox columns
    foreach (array('show_in_lists', 'is_tracked') as $col) {
        $res = in_array(1, array_column($feature_list, $col), false);
        $val = $res ? 0 : 1;
        $chk = $res ? "UNCHECK ALL" : "CHECK ALL";
        $html[$col] = <<<HTML
        <button type='button' value='{$val}' name='{$col}' class='edit-submit column_checkboxes'>{$chk}</button>
        HTML;
    }
    $table->add_row(array("", "", "", $html['show_in_lists'], $html['is_tracked'], ""), array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    // Build each feature row: `id`, `name`, `label`, `show_in_lists`, `is_tracked`, and EDIT control.
    $checked_checkbox = CHECKED_CHECKBOX;
    $empty_checkbox = EMPTY_CHECKBOX;
    foreach($feature_list as $feature) {
        foreach(array("show_in_lists", "is_tracked") as $col) {
            $checked = $feature[$col] ? $checked_checkbox : $empty_checkbox;
            $val = $feature[$col] ? 0 : 1;
            $html[$col] = <<<HTML
            <button type='button' id='{$col}-{$feature['id']}' value='{$val}' class='edit-submit btnlink chkbox'>{$checked}</button>
            HTML;
        }

        $html['edit_button'] = <<<HTML
        <form id='edit_form_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='page' value='{$page}'>
            <button type='submit' form='edit_form_{$feature['id']}' name='edit_id' class='edit-submit' value='{$feature['id']}'>EDIT</button>
        </form>
        HTML;

        $row = array(
            $feature['id'],
            $feature['name'],
            $feature['label'],
            $html['show_in_lists'],
            $html['is_tracked'],
            $html['edit_button']
        );

        $table->add_row($row);
        $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));  // class referred by bootstrap
    }

    return $table->get_html();
} // END func_get_feature_table_html()

function func_get_controlpanel_html($current_page, $last_page, $search_token="") {
    // SZetup page control properties.
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

    // Setup search bar properties
    if ($search_token === "") {
        $search_icon = SEARCH_ICON;
        $search_value = "";
    } else {
        $search_icon = CANCEL_SEARCH;
        $search_value = "value='{$search_token}' ";
    }

    foreach (array("top", "bottom") as $loc) {
        $mid_controls_html = "";
        if ($last_page > 3) {
            $mid_controls_html = <<<HTML
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_mid}' class='btn'>{$page_mid}</button>
            HTML;
        }

        if ($last_page > 7) {
            $mid_controls_html = <<<HTML
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_1q}' class='btn'>{$page_1q}</button>
            {$mid_controls_html}
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_3q}' class='btn'>{$page_3q}</button>
            HTML;
        }

        $page_controls[$loc] = <<<HTML
        <!-- BEGIN Control Panel {$loc} -->
        <div style='width: 15%; margin-bottom: 10px;' class='inline-block'>
        <form id='new_feature_{$loc}' action='features_admin.php' method='POST'>
            <button type='submit' form='new_feature_top' name='edit_id' class='btn' value='new'>New Feature</button>
        </form>
        </div>
        <div style='width: 44%;' class='inline-block'>
        <input id='search_box_{$loc}' class='search_box' type="text" placeholder='Search' style='width: 365px;' aria-label='search' required />
        <button type='button' form='search_{$loc}' class='edit-submit btnlink search_button'>{$search_icon}</button>
        </div>
        <div style='width: 34%;' class='inline-block text-center'>
        <form id='page_controls_{$loc}' action='features_admin.php' method='POST'>
            <button type='submit' form='page_controls_{$loc}' name='page' value='1' class='btn'>1</button>
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$prev_page}' class='btn'{$disabled_prev_button}>{$prev_page_sym}</button>
            {$mid_controls_html}
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$next_page}' class='btn'{$disabled_next_button}>{$next_page_sym}</button>
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$last_page}' class='btn'>{$last_page}</button>
        </form>
        </div>
        <div style='width: 5%' class='inline-block text-right'>Page {$current_page}</div>
        <!-- END Control Panel {$loc} -->
        HTML;
    }

    return $page_controls;
} // END func_get_controlpanel()

function get_checkbox_ajax() {
    $checked_checkbox = CHECKED_CHECKBOX;
    $empty_checkbox = EMPTY_CHECKBOX;

    return <<<JS

    JS;
} // END get_checkbox_ajax()

function get_checkbox_column_ajax() {
    return <<<JS

    JS;
}


?>
