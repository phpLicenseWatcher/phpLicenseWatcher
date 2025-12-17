let count_reserved_checked = $('#count_reserved').prop('checked');

function crt_checkbox() {
    if ($('#license_manager').val() != "flexlm") {
        $('#count_reserved').prop('checked', true);
        $('#count_reserved').prop('disabled', true);
        $('#count_reserved_label').css('color', "#767676");
    } else {
        $('#count_reserved').prop('checked', count_reserved_checked);
        $('#count_reserved').prop('disabled', false);
        $('#count_reserved_label').css('color', "inherit");
    }
}

$('document').ready(crt_checkbox);

$('#license_manager').change(crt_checkbox);

$('#count_reserved').change(function() {
    count_reserved_checked = $('#count_reserved').prop('checked');
});

$('#delete-button').click(function() {
    let server_name = $('#server_name').val();
    let server_label = $('#server_label').val();
    let server_id = $('#server_id').val();
    let msg = "Confirm removal for " + server_name + " (" + server_label + ")\n";
    msg += "*** THIS WILL REMOVE USAGE HISTORY FOR EVERY FEATURE\n";
    msg += "*** THIS CANNOT BE UNDONE";
    if (confirm(msg)) {
        $('#delete-server').attr('name', "delete_id");
        $('#delete-server').attr('value', server_id);
        $('form').submit();
    }
});
