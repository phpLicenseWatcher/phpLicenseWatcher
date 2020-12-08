<?php

require_once __DIR__ . "/common.php";
require_once __DIR__ . "/html_table.php";

if (isset($_GET['id'])) {
    edit_form();
} else {
    main_form();
}

exit;

function main_form() {
    db_connect($db);
    $server_list = db_get_servers($db, array(), array(), "id");
    $db->close();

    $colors = array("lavender", "transparent");
    $num_colors = count($colors);

    $table = new html_table(array('class' => "table"));
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
        $color = $colors[$i % $num_colors];
        $table->add_row($row, array('style'=>"background-color: {$color};"));

        switch($server['status']) {
        case SERVER_UP:
            $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"server-up"));
            break;
        case SERVER_VENDOR_DOWN:
            $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"server-vendor-down"));
            break;
        case SERVER_DOWN:
            $table->update_cell($table->get_rows_count()-1, 3, array('class'=>"server-down"));
            break;
        default:
            $table->update_cell($table->get_rows_count()-1, 3, array(), "not polled");
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
        $server_details = array('name'=>"", 'label'=>"", 'is_active'=>true);
        break;
    default:
        main_form();
        return null;
    }

    // print view
    print_header();

    print <<<HTML
    <form action='servers_admin.php' method='get'>
        <div style='display: inline-block;'>
            <label for='name'>Port (<code>port@domain.tld</code>)</label><br>
            <input type='textbox' id='name'>
        </div><div style=' display: inline-block;'>
            <label for='label'>Label</label><br>
            <input type='textbox' id='label'>
        </div><div style='display: inline-block;'>
            <label for='is_active'>Is Active?</label><br>
            <input type='checkbox' id='is_active'>
        </div>
        <button type='submit'>Submit</button>
    </form>
    HTML;

    print_footer();
} // END function edit_form()

function server_details_by_getid() {
    db_connect($db);
    $server_details = db_get_servers($db, array("name", "label", "is_active"), array($_GET['id']));
    $db->close();
    if (empty($server_details)) {
        return false;
    }

    return array(
        'name'      => $server_details[0]['name'],
        'label'     => $server_details[0]['label'],
        'is_active' => $server_details[0]['is_active'] ? true : false
    );
} // END function server_details_by_getid()
?>