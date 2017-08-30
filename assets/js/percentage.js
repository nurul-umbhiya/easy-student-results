jQuery(function($){
    function calculate_percentage() {
        var table = $('.marks_table');
        var out_of_marks = 0;
        var scored_marks = 0;
        //var th = this;


        $.each(table, function() {
            var tr = $(this).find('tr');

            $.each(tr, function() {
                var total_marks = parseFloat( $(this).find('input.total_marks').val() );
                if ( !isNaN(total_marks) ) {
                    var marks_obtain = parseFloat( $(this).find('.rps_marks').val() );
                    out_of_marks += total_marks;
                    scored_marks += marks_obtain;

                    //calculate percentage
                    var percent = ( marks_obtain / total_marks ) * 100;
                    percent = parseFloat( Math.round( percent ) ).toFixed(2);
                    $(this).find('.percentage').first().val(percent);
                }
            });

            //overall percentage
            var num = (scored_marks / out_of_marks) * 100;
            num = parseFloat(Math.round(num * 100) / 100).toFixed(2);
            $(this).closest('table').next('table.results_table').first().find('.total_percentage').val(num);
        });
    }
    calculate_percentage();

    $('.marks_table').on('change', '.rps_marks', function () {

        var out_of_marks = 0;
        var scored_marks = 0;

        var tr = $(this).closest('tbody').find('tr');
        var th = $(this);

        $.each(tr, function() {
            var total_marks = parseFloat( $(this).find('input.total_marks').val() );
            if ( !isNaN(total_marks) ) {
                var marks_obtain = parseFloat( $(this).find('.rps_marks').val() );
                out_of_marks += total_marks;
                scored_marks += marks_obtain;

                //calculate percentage
                var percent = ( marks_obtain / total_marks ) * 100;
                percent = parseFloat( Math.round( percent ) ).toFixed(2);
                $(this).find('.percentage').first().val(percent);
            }
        });

        var num = (scored_marks / out_of_marks) * 100;
        num = parseFloat(Math.round(num * 100) / 100).toFixed(2);
        //$(this).closest('table').next('table.results_table').first().find('.total_percentage').val(num);
        $(this).closest('table').next('table.results_table').find('.total_percentage').first().val(num);


    });
});