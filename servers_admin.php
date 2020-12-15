<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $label = $_POST['label'];
    $is_active = $_POST['is_active'] === "on" ? 1 : 0;
    if ($id === "new") {
        $sql = "INSERT INTO `servers` (`name`, `label`, `is_active`) VALUES (?, ?, ?)";
        $params = array("ssi", $name, $label, $is_active);
    } else {
        $sql = "UPDATE `servers` SET `name`=?, `label`=?, `is_active`=? WHERE `ID`=?";
        $params = array("ssii", $name, $label, $is_active, $id);
    }
    db_connect($db);
    $query = $db->prepare($sql);
    $query->bind_param(...$params);
    $query->execute();
    $query->close();
    $db->close();
}

if (isset($_GET['id'])) {
    edit_form();
} else {
    main_form();
}

exit;

function main_form() {
    db_connect($db);
    $server_list = db_get_servers($db, array(), array(), "id", false);
    $db->close();

    $table = new html_table(array('class' => "table alt-rows-bgcolor"));
    $headers = array("ID", "Name", "Label", "Is Active", "Status", "LMGRD Version", "Last Updated", "Edit");
    $table->add_row($headers, array(), "th");

    foreach($server_list as $i => $server) {
        $row = array(
            $server['id'],
            $server['name'],
            $server['label'],
            $server['is_active'] ? "True" : "False",
            $server['status'],
            $server['lmgrd_version'],
            $server['last_updated'],
            "<button type='submit' form='server_list' name='id' value='{$server['id']}'>Edit</button>"
        );

        $table->add_row($row);
        switch($server['status']) {
        case null:
            $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"info"), "Not Polled");
            break;
        case SERVER_UP:
            // Do nothing.
            break;
        case SERVER_VENDOR_DOWN:
            $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"warning"));
            break;
        case SERVER_DOWN:
        default:
            $table->update_cell($table->get_rows_count()-1, 4, array('class'=>"danger"));
            break;
        }
    }

    // Print view.
    print_header();

    print <<<HTML
    <h1>Server Administration</h1>
    <p>You may edit an existing server's name, label, active status, or add a new server to the database.<br>
    Server names must be unique and in the form of "<code>port@domain.tld</code>".
    <form id='server_list' action='servers_admin.php' method='get'>
    {$table->get_html()}
    <p><button type='submit' form='server_list' name='id' value='new'>New Server</button>
    </form>
    HTML;

    print_footer();
} // END function main_form()

function edit_form() {
    switch(true) {
    case ctype_digit($_GET['id']):
        $server_details = server_details_by_getid();
        if ($server_details === false) {
            main_form();
            return null;
        }
        break;
    case $_GET['id'] === "new":
        $server_details = array('name'=>"", 'label'=>"", 'is_active'=>'1');
        break;
    default:
        main_form();
        return null;
    }

    // print view
    $is_checked = $server_details['is_active'] === '1' ? " CHECKED" : "";
    print_header();

    print <<<HTML
    <h1>Server Details</h1>
    <form action='servers_admin.php' method='post' class='edit-form'>
        <div class='edit-form'>
            <label for='name'>Name (format: <code>port@domain.tld</code>)</label><br>
            <input type='text' name='name' id='name' class='edit-form' value='{$server_details['name']}'>
        </div><div class='edit-form'>
            <label for='label'>Label</label><br>
            <input type='text' name='label' id='label' class='edit-form' value='{$server_details['label']}'>
        </div><div class='edit-form'>
            <label for='is_active'>Is Active?</label>
            <input type='checkbox' name='is_active' id='is_active' class='edit-form'{$is_checked}>
            <input type='hidden' name='id' value='{$_GET['id']}'>
            <button type='submit' class='edit-form'>Submit</button>
        </div>
    </form>
    HTML;

    print_footer();
} // END function edit_form()

function server_details_by_getid() {
    db_connect($db);
    $server_details = db_get_servers($db, array("name", "label", "is_active"), array($_GET['id']), "", false);
    $db->close();
    return !empty($server_details) ? $server_details[0] : false;
} // END function server_details_by_getid()
?>
