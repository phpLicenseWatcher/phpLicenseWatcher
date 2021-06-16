<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/features_admin_db.php";
require_once __DIR__ . "/features_admin_func.php";

// Process paths
switch (true) {
case isset($_POST['refresh']) && $_POST['refresh'] === "1":
    $response = func_get_page();
    ajax_send_data($response); // q.v. common.php
    break;

case isset($_POST['toggle_column']) && $_POST['toggle_column'] === "1":
    $response = db_change_column();
    ajax_send_data($response); // q.v. common.php
    break;

case isset($_POST['toggle_checkbox']) && $_POST['toggle_checkbox'] === "1":
    $response = db_change_single();
    ajax_send_data($response); // q.v. common.php
    break;

case isset($_POST['edit-feature']):
    $response = edit_form();
    if (is_string($response)) {
        main_form($response);
    }
    break;

case isset($_POST['post-edit-feature']) && $_POST['post-edit-feature'] === "1":
    $msg = db_edit_feature();
    main_form($msg);
    break;

case isset($_POST['delete-feature']) && $_POST['delete-feature'] === "1":
    $msg = db_delete_feature();
    main_form($msg);
    break;

case isset($_POST['cancel-edit-feature']) && $_POST['cancel-edit-feature'] === "1":
default:
    main_form();
    break;
}

exit;

/**
 * Send initial page HTML to view.
 *
 * Control panel and features table are controlled by Jquery/Ajax.
 *
 * @param mixed $alert Optional alert message to display.
 */
function main_form($alert=null) {
    // Format any alerts for display.
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

    // Print initial view.  Ajax data is added to DOM at #features_admin_body.
    print_header();

    print <<<HTML
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="features_admin_jquery.js"></script>
    <h1>Features Administration</h1>
    <p>You may edit an existing feature's name, label, boolean statuses, or add a new feature to the database.
    {$alert_html}
    <div id='features_admin_body'></div>
    HTML;

    print_footer();
} // END function main_form()

/** Add/Edit server form.  No DB operations. */
function edit_form() {
    $id = $_POST['edit-feature'];

    // Determine if adding a new server or editing an existing server.
    // Cancel (return null) if something is wrong.
    switch(true) {
    case ctype_digit($id):
        $feature_details = db_get_feature_details_by_id($id);
        // If is_string(), $feature_details contains an error message.
        if (is_string($feature_details)) {
            return $feature_details;
        }
        // Provide delete button and handler script.
        $delete_button = "<button type='button' class='btn edit-form' id='delete-button'>Remove</button>";
        $delete_button_handler = <<<JS
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
        $('#delete-button').click(function() {
            var name = $('#name').val();
            if (confirm("Confirm removal for \\"" + name + "\\"\\n*** THIS WILL REMOVE ALL USAGE HISTORY\\n*** THIS CANNOT BE UNDONE")) {
                $('input[name="delete-feature"]').val("1");
                $('form').submit();
            }
        });
        </script>
        JS;
        break;
    case $id === "new":
        $feature_details = array(
            'name' => "",
            'label' => "",
            'show_in_lists' => 1,
            'is_tracked' => 1
        );
        // No delete button and handler script.
        $delete_button = "";
        $delete_button_handler = "";
        break;
    default:
        // Silently return to main view.
        return "";
    }

    // print view
    $is_checked['show_in_lists'] = $feature_details['show_in_lists'] === 1 ? " CHECKED" : "";
    $is_checked['is_tracked'] = $feature_details['is_tracked'] === 1 ? " CHECKED" : "";

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
            <input type='hidden' name='delete-feature' value='0'>
        </div><div class='edit-form inline-block float-right'>
            <button type='submit' class='btn btn-cancel' name='cancel-edit-feature' value='1'>Cancel</button>
            <button type='submit' class='btn btn-primary edit-form' name='post-edit-feature' value='1'>Submit</button>
            {$delete_button}
        </div>
        {$delete_button_handler}
    </form>
    HTML;

    print_footer();
    return null;
} // END function edit_form()

?>
