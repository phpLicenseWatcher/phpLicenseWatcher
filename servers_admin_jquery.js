$(document).ready(function() {
    $('#control_panel').on('click', '#export', function() {
        var result = $.ajax({
            context: this,
            method: "POST",
            data: {'export_servers': "1"},
            dataType: "text",
            async: true,
            success: function(json, result) {
                console.log(json);
                // var data = new Blob(json, {type: "plain/text"});
                // var a = document.createElement('a');
                // var url = window.URL.createObjectURL(data);
                // a.href = url;
                // a.download = 'phplw_servers.json';
                // document.body.append(a);
                // a.click();
                // a.remove();
                // window.URL.revokeObjectURL(url);
            }
        });
    });

    $('#control_panel').on('click', '#import', function() {

    });
});
