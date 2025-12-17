<?php

/** DB operation to either add or edit a form, based on $_POST['id'] */
function db_process() {
    $id = $_POST['submit_id'];
    $name = trim($_POST['name']);
    $label = $_POST['label'];
    $license_manager = $_POST['license_manager'];
    // checkboxes are not included in POST when unchecked.
    $is_active = array_key_exists('is_active', $_POST) && $_POST['is_active'] === "on" ? 1 : 0;
    $count_reserved = array_key_exists('count_reserved', $_POST) && $_POST['count_reserved'] === "on" ? 1 : 0;

    // Error check.  On error, stop and return error message.
    switch(false) {
    // $id must be all numbers or the word "new"
    case preg_match("/^\d+$|^new$/", $id):
        return array('msg' => "Invalid server ID \"{$id}\"", 'lvl' => "failure");
    case validate_server_name($name):
        return array('msg' => "Server name MUST be in form: <code>port@domain.tld</code>, <code>port@hostname</code>, or <code>port@ipv4</code>. Port is optional. You can also specify multiple servers with <code>port@domain1.tld,port@domain2.tld,port@domain3.tld</code>, etc.", 'lvl' => "failure");
    // $label cannot be blank
    case !empty($label):
        return array('msg' => "Server's label cannot be blank", 'lvl' => "failure");
    }
    // END error check

    if ($id === "new") {
        // Adding a new server
        $sql = "INSERT INTO `servers` (`name`, `label`, `is_active`, `lm_default_usage_reporting`, `license_manager`) VALUES (?, ?, ?, ?, ?)";
        $params = array("ssiis", $name, $label, $is_active, $count_reserved, $license_manager);
        $op = "added";
    } else {
        // Editing an existing server
        $sql = "UPDATE `servers` SET `name`=?, `label`=?, `is_active`=?, `lm_default_usage_reporting`=?, `license_manager`=? WHERE `ID`=?";
        $params = array("ssiisi", $name, $label, $is_active, $count_reserved, $license_manager, $id);
        $op = "updated";
    }

    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response_msg = array('msg' => "{$name} ({$label}) successfully {$op}.", 'lvl' => "success");
    } else {
        $response_msg = array('msg' => "(${name}) DB Error: {$db->error}.", 'lvl' => "failure");
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
function db_server_details_by_getid($id) {
    db_connect($db);
    $server_details = db_get_servers($db, array("name", "label", "is_active", "lm_default_usage_reporting", "license_manager"), array($id), "", false);
    $db->close();
    return !empty($server_details) ? $server_details[0] : false;
} // END function db_server_details_by_getid()

function db_delete_server() {
    // validate
    if (ctype_digit($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
    } else {
        return array('msg' => "Validation failed when attempting to remove a server from DB.", 'lvl' => "failure");
    }

    $sql = "DELETE FROM `servers` WHERE `id`=?";
    $params = array("i", intval($id));

    db_connect($db);
    $details = db_get_servers($db, array('name', 'label'), array($id), "", false)[0];
    $name = $details['name'];
    $label = $details['label'];
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();

    if (empty($db->error_list)) {
        $response = array('msg' => "Successfully deleted \"{$name}\" ({$label})", 'lvl' => "success");
    } else {
        $response = array('msg' => "\"${name}\" ({$label}), DB Error: \"{$db->error}\"", 'lvl' => "failure");
    }

    $query->close();
    $db->close();

    return $response;
} // END function db_delete_server()

/**
 * Retrieve server list and return json encoded.
 *
 * @return string json encoded server list
 */
function db_get_servers_json() {
    db_connect($db);
    $res = $db->query("SELECT `name`, `label`, `is_active`, `license_manager` FROM `servers` ORDER BY `label`;");
    $data = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    $db->close();
    return json_encode($data);
} // END Function db_get_servers_json()

function db_import_servers_json($json) {
    db_connect($db);
    $sql = "INSERT IGNORE INTO `servers` (`name`, `label`, `is_active`, `license_manager`) VALUES (?, ?, ?, ?);";
    $query = $db->prepare($sql);
    if ($query === false) {
        return array('msg' => "DB error: {$db->error}", 'lvl' => "failure");
    }

    $db->begin_transaction(0, "import");
    foreach($json as $row) {
        $query->bind_param("ssis", $row['name'], $row['label'], $row['is_active'], $row['license_manager']);
        $query->execute();
        if ($query === false) {
            $db->rollback(0, "import");
            return array('msg' => "DB error: {$db->error}", 'lvl' => "failure");
        }
    }

    $db->commit(0, "import");
    $query->close();
    $db->close();
    return array('msg' => "Import succeeded.", 'lvl' => "success");
} // END Function db_import_servers_json()

/**
 *  Sanity check on server $name.
 *
 *  Port is optional as Mathematica doesn't require a specified port number.
 *  First checks are by regular expression.  Port/IPv4 values are then checked
 *  for numerical range.
 *
 * @param string $name
 * @return bool TRUE when $name is valid, FALSE otherwise.
 */
function validate_server_name(string $name) : bool {
    $name_arr = preg_split ("/\,/", $name);
    foreach ($name_arr as $name) {
        // Regex checks are order of: (1) port@domain.tld  (2) port@hostname  (3) port@ipv4
        switch (true) {
        case preg_match("/^(?:(?<port>\d{1,5})@)?(?:(?!\-)[a-z0-9\-]+(?<!\-)\.)+[a-z\-]{2,}$/i", $name, $matches, PREG_UNMATCHED_AS_NULL) === 1:
        case preg_match("/^(?:(?<port>\d{1,5})@)?(?!\-)[a-z0-9\-]+(?<!\-)$/i", $name, $matches, PREG_UNMATCHED_AS_NULL) === 1:
        case preg_match("/^(?:(?<port>\d{1,5})@)?(?<octet1>\d{1,3})\.(?<octet2>\d{1,3})\.(?<octet3>\d{1,3})\.(?<octet4>\d{1,3})$/", $name, $matches, PREG_UNMATCHED_AS_NULL) === 1:
            // Port is optional since Mathematica doesn't specify a port.
            if (!is_null($matches['port']) && ((int) $matches['port'] < 1024 || (int) $matches['port'] > 65535)) {
                return false;
            }
            // Octets only exist in third regex check (for valid ipv4).
            // $octet array keys only exist when matching the third regex.
            foreach (array('octet1', 'octet2', 'octet3', 'octet4') as $octet) {
                if (array_key_exists($octet, $matches) && ((int) $matches[$octet] < 0 || (int) $matches[$octet] > 255)) {
                    return false;
                }
            }
            continue;
            // return true;
        default:
            // No regex matches mean $name is definitely invalid.
            return false;
        }
    }
    return true;
} // END function validate_server_name()
?>
