jQuery(function($) {

    $('#search_students_form').submit(function (){

        var department_id   = $('#department_id').val();
        var batch_id        = $('#batch_id').val();
        var semester_id     = $('#semester_id').val();
        var flag = false;

        var semester_length = $('#semester_id > option').length;

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

    $('#department_id').on('change',function() {
        var id = $(this).val();

        if( id == '' || id == null ) {
            return;
        }

        // Popupate semester dropdown box
        var i = semesters[id];
        //console.log(i);
        $('#semester_id option').remove();

        $('#semester_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_semester));


        if(i !== null){
            $.each(i, function(key, value) {
                //populate semester dropdown box
                $('#semester_id')
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));

            });
        }

        //populate batch dropdown box
        var j = batches[id];
        //console.log(j);
        $('#batch_id option').remove();

        $('#batch_id')
            .append($("<option></option>")
                .attr("value",'')
                .text(select_batch));

        // Add option to the select box from list
        if ( j !== null ) {
            $.each( j, function( key, value ) {
                $( '#batch_id' )
                    .append($("<option></option>")
                        .attr("value",key)
                        .text(value));
            });
        }

    });
});
