jQuery(function($) {
    $("#res_result_print").click(function () {
        var th = $(this);

        $("#res_result_print_data").print({
            //Use Global styles
            globalStyles: false,
            //Add link with attrbute media=print
            mediaPrint: false,
            //Custom stylesheet
            stylesheet: bs_url,
            //Print in a hidden iframe
            iframe: true,
            //Don't print this
            noPrintSelector: ".avoid-this",
            //Add this at top
            prepend: $('#result_prepend'),
            //Add this on bottom
            append: $('#result_append'),

            timeout: 250
        });
        return;
    });
});