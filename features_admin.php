<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

define("EMPTY_CHECKBOX", "&#9744;");
define("CHECKED_CHECKBOX", "&#9745;");
define("ROWS_PER_PAGE", 50);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    //print_var($_POST); die;
    switch (true) {
    case isset($_POST['edit_id']):
        edit_form();
        break;
    case isset($_POST['checkall']):
        $msg = db_change_column();
        main_form($msg);
        break;
    case isset($_POST['show_in_lists']):
    case isset($_POST['is_tracked']):
        $msg = db_change_single();
        main_form($msg);
        break;
    default:
        $msg = db_process();
        main_form($msg);
    }
} else {
    $page = ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['page'])) ? $_GET['page'] : 1;
    main_form("", $page);
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
    $result = $db->query("SELECT count(*) FROM `features`");
    $rows_total = $result->fetch_row()[0];

    // Determine which rows (by index) are displayed in this page.
    $page_start = ($page - 1) * ROWS_PER_PAGE + 1;
    $page_end = min($page_start + ROWS_PER_PAGE - 1, $rows_total);

    // Check that page is within bounds.
    if ($page_start > $page_end) {
        // Recalculate start and end with start = 0 (page 1).
        $page_start = 1;
        $page_end = min($page_start + ROWS_PER_PAGE, $rows_total);
    }

    // Get rows for current $page.
    $sql = "SELECT * FROM `features` WHERE `id` BETWEEN ? AND ? ORDER BY `id` ASC";
    $params = array("ii", $page_start, $page_end);

    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->bind_result($_id, $_name, $_label, $_show_in_lists, $_is_tracked);
    $query->store_result();
    $query->execute();
    while ($query->fetch()) {
        $feature_list[] = array(
            'id'            => $_id,
            'name'          => $_name,
            'label'         => $_label,
            'show_in_lists' => $_show_in_lists,
            'is_tracked'    => $_is_tracked
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
        $chk_html[$col] = <<<HTML
        <form id='checkall_{$col}' action='features_admin.php' method='POST'>
            <input type='hidden' name='col' value='{$col}'>
            <input type='hidden' name='page_start' value='{$page_start}'>
            <input type='hidden' name='page_end' value='{$page_end}'>
            <button type='submit' form='checkall_{$col}' name='checkall' value='{$val}' class='edit-submit'>{$chk}</button>
        </form>
        HTML;
    }
    $table->add_row(array("", "", "", $chk_html['show_in_lists'], $chk_html['is_tracked'], ""), array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    foreach($feature_list as $feature) {
        $show_in_lists['checked'] = $feature['show_in_lists'] ? CHECKED_CHECKBOX : EMPTY_CHECKBOX;
        $show_in_lists['val'] = $feature['show_in_lists'] ? 0 : 1;
        $show_in_lists['html'] = <<<HTML
        <form id='sil_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='id' value='{$feature['id']}'>
            <button type='submit' form='sil_{$feature['id']}' name='show_in_lists' class='edit-submit chkbox' value='{$show_in_lists['val']}'>{$show_in_lists['checked']}</button>
        </form>
        HTML;

        $is_tracked['checked'] = $feature['is_tracked'] ? CHECKED_CHECKBOX : EMPTY_CHECKBOX;
        $is_tracked['val'] = $feature['is_tracked'] ? 0 : 1;
        $is_tracked['html'] = <<<HTML
        <form id='it_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='id' value='{$feature['id']}'>
            <button type='submit' form='it_{$feature['id']}' name='is_tracked' class='edit-submit chkbox' value='{$is_tracked['val']}'>{$is_tracked['checked']}</button>
        </form>
        HTML;

        $edit_form_button_html = <<<HTML
        <form id='edit_form_{$feature['id']}' action='features_admin.php' method='POST'>
        <button type='submit' form='edit_form_{$feature['id']}' name='edit_id' class='edit-submit' value='{$feature['id']}'>EDIT</button>
        </form>
        HTML;

        $row = array(
            $feature['id'],
            $feature['name'],
            $feature['label'],
            $show_in_lists['html'],
            $is_tracked['html'],
            $edit_form_button_html
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
    <form id='new_feature_1' action='features_admin.php' method='POST'>
    <p><button type='submit' form='new_feature_1' name='edit_id' class='btn' value='new'>New Feature</button>
    </form>
    {$table->get_html()}
    <form id='new_feature_2' action='features_admin.php' method='POST'>
    <p><button type='submit' form='new_feature_2' name='edit_id' class='btn' value='new'>New Feature</button>
    </form>
    <script>
    {$script}
    </script>
    HTML;

    print_footer();
} // END function main_form()

/** Add/Edit server form.  No DB operations. */
function edit_form() {
    $id = $_POST['edit_id'];

    // Determine if adding a new server or editing an existing server.
    // Skip back to the main_form() if something is wrong.
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
    $is_checked['show_in_lists'] = $feature_details['show_in_lists'] === '1' ? " CHECKED" : "";
    $is_checked['is_tracked'] = $feature_details['is_tracked'] === '1' ? " CHECKED" : "";
    print_header();

    print <<<HTML
    <h1>Feature Details</h1>
    <form action='features_admin.php' method='post' class='edit-form'>
        <div class='edit-form'>
            <label for='name'>Name</label><br>
            <input type='text' name='name' id='name' class='edit-form' value='{$feature_details['name']}'>
        </div><div class='edit-form'>
            <label for='label'>Label</label><br>
            <input type='text' name='label' id='label' class='edit-form' value='{$feature_details['label']}'>
        </div><div class='edit-form'>
            <label for='show_in_lists'>Show In Lists?</label>
            <input type='checkbox' name='show_in_lists' id='show_in_lists' class='edit-form'{$is_checked['show_in_lists']}>
            <label for='is_tracked'>Is Tracked?</label>
            <input type='checkbox' name='is_tracked' id='is_tracked' class='edit-form'{$is_checked['is_tracked']}>
            <input type='hidden' name='id' value='{$id}'>
            <button type='submit' class='edit-form btn'>Submit</button>
        </div>
    </form>
    HTML;

    print_footer();
} // END function edit_form()

/**
 * Change the status of either 'show_in_lists' or is_tracked' for all features.
 *
 * @return string response message to display on main form.  Or empty string for no message.
 */
function db_change_column() {
    // extract values from POST
    if (isset($_POST['checkall']))   $checked    = $_POST['checkall'];
    if (isset($_POST['col']))        $col        = $_POST['col'];
    if (isset($_POST['page_start'])) $page_start = $_POST['page_start'];
    if (isset($_POST['page_end']))   $page_end   = $_POST['page_end'];

    // validate
    switch (false) {
    case isset($checked)     && preg_match("/^[01]$/", $checked):
    case isset($col)         && preg_match("/^show_in_lists$|^is_tracked$/", $col):
    case isset($page_start)  && ctype_digit($page_start):
    case isset($page_end)    && ctype_digit($page_end):
    case intval($page_start) <= intval($page_end):
        // Return to main form.  No DB process.
        return "Validation failed for 'db_change_column()'";
    }

    //To Do: Page processing with $vals['start'] and $vals['end']
    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id` BETWEEN ? AND ?";
    $params = array("iii", intval($checked), intval($page_start), intval($page_end));

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = "<p class='red-text'>&#10006; DB Error: {$db->error}.";
    } else {
        $response_msg = "";
    }

    $query->close();
    $db->close();
    return $response_msg;
}

function db_change_single() {
    if (isset($_POST['id'])) $id = $_POST['id'];
    if (isset($_POST['show_in_lists'])) {
        $col = 'show_in_lists';
        $val = $_POST['show_in_lists'];
    } else if (isset($_POST['is_tracked'])) {
        $col = 'is_tracked';
        $val = $_POST['is_tracked'];
    }

    //validate
    switch(false) {
    case isset($id)  && ctype_digit($id):
    case isset($col) && preg_match("/^show_in_lists$|^is_tracked$/", $col):
    case isset($val) && preg_match("/^[01]$/", $val):
        // Return to main form.  No DB process.
        return "Validation failed for 'db_change_single()'";
    }

    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id`=?";
    $params = array("ii", intval($val), intval($id));

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = "<p class='red-text'>&#10006; DB Error: {$db->error}.";
    } else {
        $response_msg = "";
    }

    $query->close();
    $db->close();
    return $response_msg;
}


/**
 * DB operation to either add or edit a feature, based on $_POST['id']
 *
 * @return string response message from operation (either success or error message).
 */
function db_process() {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $label = empty($_POST['label']) ? null : $_POST['label'];
    $show_in_lists = $_POST['show_in_lists'] === "on" || $_POST['show_in_lists'] === true ? 1 : 0;
    $is_tracked = $_POST['is_tracked'] === "on" || $_POST['is_tracked'] === true ? 1 : 0;

    // Error check.  On error, stop and return error message.
    switch(false) {
    // $id must be all numbers or the word "new"
    case preg_match("/^\d+$|^new$/", $id):
        return "<p class='red-text'>&#10006; Invalid feature ID \"{$id}\"";
    // $name cannot be blank
    case !empty($name):
        return "<p class='red-text'>&#10006; Feature name cannot be blank";
    }
    // $label can be blank.
    // END error check

    if ($id === "new") {
        // Adding a new server
        $sql = "INSERT INTO `features` (`name`, `label`, `show_in_lists`, `is_tracked`) VALUES (?, ?, ?, ?)";
        $params = array("ssii", $name, $label, $show_in_lists, $is_tracked);
        $op = "added";
    } else {
        // Editing an existing server
        $sql = "UPDATE `features` SET `name`=?, `label`=?, `show_in_lists`=?, `is_tracked`=? WHERE `ID`=?";
        $params = array("ssiii", $name, $label, $show_in_lists, $is_tracked, $id);
        $op = "updated";
    }

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        if (!empty($label)) $label = " ({$label})";
        $response_msg = "<p class='green-text'>&#10004; {$name}{$label} successfully {$op}.";
    } else {
        $response_msg = "<p class='red-text'>&#10006; (${name}) DB Error: {$db->error}.";
    }

    $query->close();
    $db->close();
    return $response_msg;
} // END function db_process()

/**
 * Retrieve server details by server ID.
 *
 * @param int $id
 * @return array server's name, label and active status.
 */
function feature_details_by_getid($id) {
    if (!ctype_digit($id)) {
        return false;
    }

    $id = intval($id);

    db_connect($db);
    $sql = "SELECT `name`, `label`, `show_in_lists`, `is_tracked` FROM `features` WHERE `id`={$id}";
    $result = $db->query($sql);
    $features_list = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $db->close();

    return !empty($features_list) ? $features_list[0] : false;
} // END function server_details_by_getid()
?>
