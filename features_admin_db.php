<?php
require_once __DIR__ . "/common.php";

/* ----------------------------------------------------------------------------
 * Notes
 * Some functions will return an alert message, but it could be a string or
 * array of strings depending on where the DB lookup result was requested.
 *
 * Alerts responding to Ajax requests should be a string message that will be
 * printed via JS.  There are no green/red/blue alert designations.
 *
 * Any alerts responding to the feature edit form (via POST) can be either a
 * "success" alert or "failure" alert.  This is sent via array with
 * ['msg'] being the alert message, and ['lvl'] being "success" (green alert),
 * "failure' (red alert), or "info" (blue alert).  Alternatively, the feature
 * edit page alert can be a string message (not in an array), and it will be
 * assumed to be a blue info alert.
 * ------------------------------------------------------------------------- */

/**
 * Retrieve feature details by feature ID.
 *
 * This query is used by the feature edit form to pre-fill input elements.
 *
 * @param int $id Feature ID to lookup
 * @return mixed either a feature's details in associative array or error message string on failure.
 */
function db_get_feature_details_by_id($id) {
    if (!ctype_digit($id)) {
        return false;
    }

    db_connect($db);
    $sql = "SELECT `name`, `label`, `show_in_lists`, `is_tracked` FROM `features` WHERE `id`=?";
    $params = array('i', $id);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();
    $query->bind_result($feature['name'], $feature['label'], $feature['show_in_lists'], $feature['is_tracked']);
    $query->fetch();

    if (!empty($db->error_list)) {
        $err_msg = htmlspecialchars($db->error);
        return "DB Error: {$err_msg}.";
    }

    $query->close();
    $db->close();

    // Make sure that $feature isn't an empty set.  Return error message when an empty set.
    $validate_feature = array_filter($feature, function($val) { return strlen($val) > 0; });
    return !empty($validate_feature) ? $feature : "DB returned empty set during feature lookup.";
} // END function db_get_feature_details_by_id()

/**
 * Change the status of either 'show_in_lists' or is_tracked' for all features.
 *
 * Used via POST/AJAX.
 *
 * @return string alert message to display.  Or empty string for no alert.
 */
function db_change_column() {
    clean_post();

    // validate
    switch (false) {
    case isset($_POST['val']) && preg_match("/^[01]$/", $_POST['val']):
    case isset($_POST['col']) && preg_match("/^show_in_lists$|^is_tracked$/", $_POST['col']):
    case isset($_POST['page']) && ctype_digit($_POST['page']):
    case isset($_POST['search']):
        // Return to main form.  No DB process.
        return "Validation failed for check/uncheck column";
    }

    // extract values from POST
    $col = $_POST['col'];
    $val = $_POST['val'];
    $page = intval($_POST['page']);
    $search_token = $_POST['search'];

    $rows_per_page = ROWS_PER_PAGE;  // defined in common.php
    $first_row = ($page-1) * $rows_per_page;  // starting row, zero based.

    if ($search_token !== "") {
        $regexp = "WHERE `name` REGEXP ?";
        $params = array("siii",  $search_token, $first_row, $rows_per_page, $val);
    } else {
        $regexp = "";
        $params = array("iii", $first_row, $rows_per_page, $val);
    }

    // DB query.
    $sql = <<<SQL
    UPDATE `features` f, (
        SELECT `id`
        FROM `features`
        {$regexp}
        ORDER BY `name` ASC
        LIMIT ?,?
    ) AS ftmp
    SET f.`{$col}`=?
    WHERE f.`id`=ftmp.`id`;
    SQL;

    db_connect($db);
    $query = $db->prepare($sql);
    if (is_bool($query)) {
        return $db->error;
    }
    $query->bind_param(...$params);
    $query->execute();

    // result from query.
    if (!empty($db->error_list)) {
        $response_msg = "DB Error: {$db->error}.";
    } else {
        $response_msg = "OK";
    }

    $query->close();
    $db->close();
    return $response_msg;
} // END function db_change_column()

/**
 * Change a single feature's 'show_in_lists' or 'is_tracked' column.
 *
 * Used via POST/AJAX.
 *
 * @return string response message to indicate success or error.
 */
function db_change_single() {
    //validate
    clean_post();
    switch(false) {
    case isset($_POST['id']) && ctype_digit($_POST['id']):
    case isset($_POST['col']) && preg_match("/^show_in_lists$|^is_tracked$/", $_POST['col']):
    case isset($_POST['state']) && preg_match("/^[01]$/", $_POST['state']):
        // Return to main form.  No DB process.
        return "Validation failed for checkbox toggle.";
    }

    $id = $_POST['id'];
    $new_state = $_POST['state'] === "0" ? 1 : 0;
    $col = $_POST['col'];

    $sql = "UPDATE `features` SET `{$col}`=? WHERE `id`=?";
    $params = array("ii", $new_state, $id);

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (!empty($db->error_list)) {
        $response_msg = "DB Error: {$db->error}.";
    } else {
        $response_msg = "OK";  // indicate success.
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
function db_edit_feature() {
    // Validate and set.
    clean_post();
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : null;
    $label = isset($_POST['label']) && !empty($_POST['label']) ? htmlspecialchars($_POST['label']) : null;
    $show_in_lists = isset($_POST['show_in_lists']) && ($_POST['show_in_lists'] === "on" || $_POST['show_in_lists'] === true) ? 1 : 0;
    $is_tracked = isset($_POST['is_tracked']) && ($_POST['is_tracked'] === "on" || $_POST['is_tracked'] === true) ? 1 : 0;

    // Further validate.  On error, stop and return error message.
    switch(false) {
    // $id must be all numbers or the word "new"
    case preg_match("/^\d+$|^new$/", $id):
        return array('msg'=>"Invalid feature ID \"{$id}\"", 'lvl'=>"failure");
    // $name cannot be blank
    case !empty($name):
        return array('msg'=>"Feature name cannot be blank", 'lvl'=>"failure");
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
        $response_msg = array('msg' => "{$name}{$label} successfully {$op}.", 'lvl' => "success");
    } else {
        $response_msg = array('msg' => "(${name}) DB Error: {$db->error}.", 'lvl' => "failure");
    }

    $query->close();
    $db->close();

    return $response_msg;
} // END function db_process()

/**
 * Delete feature from DB by feature's ID.
 *
 * @return array success/error message and page to return to.
 */
function db_delete_feature() {
    // validate
    clean_post();
    switch (false) {
    case isset($_POST['name']):
    case isset($_POST['id']) && ctype_digit($_POST['id']):
        // Do not process
        return array('msg' => "Request to delete a feature has failed validation.", 'lvl' => "failure");
    }

    $name = htmlspecialchars($_POST['name']);
    $id = $_POST['id'];

    db_connect($db);
    $sql = "DELETE FROM `features` WHERE `id`=?";
    $params = array("i", $id);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response_msg = array('msg' => "Successfully deleted {$name}", 'lvl' => "success");
    } else {
        $response_msg = array('msg' => "({$name}) DB Error: {$db->error}.", 'lvl' => "failure");
    }

    $query->close();
    $db->close();

    return $response_msg;
} //END function delete_feature()

/**
 * Get features table results from DB based on $page and $search_token.
 *
 * Used via POST/AJAX.  Page depends on constant ROWS_PER_PAGE, defined in common.php
 * Result set is a single page subset of the Features table.
 *
 * @param string $page Page number of result subset.
 * @param string $search_token Feature's `name` column search string for DB lookup.
 * @return array ['alert'] => alert to display, ['features'] => DB result set, ['last_page'] => final page number in DB result set.
 */
function db_get_page_data($page, $search_token="") {
    $rows_per_page = ROWS_PER_PAGE;  // defined in common.php
    $first_row = ($page-1) * $rows_per_page;  // starting row, zero based.
    $results = array();

    // Used in 'feature_list' query.  Constrain query by search token or select entire table.
    if ($search_token === "") {
        $where = "";
        $order_by = "ORDER BY `name` ASC";
        $params['feature_list'] = array("ii", $first_row, $rows_per_page);
        $params['feature_count'] = null;
    } else {
        // REGEXP is not utf8 safe, so we are using LIKE to pattern match.
        // Wildcard chars ('%' and '_') and '\' need to be escaped.
        $search_token = preg_replace("/(%|_|\\\)/u", '\\\$0', $search_token); // escaping chars.
        $search_token = "%{$search_token}%"; // adding wildcards to complete search pattern.
        $where = "WHERE `name` LIKE ? OR `label` LIKE ?";
        // ORDER BY `label` IS NULL ensures that NULL cols are sorted last.
        // This works because 0 (false) is lower than 1 (true).
        $order_by = "ORDER BY `label` IS NULL, `label` ASC, `name` ASC";
        $params['feature_list'] = array ("ssii", $search_token, $search_token, $first_row, $rows_per_page);
        $params['feature_count'] = array("ss", $search_token, $search_token);
    }

    // Query to get current page of features
    $sql['feature_list'] = <<<SQL
    SELECT * FROM `features`
    {$where}
    {$order_by}
    LIMIT ?, ?
    SQL;

    // Query for how many features are in the DB. (to determine how many pages there are)
    $sql['feature_count'] = "SELECT COUNT(*) FROM `features` {$where}";

    db_connect($db);
    // Run query to get features for current page
    $query = $db->prepare($sql['feature_list']);
    $query->bind_param(...$params['feature_list']);
    $query->execute();
    $query->bind_result($r_id, $r_name, $r_label, $r_lists, $r_tracked);
    while ($query->fetch()) {
        $results[] = array(
            'id' => $r_id,
            'name' => $r_name,
            'label' => $r_label,
            'show_in_lists' => $r_lists,
            'is_tracked' => $r_tracked
        );
    }
    $query->close();

    // Run query to get feature count and determine how many pages there are.
    $query = $db->prepare($sql['feature_count']);
    if (!is_null($params['feature_count'])) $query->bind_param(...$params['feature_count']);
    $query->execute();
    $query->bind_result($r_count);
    $query->fetch();

    $total_pages = intval(ceil($r_count / $rows_per_page));

    $alert = !empty($db->error_list) ? "DB Error: {$db->error}." : "";

    $query->close();
    $db->close();

    return array('alert' => $alert, 'features' => $results, 'last_page' => $total_pages);
} //END function db_get_page_data()
?>
