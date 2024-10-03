<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/html_table.php";
require_once __DIR__ . "/lmtools.php";
require_once __DIR__ . "/servers_admin_db.php";

switch(true) {
case isset($_POST['submit_id']):
    $msg = db_process();
    main_form($msg);
    break;
case isset($_POST['edit_id']):
    edit_form();
    break;
case isset($_POST['delete_id']):
    $msg = db_delete_server();
    main_form($msg);
    break;
case isset($_POST['export_servers']) && $_POST['export_servers'] === "1":
    $json = db_get_servers_json();
    ajax_send_data($json, "application/json");  // q.v. common.php
    break;
case isset($_FILES['server_import']):
    $json = validate_uploaded_json();
    if ($json === false) {
        $msg = array('msg' => "Invalid server JSON.", 'lvl' => "failure");
    } else {
        $msg = db_import_servers_json($json);
    }
    main_form($msg);
    break;
case isset($_POST['cancel']):
default:
    main_form();
}

exit;

/**
 * Display server list and controls to add or edit a server.
 *
 * @param string $response Print any error/success messages from a add or edit.
 */
function main_form($alert=null) {
    db_connect($db);
    $server_list = db_get_servers($db, array(), array(), "label", false);
    $db->close();

    $table = new html_table(array('class' => "table alt-rows-bgcolor"));
    $headers = array("Name", "Label", "Licensing", "Active", "LM Default Usage Reporting", "Status", "Server Version", "Last Updated");
    $table->add_row($headers, array(), "th");

    // Don't display "no servers polled" notice when there are no servers in DB.
    // Otherwise, assume all servers aren't polled until shown otherwise.
    $display_notice = count($server_list) > 0 ? true : false;
    foreach($server_list as $i => $server) {
        $row = array(
            $server['name'],
            $server['label'],
            ucwords($server['license_manager']),
            $server['is_active'] === "1" ? "True" : "False",
            $server['lm_default_usage_reporting'] === "1" ? "True" : "False",
            $server['status'],
            $server['version'],
            date_format(date_create($server['last_updated']), "m/d/Y h:ia"),
            "<button type='submit' form='server_list' name='edit_id' class='btn btn-link' value='{$server['id']}' aria-label='edit {$server['name']}'>EDIT</button>"
        );
        $table->add_row($row);

        $last_row = $table->get_rows_count() - 1;
        $table->update_cell($last_row, 0, null, null, "th");
        switch($server['status']) {
        case null:
            $table->update_cell($last_row, 5, array('class'=>"info"), "Not Polled");
            break;
        case SERVER_UP:
            // No table cell update.
            $display_notice = false;
            break;
        case SERVER_VENDOR_DOWN:
            $table->update_cell($last_row, 5, array('class'=>"warning"));
            break;
        case SERVER_DOWN:
        default:
            $table->update_cell($last_row, 5, array('class'=>"danger"));
            break;
        }
    }

    // Get alert HTML based on message/properties.
    switch(true) {
    case is_string($alert):
        $alert_html = get_alert_html($alert);
        break;
    case isset($alert['msg']) && isset($alert['lvl']):
        $alert_html = get_alert_html($alert['msg'], $alert['lvl']);
        break;
    case is_null($alert):
    default:
        $alert_html = "";
        break;
    }

    if ($display_notice) {
        $alert_html .= get_not_polled_notice();
    }

    // Control Panel
    $control_panel_html = <<<HTML
    <div id='control_panel'>
        <button type='submit' form='server_list' name='edit_id' class='btn servers-control-panel' value='new'>New Server</button>
        <button type='button' id='export' class='btn servers-control-panel'>Export Servers</button>
        <button type='button' id='import' class='btn servers-control-panel'>Import Servers</button>
        <form method="post" action="" enctype="multipart/form-data" id='upload-form' class='inline-block'>
            <input type='file' accept='application/json' id='upload' name='server_import' class='servers-control-panel'>
        </form>
    </div>

    HTML;

    // Print view.
    print_header();

    print <<<HTML
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="servers_admin_jquery.js"></script>
    <h1>Server Administration</h1>
    <p>You may edit an existing server's name, label, active status, or add a new server to the database.<br>
    Server names must be unique and in the form of <code>port@domain.tld</code>, <code>port@hostname</code>, or <code>port@ipv4</code>.  Port is optional, but must be unprivileged.  You can also specify multiple servers with <code>port@domain1.tld,port@domain2.tld,port@domain3.tld</code>, etc.
    {$alert_html}
    {$control_panel_html}
    <form id='server_list' action='servers_admin.php' method='POST'>
    {$table->get_html()}
    </form>
    HTML;

    print_footer();
} // END function main_form()

/** Add/Edit server form.  No DB operations. */
function edit_form() {
    $id = $_POST['edit_id'];

    // Validate adding a new server or editing an existing server.
    // Skip back to the main_form() if something is wrong.
    $err_msg = array('msg' => "Validation failed when requesting form to edit an existing server.", 'lvl' => "failure");
    switch(true) {
    case ctype_digit($id):
        $server_details = db_server_details_by_getid($id);
        $delete_button = "<button type='button' class='btn edit-form' id='delete-button'>Remove</button>";
        if ($server_details === false) {
            main_form($err_msg);
            return null;
        }
        break;
    case $id === "new":
        $server_details = array('name'=>"", 'label'=>"", 'is_active'=>"1");
        $delete_button = "";
        break;
    default:
        main_form($err_msg);
        return null;
    }

    // get supported license managers
    $lmtools = new lmtools();
    $lm_supported = $lmtools->list_all_available();

    // print view
    $is_active_checked = $server_details['is_active'] === "1" ? " CHECKED" : "";
    $count_reserved_checked = $server_details['lm_default_usage_reporting'] === "1" ? " CHECKED" : "";
    $server_select_box = build_select_box($lm_supported, array('name' => "license_manager", 'id' => "license_manager"),
        array_key_exists('license_manager', $server_details) ? $server_details['license_manager'] : "");
    print_header();

    print <<<HTML
    <h1>Server Details</h1>
    <form action='servers_admin.php' method='post' class='edit-form'>
        <div class='edit-form block'>
            <input type='hidden' id='server_id' value='{$id}'>
            <input type='hidden' id='server_name' value='{$server_details['name']}'>
            <input type='hidden' id='server_label' value='{$server_details['label']}'>
        </div><div class='edit-form block'>
            <label for='name'>Name (format: <code>port@domain</code>, <code>port@host</code>, <code>port@ipv4</code>, port optional.)</label><br>
            <input type='text' name='name' id='name' class='edit-form' value='{$server_details['name']}'>
        </div><div class='edit-form block'>
            <label for='label'>Label</label><br>
            <input type='text' name='label' id='label' class='edit-form' value='{$server_details['label']}'>
        </div><div class='edit-form block'>
            <label for='license_manager'>Server Type:</label>
            {$server_select_box}
        </div><div class='edit-form block'>
            <input type='checkbox' name='count_reserved' id='count_reserved' class='edit-form'{$count_reserved_checked}>
            <label for='count_reserved' id='count_reserved_label'>Default license manager usage reporting (flexlm only)</label>
        </div><div class='edit-form inline-block'>
            <input type='checkbox' name='is_active' id='is_active' class='edit-form'{$is_active_checked}>
            <label for='is_active'>Server is active</label>
        </div><div class='edit-form inline-block float-right'>
            <input type='hidden' id='delete-server'>
            <button type='submit' class='btn btn-cancel edit-form' name='cancel' value='1'>Cancel</button>
            <button type='submit' class='btn btn-primary edit-form' name='submit_id' value='{$id}'>Submit</button>
            {$delete_button}
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="servers_edit_jquery.js"></script>
    </form>
    HTML;

    print_footer();
} // END function edit_form()

function validate_uploaded_json() {
    $tmp_name = $_FILES['server_import']['tmp_name'];
    $type     = $_FILES['server_import']['type'];
    $error    = $_FILES['server_import']['error'];

    switch(false) {
    case isset($tmp_name) && is_uploaded_file($tmp_name):
    case isset($type)     && $type  === "application/json":
    case isset($error)    && $error === 0:
        return false;
    }

    $file = file_get_contents($tmp_name);
    if ($file === false) {
        return false;
    }

    $json = json_decode($file, true);
    if (is_null($json)) {
        return false;
    }

    foreach ($json as $row) {
        // validate_server_name() is defined in servers_admin_db.php
        switch (false) {
        case is_array($row):
        case array_key_exists('license_manager', $row):
        case array_key_exists('name', $row) && validate_server_name($row['name']):
        case array_key_exists('label', $row):
        case array_key_exists('is_active', $row) && preg_match("/^[01]$/", $row['is_active']) === 1:
            return false;
        }
    }

    return $json;
} // END Function validate_uploaded_file()

?>
