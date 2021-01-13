<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

define("EMPTY_CHECKBOX", "&#9744;");
define("CHECKED_CHECKBOX", "&#9745;");
define("PREVIOUS_PAGE", "&#9204;");
define("NEXT_PAGE", "&#9205;");
define("ROWS_PER_PAGE", 50);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    //print_var($_POST); die;
    switch (true) {
    case isset($_POST['edit_id']):
        edit_form();
        break;
    case isset($_POST['change_col']):
        $res = db_change_column();
        $page = strval(ceil($res['id']/ROWS_PER_PAGE));
        main_form($res['msg'], $page);
        break;
    case isset($_POST['change_show_in_lists']):
    case isset($_POST['change_is_tracked']):
        $res = db_change_single();
        $page = intval(ceil($res['id']/ROWS_PER_PAGE));
        main_form($res['msg'], $page);
        break;
    case isset($_POST['post_form']):
        $res = db_process();
        main_form($res['msg'], $res['page']);
        break;
    case isset($_POST['delete_feature']);
        $res = delete_feature();
        main_form($res['msg'], $res['page']);
        break;
    default:
        main_form();
    }
} else {
    $page = ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['page'])) ? $_GET['page'] : 1;
    $page = ctype_digit($page) ? intval($page) : 1; // $page must be an integer.
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

    $disabled_prev_button = $page <= 1          ? "DISABLED" : "";
    $disabled_next_button = $page >= $page_last ? "DISABLED" : "";

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
        <form id='page_controls_{$loc}' action='features_admin.php' method='GET' class='text-center'>
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
        $chk_html[$col] = <<<HTML
        <form id='change_col_{$col}' action='features_admin.php' method='POST'>
            <input type='hidden' name='col' value='{$col}'>
            <input type='hidden' name='row_first' value='{$row_first}'>
            <input type='hidden' name='row_last' value='{$row_last}'>
            <button type='submit' form='change_col_{$col}' name='change_col' value='{$val}' class='edit-submit'>{$chk}</button>
        </form>
        HTML;
    }
    $table->add_row(array("", "", "", $chk_html['show_in_lists'], $chk_html['is_tracked'], ""), array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    // Build each feature row: `id`, `name`, `label`, `show_in_lists`, `is_tracked`, and EDIT control.
    foreach($feature_list as $feature) {
        $show_in_lists['checked'] = $feature['show_in_lists'] ? CHECKED_CHECKBOX : EMPTY_CHECKBOX;
        $show_in_lists['val'] = $feature['show_in_lists'] ? 0 : 1;
        $show_in_lists['html'] = <<<HTML
        <form id='sil_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='id' value='{$feature['id']}'>
            <button type='submit' form='sil_{$feature['id']}' name='change_show_in_lists' class='edit-submit chkbox' value='{$show_in_lists['val']}'>{$show_in_lists['checked']}</button>
        </form>
        HTML;

        $is_tracked['checked'] = $feature['is_tracked'] ? CHECKED_CHECKBOX : EMPTY_CHECKBOX;
        $is_tracked['val'] = $feature['is_tracked'] ? 0 : 1;
        $is_tracked['html'] = <<<HTML
        <form id='it_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='id' value='{$feature['id']}'>
            <button type='submit' form='it_{$feature['id']}' name='change_is_tracked' class='edit-submit chkbox' value='{$is_tracked['val']}'>{$is_tracked['checked']}</button>
        </form>
        HTML;

        $edit_form_button_html = <<<HTML
        <form id='edit_form_{$feature['id']}' action='features_admin.php' method='POST'>
            <input type='hidden' name='page' value='{$page}'>
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
    {$page_controls['top']}
    {$table->get_html()}
    {$page_controls['bottom']}
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
            <input type='hidden' name='page' value='{$page}'>
            <button type='submit' class='edit-form btn' name='post_form' value='1'>Submit</button>
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
    if (isset($_POST['change_col'])) $checked   = $_POST['change_col'];
    if (isset($_POST['col']))        $col       = $_POST['col'];
    if (isset($_POST['row_first']))  $row_first = $_POST['row_first'];
    if (isset($_POST['row_last']))   $row_last  = $_POST['row_last'];

    // validate
    switch (false) {
    case isset($checked)    && preg_match("/^[01]$/", $checked):
    case isset($col)        && preg_match("/^show_in_lists$|^is_tracked$/", $col):
    case isset($row_first)  && ctype_digit($row_first):
    case isset($row_last)   && ctype_digit($row_last):
    case intval($row_first) <= intval($row_last):
        // Return to main form.  No DB process.
        return array('msg'=>"<p class='red-text'>&#10006; Validation failed for 'db_change_column()'", 'id'=>"1");
    }

    //To Do: Page processing with $vals['start'] and $vals['end']
    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id` BETWEEN ? AND ?";
    $params = array("iii", intval($checked), intval($row_first), intval($row_last));

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = array('msg'=>"<p class='red-text'>&#10006; DB Error: {$db->error}.", 'id'=>intval($row_first));
    } else {
        $response_msg = array('msg'=>"", 'id'=>intval($row_first));
    }

    $query->close();
    $db->close();
    return $response_msg;
}

function db_change_single() {
    if (isset($_POST['id'])) $id = $_POST['id'];
    if (isset($_POST['change_show_in_lists'])) {
        $col = 'show_in_lists';
        $val = $_POST['change_show_in_lists'];
    } else if (isset($_POST['change_is_tracked'])) {
        $col = 'is_tracked';
        $val = $_POST['change_is_tracked'];
    }

    //validate
    switch(false) {
    case isset($id)  && ctype_digit($id):
    case isset($col) && preg_match("/^show_in_lists$|^is_tracked$/", $col):
    case isset($val) && preg_match("/^[01]$/", $val):
        // Return to main form.  No DB process.
        return array('msg'=>"<p class='red-text'>&#10006; Validation failed for 'db_change_single()'", 'id'=>"1");
    }

    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id`=?";
    $params = array("ii", intval($val), intval($id));

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = array('msg'=>"<p class='red-text'>&#10006; DB Error: {$db->error}.", 'id'=>intval($id));
    } else {
        $response_msg = array('msg'=>"", 'id'=>intval($id));
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
    $page = ctype_digit($_POST['page']) ? intval($_POST['page']) : 1;

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
        $id = intval($id);
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

    return array('msg'=>$response_msg, 'page'=>$page);
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

/**
 * Delete feature from DB by `id`
 *
 * @return array success/error message and page to return to.
 */
function delete_feature() {

    // validate
    switch (false) {
    case ctype_digit($_POST['id']):
    case ctype_digit($_POST['page']):
        // Do not process
        return array('msg'=>"Request to delete a feature has failed validation.", 'page'=>1);
    }

    $id = $_POST['id'];
    $name = $_POST['name'];
    $page = $_POST['page'];

    db_connect($db);
    $sql = "DELETE FROM `features` WHERE `id`=?";
    $params = array("i", intval($id));
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute;

    if (empty($db->error_list)) {
        $response = "<span class='green-text'>&#10004; Successfully deleted ID {$id}: {$name}</span>";
    } else {
        $response = "<p class='red-text'>&#10006; ID ${id}: ${name}, DB Error: {$db->error}.";
    }

    $query->close();
    $db->close();

    return array('msg'=>$response, 'page'=>intval($page));
} //END function delete_feature()
?>
