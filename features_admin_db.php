<?php
require_once __DIR__ . "/common.php";

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
} // END function feature_details_by_getid()

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
} // END function db_change_column()

/**
 * Change a single feature's 'show_in_lists' or 'is_tracked' column.
 *
 * @return string response message to indocate success or error.
 */
function db_change_single() {
    if (isset($_POST['id'])) $id = $_POST['id'];
    if (isset($_POST['col'])) $col = $_POST['col'];
    if (isset($_POST['state'])) $state = $_POST['state'];

    //validate
    switch(false) {
    case isset($id)    && ctype_digit($id):
    case isset($col)   && preg_match("/^show_in_lists$|^is_tracked$/", $col):
    case isset($state) && preg_match("/^[01]$/", $state):
        // Return to main form.  No DB process.
        return "Validation failed for checkbox toggle.";
    }

    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id`=?";
    $params = array("ii", intval($state), intval($id));

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = "DB Error: {$db->error}.";
    } else {
        $response_msg = "1";  // indicates success.
    }

    $query->close();
    $db->close();
    return $response_msg;
} // END function db_change_single()

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
 * Delete feature from DB by feature's ID.
 *
 * @return array success/error message and page to return to.
 */
function delete_feature() {

    // validate
    switch (false) {
    case ctype_digit($_POST['id']):
    case ctype_digit($_POST['page']):
        // Do not process
        return array('msg'=>"<p class='red-text'>&#10006; Request to delete a feature has failed validation.", 'page'=>1);
    }

    $id = $_POST['id'];
    $name = $_POST['name'];
    $page = $_POST['page'];

    db_connect($db);
    $sql = "DELETE FROM `features` WHERE `id`=?";
    $params = array("i", intval($id));
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response = "<p class='green-text'>&#10004; Successfully deleted ID {$id}: {$name}";
    } else {
        $response = "<p class='red-text'>&#10006; ID ${id}: ${name}, DB Error: {$db->error}.";
    }

    $query->close();
    $db->close();

    return array('msg'=>$response, 'page'=>intval($page));
} //END function delete_feature()
?>
