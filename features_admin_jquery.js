const SEARCH_ICON = "&#128269;";
const CANCEL_SEARCH = "&#10060;";
const EMPTY_CHECKBOX = "&#9744;"
const CHECKED_CHECKBOX = "&#9745;";
const PREVIOUS_PAGE = "&#9204;";
const NEXT_PAGE = "&#9205;";

function refresh_body(post_data) {
    console.log("foobarbaz");
    var page = sessionStorage.getItem('features_page');
    var search_token = sessionStorage.getItem('features_search_token');
    Object.assign(post_data, {'page': page, 'search_token': search_token, 'refresh': 1});
    var result = $.ajax({
        context: this,
        method: "POST",
        data: post_data,
        dataType: "text",
        async: true,
        success: function(response, result) {
            // On success, response will be an updated features table as HTML.
            // On failure, response may be an error message.
            if (result === "success") {
                console.log("1");
                console.log(response);
                $('#features_admin_body').html(response);
            } else {
                alert(response);
            }
        }
    });
}

$(document).ready(function() {
    if (!sessionStorage.getItem('features_search_token')) {
        sessionStorage.setItem('features_search_token', "");
    }

    if (!sessionStorage.getItem('features_page')) {
        sessionStorage.setItem('features_page', 1);
    }

    refresh_body({});

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

    $('.column_checkboxes').click(function() {
        var name = $(this).attr('name');
        var val = $(this).val();
        refresh_body({'change_col': name, 'val': val});
    });
});
