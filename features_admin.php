<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/features_admin_db.php";
require_once __DIR__ . "/html_table.php";

define("EMPTY_CHECKBOX", "&#9744;");
define("CHECKED_CHECKBOX", "&#9745;");
define("PREVIOUS_PAGE", "&#9204;");
define("NEXT_PAGE", "&#9205;");
define("ROWS_PER_PAGE", 50);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch (true) {
    case isset($_POST['edit_id']):
        edit_form();
        break;
    case isset($_POST['change_col']):
        $res = db_change_column();
        $page = strval(ceil($res['id']/ROWS_PER_PAGE));
        main_form($res['msg'], $page);
        break;
    case isset($_POST['toggle_checkbox']):
        $res = db_change_single();
        header("Content-Type: plain/text");
        print $res;
        break;
    case isset($_POST['post_form']):
        $res = db_process();
        main_form($res['msg'], $res['page']);
        break;
    case isset($_POST['delete_feature']):
        $res = delete_feature();
        main_form($res['msg'], $res['page']);
        break;
    case isset($_POST['cancel_form']):
    case isset($_POST['page']):
        $page = ctype_digit($_POST['page']) ? intval($_POST['page']) : 1;
        main_form("", $page);
        break;
    default:
        main_form();
    }
} else {
    main_form();
}

exit;

/**
 * Display server list and controls to add or edit a server.
 *
 * @param string $response Print any error/success messages from a add or edit.
 * @param integer $page View pages consists of 'ROWS_PER_PAGE' number of rows, each.
 */
function main_form($response="", $page=1) {
    db_connect($db);

    // How many features exist?
    $result = $db->query("SELECT max(`id`) FROM `features`");
    $rows_total = $result->fetch_row()[0];

    // What is the last page?
    $page_last = intval(ceil($rows_total / ROWS_PER_PAGE));

    // requested $page validation.
    // correct $page when validation case proves FALSE.
    switch(false) {
    case $page >= 1:
        $page = 1;
        break;
    case $page <= $page_last:
        $page = $page_last;
        break;
    }

    // Determine which rows (by index) are displayed in this page.
    $row_first = ($page - 1) * ROWS_PER_PAGE + 1;
    $row_last = min($row_first + ROWS_PER_PAGE - 1, $rows_total);

    // formulae for $page_1q and $page_3q preserve equidistance from $page_mid in consideration to intdiv rounding.
    $page_mid = intdiv($page_last, 2);
    $page_1q = $page_mid - intdiv($page_mid, 2);
    $page_3q = $page_mid + intdiv($page_mid, 2);

    //build page controls
    $next_page_sym = NEXT_PAGE;     // added inline to string.
    $next_page     = $page + 1;
    $prev_page_sym = PREVIOUS_PAGE; // added inline to string.
    $prev_page     = $page - 1;

    $disabled_prev_button = $page <= 1          ? " DISABLED" : "";
    $disabled_next_button = $page >= $page_last ? " DISABLED" : "";

    foreach (array("top", "bottom") as $loc) {
        $mid_controls_html = "";
        if ($page_last > 3) {
            $mid_controls_html = <<<HTML
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_mid}' class='btn'>{$page_mid}</button>
            HTML;
        }

        if ($page_last > 7) {
            $mid_controls_html = <<<HTML
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_1q}' class='btn'>{$page_1q}</button>
            {$mid_controls_html}
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_3q}' class='btn'>{$page_3q}</button>
            HTML;
        }

        $page_controls[$loc] = <<<HTML
        <div style='display: inline-block; width: 25%;'>
        <form id='new_feature_{$loc}' action='features_admin.php' method='POST'>
            <input type='hidden' name='page' value='{$page}'>
            <p><button type='submit' form='new_feature_top' name='edit_id' class='btn' value='new'>New Feature</button>
        </form>
        </div>
        <div style='display: inline-block; width: 50%;'>
        <form id='page_controls_{$loc}' action='features_admin.php' method='POST' class='text-center'>
            <button type='submit' form='page_controls_{$loc}' name='page' value='1' class='btn'>1</button>
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$prev_page}' class='btn'{$disabled_prev_button}>{$prev_page_sym}</button>
            {$mid_controls_html}
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$next_page}' class='btn'{$disabled_next_button}>{$next_page_sym}</button>
            <button type='submit' form='page_controls_{$loc}' name='page' value='{$page_last}' class='btn'>{$page_last}</button>
        </form>
        </div>
        <div style='display: inline-block; width: 24%' class='text-right'>Page {$page}</div>
        HTML;
    } // END build page controls

    // Get rows for current $page.
    $sql = "SELECT * FROM `features` WHERE `id` BETWEEN ? AND ? ORDER BY `id` ASC";
    $params = array("ii", $row_first, $row_last);

    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->bind_result($p_id, $p_name, $p_label, $p_show_in_lists, $p_is_tracked);
    $query->store_result();
    $query->execute();
    while ($query->fetch()) {
        $feature_list[] = array(
            'id'            => $p_id,
            'name'          => $p_name,
            'label'         => $p_label,
            'show_in_lists' => $p_show_in_lists,
            'is_tracked'    => $p_is_tracked
        );
    }
    $query->close();
    $db->close();

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
        <form id='change_col_{$col}' action='features_admin.php' method='POST'>
            <input type='hidden' name='col' value='{$col}'>
            <input type='hidden' name='row_first' value='{$row_first}'>
            <input type='hidden' name='row_last' value='{$row_last}'>
            <button type='submit' form='change_col_{$col}' name='change_col' value='{$val}' class='edit-submit'>{$chk}</button>
        </form>
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
            <button type='button' id='{$col}-{$feature['id']}' value='{$val}' class='edit-submit chkbox'>{$checked}</button>
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

    // Print view.
    print_header();

    print <<<HTML
    <h1>Features Administration</h1>
    <p>You may edit an existing feature's name, label, boolean statuses, or add a new feature to the database.
    {$response}
    {$page_controls['top']}
    {$table->get_html()}
    {$page_controls['bottom']}
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script>
        $('.chkbox').click(function() {
            var id = $(this).attr('id');
            var vals = id.split("-");
            vals.push($(this).val());
            var result = $.ajax({
                context: this,
                method: "POST",
                data: {'toggle_checkbox': "1", 'col': vals[0], 'id': vals[1], 'state': vals[2]},
                dataType: "text",
                async: true,
                success: function(response, result) {
                    // Response will be "1" on success or an error message on failure.
                    if (result === "success" && response === "1") {
                        var icon = "&#" + $(this).html().charCodeAt(0) + ";";
                        $(this).val(icon === "{$checked_checkbox}" ? "1" : "0");
                        $(this).html(icon === "{$checked_checkbox}" ? "{$empty_checkbox}" : "{$checked_checkbox}");
                    } else {
                        alert(response);
                    }
                }
            });
        });
    </script>

    HTML;

    print_footer();
} // END function main_form()

/** Add/Edit server form.  No DB operations. */
function edit_form() {
    $id = $_POST['edit_id'];
    $page = ctype_digit($_POST['page']) ? $_POST['page'] : "1";

    // Determine if adding a new server or editing an existing server.
    // Cancel (return null) if something is wrong.
    switch(true) {
    case ctype_digit($id):
        $feature_details = feature_details_by_getid($id);
        if ($feature_details === false) {
            main_form();
            return null;
        }
        break;
    case $id === "new":
        $feature_details = array('name'=>"", 'label'=>"", 'show_in_lists'=>'1', 'is_tracked'=>'1');
        break;
    default:
        main_form();
        return null;
    }

    // print view
    $is_checked['show_in_lists'] = $feature_details['show_in_lists'] === "1" ? " CHECKED" : "";
    $is_checked['is_tracked'] = $feature_details['is_tracked'] === "1" ? " CHECKED" : "";
    $delete_button = $id === 'new' ? "" : "<button type='button' class='btn edit-form' id='delete-button'>Remove</button>";
    print_header();

    print <<<HTML
    <h1>Feature Details</h1>
    <form action='features_admin.php' method='post' class='edit-form'>
        <div class='edit-form block'>
            <label for='name'>Name</label><br>
            <input type='text' name='name' id='name' class='edit-form' value='{$feature_details['name']}'>
        </div><div class='edit-form block'>
            <label for='label'>Label</label><br>
            <input type='text' name='label' id='label' class='edit-form' value='{$feature_details['label']}'>
        </div><div class='edit-form inline-block'>
            <label for='show_in_lists'>Show In Lists?</label>
            <input type='checkbox' name='show_in_lists' id='show_in_lists' class='edit-form'{$is_checked['show_in_lists']}>
            <label for='is_tracked'>Is Tracked?</label>
            <input type='checkbox' name='is_tracked' id='is_tracked' class='edit-form'{$is_checked['is_tracked']}>
            <input type='hidden' name='id' value='{$id}'>
            <input type='hidden' name='page' value='{$page}'>
            <input type='hidden' id='delete-feature'>
        </div><div class='edit-form inline-block float-right'>
            <button type='submit' class='btn btn-cancel' name='cancel_form' value='1'>Cancel</button>
            <button type='submit' class='btn btn-primary edit-form' name='post_form' value='1'>Submit</button>
            {$delete_button}
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
        $('#delete-button').click(function() {
            var name = $('#name').val();
            if (confirm("Confirm removal for \\"" + name + "\\"\\n*** THIS WILL REMOVE ALL USAGE HISTORY\\n*** THIS CANNOT BE UNDONE")) {
                $('#delete-feature').attr('name', "delete_feature");
                $('#delete-feature').attr('value', "1");
                $('form').submit();
            }
        });
        </script>
    </form>
    HTML;

    print_footer();
} // END function edit_form()
?>
