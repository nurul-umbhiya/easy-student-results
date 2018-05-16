jQuery(function($) {
    $('#result_table, .rps_promote_students_tbl').on('change', '#department_id', function() {
        change_batch_semester();
        change_student_course();
    });

    function change_batch_semester() {
        if( typeof edit_result != 'undefined' && edit_result == true )
            return;

        var id = $('#department_id').val();

        // Popupate semester dropdown box
        var i = typeof semesters != 'undefined' ? semesters[id] : null;
        //console.log(i);
        $('#semester_id option').remove();

        $('#semester_id')
            .append($("<option></option>")
            .attr("value",'')
            .text('Select Semester/Section'));

        if (i !== null) {
            $.each(i, function (key, value) {
                //populate semester dropdown box
                $('#semester_id')
                    .append($("<option></option>")
                        .attr("value", key)
                        .text(value));

            });
        }

        //populate batch dropdown box
        var j = typeof batches != 'undefined' ? batches[id] : null;
        //console.log(j);
        $('#batch_id option').remove();

        // Add option to the select box from list
        if (j !== null) {
            $.each(j, function (key, value) {
                $('#batch_id')
                    .append($("<option></option>")
                        .attr("value", key)
                        .text(value));
            });
        }
    }
    change_batch_semester();

    $('#result_table').on('change', '#batch_id', function() {
        //$('#batch_id').on('change', function () {
        change_student_course();
    });

    $('#result_table').on('change', '#semester_id', function() {
        //$('#semester_id').on('change', function () {
        change_student_course();
    });

    function change_student_course() {

        if( typeof edit_result != 'undefined' && edit_result == true )
            return;

        var department_id = $('#department_id').val();
        var batch_id = $('#batch_id').val();
        var semester_id = $('#semester_id').val();

        //call ajax
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : result.ajaxurl,
            data : {action: "load_student_course", department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: result.nonce},
            success: function(response) {
                if(response.type == "success") {
                    if ( response.data )
                        $('#student_cours_div').html( response.data );

                }
                else {

                    alert("Invalid Request.")
                }
            }
        })

    }

    function change_student_course2() {

        if( typeof edit_result != 'undefined' && edit_result == true )
            return;

        var department_id = $('#department_id').val();
        var batch_id = $('#batch_id').val();
        var semester_id = $('#semester_id').val();

        //call ajax
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : result.ajaxurl,
            data : {action: "load_student_course", department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: result.nonce},
            success: function(response) {
                if(response.type == "success") {
                    if ( response.student )
                        $('#student_table tbody').html( response.student );

                    if ( response.course )
                        $('#course_table tbody').html( response.course );
                }
                else {

                    alert("Invalid Request.")
                }
            }
        })

    }

    $('body').on('click', '.selectAll', function(){
        var th = $(this);

        if( th.prop('checked') ) {
            // something when checked
            th.parent().parent().parent().find('input[type="checkbox"]').prop('checked', true);
        } else {
            // something else when not
            th.parent().parent().parent().find('input[type="checkbox"]').prop('checked', false);
        }

    });
});