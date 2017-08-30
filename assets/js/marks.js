jQuery(function($) {
    $('.marks_table').on('change', '.rps_marks', function () {
        var marks = $(this).parent().parent().parent().find('.rps_marks');
        var total = 0.00;
        $.each( marks, function(index, value) {
            total += parseFloat($(this).val());
        });

        $(this).closest('table').next('table.results_table').find('.total_marks_obtained').first().val(total);
    });

    $('.marks_table').on('change', '.grades', function () {
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