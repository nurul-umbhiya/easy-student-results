jQuery(function($){

    $('#search_result_form').submit(function () {

        var exam_id         = $('#exam_id').val();
        var department_id   = $('#department_id').val();
        var batch_id        = $('#batch_id').val();
        var semester_id     = $('#semester_id').val();
        var flag = false;

        var semester_length = $('#semester_id > option').length;


        if( exam_id == '' || exam_id == null ) {
            var error_str = '<div class="alert alert-danger" role="alert">' + exam_error_text +'</div>';
            $('#rps_result_errors').html(error_str);
            flag = true;
            $('#department_id').focus();
            return false;
        }

        if( department_id == '' || department_id == null ) {
            var error_str = '<div class="alert alert-danger" role="alert">' + department_error_text +'</div>';
            $('#rps_result_errors').html(error_str);
            flag = true;
            $('#department_id').focus();
            return false;
        }

        if( batch_id == '' || batch_id == null ) {
            var error_str = '<div class="alert alert-danger" role="alert">' + batch_error_text +'</div>';
            $('#rps_result_errors').html(error_str);
            flag = true;
            $('#batch_id').focus();
            return false;
        }

        if( semester_length > 1 && ( semester_id =='' || semester_id == null ) ) {
            var error_str = '<div class="alert alert-danger" role="alert">' + semester_error_text +'</div>';
            $('#rps_result_errors').html(error_str);
            flag = true;
            $('#semester_id').focus();
            return false;
        }


        if( flag ) {
            return false;
        } else {
            return true;
        }

        return false;

    });

    $('#exam_id').on('change',function() {
        var id = $(this).val();

        if (id == '' || id == null) {
            return;
        }

        console.log(id);
        console.log(exam_list_data);
        console.log(exam_list_data[id]);

        // Popupate department dropdown box
        var i = exam_list_data[id]['department_ids'];
        console.log(i);
        $('#department_id option').remove();

        $('#department_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_department));

        var department_id = '', batch_id = '', semester_id = '';

        if(i !== null) {
            $.each(i, function(key, value) {
                //populate semester dropdown box

                if ( department_id == '' ) {
                    department_id = key;
                }

                $('#department_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }


        // Popupate batch dropdown box
        var j = exam_list_data[id][department_id]['batch_ids'];
        console.log(j);
        $('#batch_id option').remove();

        $('#batch_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_batch));


        if(j !== null) {
            $.each(j, function(key, value) {
                //populate semester dropdown box

                if ( batch_id == '' ) {
                    batch_id = key;
                }

                $('#batch_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }

        // Popupate semester dropdown box
        var k = exam_list_data[id][department_id]['semester_ids'];
        console.log(k);
        $('#semester_id option').remove();

        $('#semester_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_semester));


        if(k !== null) {
            $.each(k, function(key, value) {
                //populate semester dropdown box
                $('#semester_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }

    });

    $('#department_id').change(function() {

        var id = $('#exam_id').val();

        if (id == '' || id == null) {
            return;
        }

        var department_id = $('#department_id').val();

        if (department_id == '' || department_id == null) {
            return;
        }

        // Popupate batch dropdown box
        var j = exam_list_data[id][department_id]['batch_ids'];
        console.log(j);
        $('#batch_id option').remove();

        $('#batch_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_batch));


        if(j !== null) {
            $.each(j, function(key, value) {
                //populate semester dropdown box

                if ( batch_id == '' ) {
                    batch_id = key;
                }

                $('#batch_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }

        // Popupate semester dropdown box
        var k = exam_list_data[id][department_id]['semester_ids'];
        console.log(k);
        $('#semester_id option').remove();

        $('#semester_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_semester));


        if(k !== null) {
            $.each(k, function(key, value) {
                //populate semester dropdown box
                $('#semester_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }
    });


    $("#res_result_print").click(function() {
        var th = $(this);

        $("#res_result_print_data").print({
            //Use Global styles
            globalStyles : false,
            //Add link with attrbute media=print
            mediaPrint : false,
            //Custom stylesheet
            stylesheet : bs_url,
            //Print in a hidden iframe
            iframe : true,
            //Don't print this
            noPrintSelector : ".avoid-this",
            //Add this at top
            prepend : $('#result_prepend'),
            //Add this on bottom
            append : $('#result_append'),

            timeout: 250
        });
        return;
    });

});
