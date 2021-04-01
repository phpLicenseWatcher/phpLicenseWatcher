const SEARCH_ICON = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>';
const CANCEL_SEARCH = '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M15.5,14h-0.79l-0.28-0.27C15.41,12.59,16,11.11,16,9.5C16,5.91,13.09,3,9.5,3C6.08,3,3.28,5.64,3.03,9h2.02 C5.3,6.75,7.18,5,9.5,5C11.99,5,14,7.01,14,9.5S11.99,14,9.5,14c-0.17,0-0.33-0.03-0.5-0.05v2.02C9.17,15.99,9.33,16,9.5,16 c1.61,0,3.09-0.59,4.23-1.57L14,14.71v0.79l5,4.99L20.49,19L15.5,14z"/><polygon points="6.47,10.82 4,13.29 1.53,10.82 0.82,11.53 3.29,14 0.82,16.47 1.53,17.18 4,14.71 6.47,17.18 7.18,16.47 4.71,14 7.18,11.53"/></g></g></svg>';
const EMPTY_CHECKBOX = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#337ab7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>';
const CHECKED_CHECKBOX = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#337ab7"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM17.99 9l-1.41-1.42-6.59 6.59-2.58-2.57-1.42 1.41 4 3.99z"/></svg>';
const PREVIOUS_PAGE = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M15.61 7.41L14.2 6l-6 6 6 6 1.41-1.41L11.03 12l4.58-4.59z"/></svg>';
const NEXT_PAGE = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 0 24 24" width="16px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M10.02 6L8.61 7.41 13.19 12l-4.58 4.59L10.02 18l6-6-6-6z"/></svg>';

function refresh_body(post_data) {
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

    $('#features_admin_body').on('click', '.chkbox', function() {
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
                if (response !== "OK") {
                    alert(response);
                }

                refresh_body({});
            }
        });
    });

    $('#features_admin_body').on('click', '.column_checkboxes', function() {
        var col = $(this).attr('name');
        var val = $(this).val();
        var page = sessionStorage.getItem('features_page');
        var result = $.ajax({
            context: this,
            method: "POST",
            data: {'toggle_column': "1", 'col': col, 'val': val, 'page': page},
            dataType: "text",
            async: true,
            success: function(response, result) {
                if (response !== "OK") {
                    alert(response);
                }

                refresh_body({});
            }
        });
    });
});
