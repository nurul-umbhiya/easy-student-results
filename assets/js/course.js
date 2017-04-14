/**
 * Created by nurul on 10/4/15.
 */
jQuery(function($) {

   //validation
   $('input#publish').click(function() {
       var title = $('#title').val();
       var course_code     = $('#course_code').val();
       var department_id   = $('#department_id').val();

       var flag = false;

       if ( title == '' ) {
           alert(data.title);
           flag = true;
           $('#title').focus();
           return false;
       }

       if ( course_code == '' ) {
           alert(data.course_code);
           flag = true;
           $('#course_code').focus();
           return false;
       }

       if( department_id == '' ) {
           alert(data.department_id);
           flag = true;
           $('#department_id').focus();
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
