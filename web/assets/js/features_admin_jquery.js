// Retrieve control panel and feature table views
function refresh_body() {
    var page = sessionStorage.getItem('features_page');
    var search_token = sessionStorage.getItem('features_search_token');
    // Object.assign(post_data, {'page': page, 'search_token': search_token, 'refresh': 1});
    var result = $.ajax({
        context: this,
        method: "POST",
        data: {'page': page, 'search_token': search_token, 'refresh': 1},
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

// Run a search with token entered in search box
function run_search() {
    var search = $('#search_box').val();
    sessionStorage.setItem('features_search_token', search);
    sessionStorage.setItem('features_page', "1");
}

// End search view.  Return to full table view.
function cancel_search() {
    $('#search_box').val("")
    sessionStorage.setItem('features_search_token', "");
    sessionStorage.setItem('features_page', "1");
}

$(document).ready(function() {
    if (!sessionStorage.getItem('features_search_token')) {
        sessionStorage.setItem('features_search_token', "");
    }

    if (!sessionStorage.getItem('features_page')) {
        sessionStorage.setItem('features_page', "1");
    }

    refresh_body();

    // Individual checkbox control handler
    $('#features_admin_body').on('click', '.single_checkbox', function() {
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

                refresh_body();
            }
        });
    });

    // "Check/Uncheck All" control handler
    $('#features_admin_body').on('click', '.column_checkboxes', function() {
        var col = $(this).attr('name');
        var val = $(this).val();
        var page = sessionStorage.getItem('features_page');
        var search = sessionStorage.getItem('features_search_token');
        var result = $.ajax({
            context: this,
            method: "POST",
            data: {'toggle_column': "1", 'col': col, 'val': val, 'page': page, 'search': search},
            dataType: "text",
            async: true,
            success: function(response, result) {
                if (response !== "OK") {
                    alert(response);
                }

                refresh_body();
            }
        });
    });

    // Page control handler
    $('#features_admin_body').on('click', 'button[name="page"]', function() {
        var page = $(this).val()
        sessionStorage.setItem('features_page', page)
        refresh_body();
    });

    // Feature search control handler (hourglass button)
    $('#features_admin_body').on('click', '#search_button', function() {
        if (sessionStorage.getItem('features_search_token') === "") {
            run_search();
        } else {
            cancel_search();
        }

        refresh_body();
    });

    // [ENTER] key pressed after entering a search token will run the search
    $('#features_admin_body').on('keyup', '#search_box', function(e) {
        if (e.which === 13) {
            run_search();
            refresh_body();
        }
    });
});

// [ESC] key globally cancels any active search view.
$(document).keyup(function(e) {
    if (sessionStorage.getItem('features_search_token') !== "" && e.which === 27) {
        cancel_search();
        refresh_body();
    }
});
