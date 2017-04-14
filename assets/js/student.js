/**
 * Created by nurul on 10/4/15.
 */
jQuery(function($) {

    //validation
    $('input#publish').click(function() {

        var title = $('#title').val();
        var reg_no          = $('#reg_no').val();
        var roll_no         = $('#roll_no').val();
        var department_id   = $('#department_id').val();
        var batch_id        = $('#batch_id').val();

        var flag = false;

        if ( title == '' || title == null ) {
            alert(data.title);
            flag = true;
            $('#title').focus();
            return false;
        }

        if ( reg_no == '' || reg_no == null ) {
            alert(data.reg_no);
            flag = true;
            $('#reg_no').focus();
            return false;
        }

        if ( roll_no == '' || roll_no == null ) {
            alert(data.roll_no);
            flag = true;
            $('#roll_no').focus();
            return false;
        }

        if( department_id == '' || department_id == null ) {
            alert(data.department_id);
            flag = true;
            $('#department_id').focus();
            return false;
        }

        if( batch_id == '' || batch_id == null ) {
            alert(data.batch_id);
            flag = true;
            $('#batch_id').focus();
            return false;
        }

        if( flag ) {
            return false;
        } else {
            return true;
        }

        return false;
    });


});
/**
 * Created by nurul on 10/4/15.
 */
