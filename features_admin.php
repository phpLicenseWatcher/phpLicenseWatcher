<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch (true) {
    case isset($_POST['edit_id']):
        edit_form();
        break;
    case isset($_POST['checkall']):
        $msg = db_checkall();
        main_form($msg);
        break;
    default:
        $msg = db_process();
        main_form($msg);
    }
} else {
    main_form();
}

exit;


/**
 * Display server list and controls to add or edit a server.
 *
 * @param string $response Print any error/success messages from a add or edit.
 */
function main_form($response="") {
    db_connect($db);
    $result = $db->query("SELECT * FROM `features`");
    $feature_list = $result->fetch_all(MYSQLI_ASSOC);
    $end_page_count = count($feature_list);
    $db->close();

    $table = new html_table(array('class' => "table alt-rows-bgcolor"));
    $headers = array("ID", "Name", "Label", "Show In Lists", "Is Tracked", "");
    $table->add_row($headers, array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    // Add an "uncheck/check all" button for checkbox columns
    foreach (array('show_in_lists', 'is_tracked') as $col) {
        $res = in_array('1', array_column($feature_list, $col), true);
        if ($res) {
            $master_chk[$col] = "<button type='submit' form='feature_list' name='checkall' value='{$col}.0.1.{$end_page_count}' class='edit-submit'>UNCHECK ALL</button>";
        } else {
            $master_chk[$col] = "<button type='submit' form='feature_list' name='checkall' value='{$col}.1.1.{$end_page_count}' class='edit-submit'>CHECK ALL</button>";
        }
    }
    $table->add_row(array("", "", "", $master_chk['show_in_lists'], $master_chk['is_tracked'], ""), array(), "th");
    $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center"));
    $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));

    foreach($feature_list as $feature) {
        $show_in_lists_checked = $feature['show_in_lists'] ? " CHECKED>" : ">";
        $is_tracked_checked = $feature['is_tracked'] ? " CHECKED>" : ">";
        $row = array(
            $feature['id'],
            $feature['name'],
            $feature['label'],
            "<input class='show_in_lists' type='checkbox'{$show_in_lists_checked}",
            "<input class='is_tracked' type='checkbox'{$is_tracked_checked}",
            "<button type='submit' form='feature_list' name='edit_id' class='edit-submit' value='{$feature['id']}'>EDIT</button>"
        );

        $table->add_row($row);
        $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"text-center")); // class referred by bootstrap
        $table->update_cell($table->get_rows_count()-1, 5, array('class'=>"text-right"));  // class referred by bootstrap
    }

    // Print view.
    print_header();

    print <<<HTML
    <h1>Server Administration</h1>
    <p>You may edit an existing server's name, label, active status, or add a new server to the database.<br>
    Server names must be unique and in the form of "<code>port@domain.tld</code>".
    {$response}
    <form id='feature_list' action='features_admin.php' method='POST'>
    <p><button type='submit' form='feature_list' name='edit_id' class='btn' value='new'>New Server</button>
    {$table->get_html()}
    <p><button type='submit' form='feature_list' name='edit_id' class='btn' value='new'>New Server</button>
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
function db_checkall() {
    $vals = array_combine(array('column', 'checked', 'start', 'end'), explode(".", $_POST['checkall']));
    if (!$vals) {
        // silently return to main form, no DB process.
        return "";
    }

    // validate
    switch (false) {
    case preg_match("/^show_in_lists$|^is_tracked$/", $vals['column']):
    case preg_match("/^0$|^1$/", $vals['checked']):
    case ctype_digit($vals['start']):
    case ctype_digit($vals['end']):
    case intval($vals['start']) <= intval($vals['end']):
        // silently return to main form, no DB process.
        return "";
    }

    //To Do: Page processing with $vals['start'] and $vals['end']
    $sql = "UPDATE `features` SET `{$vals['column']}`=?";
    $params = array("i", intval($vals['checked']));

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
        $params = array("ssi", $name, $label, $show_in_lists, $is_tracked);
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
