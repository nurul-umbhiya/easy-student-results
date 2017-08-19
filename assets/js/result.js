jQuery(function($) {
    $('#result_table, .rps_promote_students_tbl').on('change', '#department_id', function() {
    //$('#department_id').on('change', function () {
        if( edit_result == true )
            return;

        var id = $(this).val();

        // Popupate semester dropdown box
        var i = semesters[id];
        //console.log(i);
        $('#semester_id option').remove();


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
        var j = batches[id];
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

        change_student_course();


    });

    $('#result_table').on('change', '#batch_id', function() {
    //$('#batch_id').on('change', function () {
        change_student_course();
    });

    $('#result_table').on('change', '#semester_id', function() {
    //$('#semester_id').on('change', function () {
        change_student_course();
    });

    function change_student_course() {

        if( edit_result == true )
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

        if( edit_result == true )
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

    $('#marks_table, .marks_table').on('change', '.rps_marks', function () {
        var marks = $(this).parent().parent().parent().parent().find('.rps_marks');
        var total = 0.00;
        $.each( marks, function(index, value) {
            total += parseFloat($(this).val());
        });

        //$('#total_marks_obtained').val(total);
        $(this).parent().parent().parent().parent().find('.total_marks_obtained,#total_marks_obtained').val(total);
    });

    $('#marks_table, .marks_table').on('change', '.grades', function () {
        var grade = $(this).val();
        var grade_point = change_grade_point(grade);
        $(this).parent().parent().find('.rps_grade').val(grade_point);
    });

    function change_grade_point(grade) {
        //console.log(grades);
        var ret = "";
        if (grade_points) {
            $.each( grade_points, function(index, value) {
                //console.log(index + ' ' + value);
                if(index == grade) {
                    ret = value;
                    return;
                }
            });
        }
        return ret;
    }

    function change_all_grade_point() {
        var grade_class = $('.grades');
        $.each(grade_class, function(a,b) {
            var grade = $(this).val();
            var grade_point = change_grade_point(grade);
            //console.log(grade + ' ' + grade_point);
            $(this).parent().parent().find('.rps_grade').val(grade_point);
        });
    }

    change_all_grade_point();

});
