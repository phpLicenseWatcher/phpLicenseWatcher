<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/features_admin_db.php";
require_once __DIR__ . "/features_admin_func.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    switch (true) {
    case isset($_POST['edit_id']):
        edit_form();
        break;
    case isset($_POST['search']):
        trim_post();
        print_var(db_get_page(1, $_POST['search-string'])); die;
        break;
    case isset($_POST['change_col']):
        $res = db_change_column();
        $page = strval(ceil($res['id']/ROWS_PER_PAGE));
        main_form($res['msg'], $page);
        break;
    case isset($_POST['toggle_checkbox']):
        $res = db_change_single();
        header("Content-Type: plain/text");
        print $res;
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
    case isset($_POST['page']):
        $page = ctype_digit($_POST['page']) ? intval($_POST['page']) : 1;
        main_form("", $page);
        break;
    default:
        $page = 1;
        $res = db_get_page_data($page);
        main_form($res['features'], $page, $res['total_pages'], $res['response']);
    }
} else {
    $page = 1;
    $res = db_get_page_data($page);
    main_form($res['features'], $page, $res['total_pages'], $res['response']);
}

exit;

/**
 * Display server list and controls to add or edit a server.
 */
function main_form($feature_list, $current_page, $last_page, $response=null) {

    // $page validation.
    // correct $page when validation case proves FALSE.
    switch(false) {
    case $current_page >= 1:
        $current_page = 1;
        break;
    case $current_page <= $last_page:
        $current_page = $last_page;
        break;
    }

    $page_controls = func_get_controlpanel_html($last_page);
    $features_table_html = func_get_features_table_html($feature_list);

    // Print view.
    print_header();

    print <<<HTML
    <h1>Features Administration</h1>
    <p>You may edit an existing feature's name, label, boolean statuses, or add a new feature to the database.
    {$response}
    {$page_controls['top']}
    {$features_table_html}
    {$page_controls['bottom']}
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script>
        $('.chkbox').click(function() {
            var id = $(this).attr('id');
            var vals = id.split("-");
            vals.push($(this).val());
            var result = $.ajax({
                context: this,
                method: "POST",
                data: {'toggle_checkbox': "1", 'col': vals[0], 'id': vals[1], 'state': vals[2]},
                dataType: "text",
                async: true,
                success: function(response, result) {
                    // Response will be "1" on success or an error message on failure.
                    if (result === "success" && response === "1") {
                        var icon = "&#" + $(this).html().charCodeAt(0) + ";";
                        $(this).val(icon === "{$checked_checkbox}" ? "1" : "0");
                        $(this).html(icon === "{$checked_checkbox}" ? "{$empty_checkbox}" : "{$checked_checkbox}");
                    } else {
                        alert(response);
                    }
                }
            });
        });
    </script>

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

function build_table($data) {

}
?>
