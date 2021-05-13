$(document).ready(function() {
    $('#control_panel').on('click', '#export', function() {
        var result = $.ajax({
            context: this,
            method: "POST",
            data: {'export_servers': "1"},
            dataType: "json",
            async: true,
            success: function(json, result) {
                var blob = new Blob([JSON.stringify(json, null, 4)], {type: "application/json"});
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(blob);
                a.href = url;
                a.download = 'phplw_servers.json';
                document.body.append(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            }
        });
    });

    $('#control_panel').on('click', '#import', function() {
        $('#upload').click();
    });

    $('#control_panel').on('input', '#upload', function() {
        $('#upload-form').submit();
    });
});
