<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/features_admin_db.php";
require_once __DIR__ . "/features_admin_func.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch (true) {
    case isset($_POST['refresh']) && $_POST['refresh'] === "1":
        $res = func_get_page();
        file_put_contents('/home/vagrant/log.out', print_r($res, true));
        header("Content-Type: plain/text");
        print $res;
        break;

    case isset($_POST['toggle_column']) && $_POST['toggle_column'] === "1":
        $res = db_change_column();
        header("Content-Type: plain/text");
        print $res;
        break;

    case isset($_POST['toggle_checkbox']) && $_POST['toggle_checkbox'] === "1":
        $res = db_change_single();
        header("Content-Type: plain/text");
        print $res;
        break;

    case isset($_POST['edit_id']):
        edit_form();
        break;

    case isset($_POST['post_form']):
        $res = db_process();
        main_form($res['msg'], $res['page']);
        break;

    case isset($_POST['delete_feature']):
        $res = db_delete_feature();
        main_form($res['msg'], $res['page']);
        break;

    case isset($_POST['cancel_form']):
        break;

    default:
        $page = 1;
        $res = db_get_page_data($page);
        main_form($res['features'], $page, $res['total_pages'], $res['response']);
    }
} else {
    main_form();
}

exit;

/**
 * Send initial page HTML to view.
 */
function main_form() {
    // Most of this page is controlled by Jquery and Ajax.

    // Print initial view.
    print_header();

    print <<<HTML
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="features_admin_jquery.js"></script>
    <h1>Features Administration</h1>
    <p>You may edit an existing feature's name, label, boolean statuses, or add a new feature to the database.
    <div id='features_admin_body'></div>
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
        $feature_details = db_get_feature_details_by_id($id);
        // If is_string(), $feature details contains an error message.
        if (is_string($feature_details)) {
            main_form($feature_details, $page);
            return null;
        }
        break;
    case $id === "new":
        $feature_details = array(
            'name' => "",
            'label' => "",
            'show_in_lists' => 1,
            'is_tracked' => 1
        );
        break;
    default:
        main_form();
        return null;
    }

    // print view
    $is_checked['show_in_lists'] = $feature_details['show_in_lists'] === 1 ? " CHECKED" : "";
    $is_checked['is_tracked'] = $feature_details['is_tracked'] === 1 ? " CHECKED" : "";
    $delete_button = $id === 'new' ? "" : "<button type='button' class='btn edit-form' id='delete-button'>Remove</button>";
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
            <input type='hidden' name='page' value='{$page}'>
            <input type='hidden' id='delete-feature'>
        </div><div class='edit-form inline-block float-right'>
            <button type='submit' class='btn btn-cancel' name='cancel_form' value='1'>Cancel</button>
            <button type='submit' class='btn btn-primary edit-form' name='post_form' value='1'>Submit</button>
            {$delete_button}
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
        $('#delete-button').click(function() {
            var name = $('#name').val();
            if (confirm("Confirm removal for \\"" + name + "\\"\\n*** THIS WILL REMOVE ALL USAGE HISTORY\\n*** THIS CANNOT BE UNDONE")) {
                $('#delete-feature').attr('name', "delete_feature");
                $('#delete-feature').attr('value', "1");
                $('form').submit();
            }
        });
        </script>
    </form>
    HTML;

    print_footer();
} // END function edit_form()
?>
