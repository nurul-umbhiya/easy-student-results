<?php

if ( !defined( 'WPINC' ) ) {
    die();
}

class RPS_Helper_Ajax {

    private static $instance;
    protected $pagehook, $error, $messages, $TD, $options;
    protected $helper, $page, $dbs, $student, $result, $course;

    public static function getInstance() {
        if ( self::$instance == NULL ) {
            self::$instance = new self;

            self::$instance->helper = new RPS_Helper_Function();
            self::$instance->dbs = new RPS_Helper_DBS();
            self::$instance->TD =  RPS_Result_Management::TD;

            self::$instance->student = new RPS_Helper_Student();
            self::$instance->result = new RPS_Helper_Result();
            self::$instance->course = new RPS_Helper_Course();

            self::$instance->actions();
        }
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        //add_action( 'wp_ajax_nopriv_', array($this,'') );
        //add_action( 'wp_ajax_', array($this,'') );

        add_action( 'wp_ajax_load_student_course', array($this,'load_student_course') );
    }

    public function load_student_course() {
        $result['type'] = '';
        //check nonce
        if( check_ajax_referer( 'rps_result_load_data', 'secure',false ) ) {
            $department_id = isset( $_POST['department_id'] ) ?  intval($_POST['department_id']) : 0;
            $batch_id = isset( $_POST['batch_id'] ) ?  intval($_POST['batch_id']) : 0;
            $semester_id = isset( $_POST['semester_id'] ) ?  intval($_POST['semester_id']) : 0;

            if ( $department_id && $batch_id && $semester_id ) {
                $student_list = $this->student->getStudentDetails( $department_id, $batch_id, $semester_id );
                $course_list    = $this->course->getAllCourses( $department_id, $semester_id );
            } elseif ( $department_id && $batch_id ) {
                $student_list = $this->student->getStudentDetails( $department_id, $batch_id );
                $course_list    = $this->course->getAllCourses( $department_id );
            }


            ob_start();
            if( ! is_wp_error( $student_list ) && ! empty( $student_list ) ) :
                foreach ( $student_list as $student_id => $student_data ) {
                    ?>
                    <div class="panel panel-default col-md-6">
                        <!-- Table -->
                        <table class="table">
                            <tbody>
                            <tr>
                                <td><input type="checkbox" class="selectAll" /><input type="hidden" name="student_ids[]" value="<?php echo $student_id; ?>"></td>
                                <td><?php echo "<strong>"; _e('Student Name', $this->TD); echo ':</strong> ' . $student_data['name']; ?></td>
                                <td><?php echo '<strong>'; _e('Roll No', $this->TD); echo ':</strong> '. $student_data['roll_no']; ?></td>
                            </tr>

                            <?php if( ! is_wp_error( $course_list ) && ! empty( $course_list ) ) { ?>
                            <tr><td colspan="3"><?php _e('Select Course', $this->TD); ?></td></tr>
                            <?php foreach ( $course_list as $course_id => $course_data ) { ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="course_id[student_<?php echo $student_id; ?>][]" value="<?php echo $course_id; ?>" />
                                    </td>
                                    <td><?php echo __( '<strong>Course Code:</strong> ', $this->TD ) . $course_data['course_code']; ?></td>
                                    <td><?php echo __( '<strong>Course Name:</strong> ', $this->TD)  . $course_data['name'];  ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>

                        <?php	} else { ?>
                            <tr><td colspan="3"><?php _e('No course found.', $this->TD); ?></td></tr>
                        <?php } ?>

                    </div>
                    <?php
                }

            else: ?>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <?php _e('No Students found.', $this->TD); ?>
                    </div>
                </div>
            <?php

            endif;
            $data = ob_get_clean();



            $result['data'] = $data;
            $result['type'] = 'success';

        }
        else {
            $result['type'] = "Nonce Error";
        }

        header( 'Content-Type: application/json; charset=utf-8' );
        //echo result
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        }
        else {
            header("Location: ".$_SERVER["HTTP_REFERER"]);
        }

        die();
    }

    public function load_student_course2() {
        $result['type'] = '';
        //check nonce
        if( check_ajax_referer( 'rps_result_load_data', 'secure',false ) ) {
            $department_id = isset( $_POST['department_id'] ) ?  intval($_POST['department_id']) : 0;
            $batch_id = isset( $_POST['batch_id'] ) ?  intval($_POST['batch_id']) : 0;
            $semester_id = isset( $_POST['semester_id'] ) ?  intval($_POST['semester_id']) : 0;

            if ( $department_id && $batch_id && $semester_id ) {
                $student_list = $this->student->getStudentDetails( $department_id, $batch_id, $semester_id );
                $course_list    = $this->course->getAllCourses( $department_id, $semester_id );
            } elseif ( $department_id && $batch_id ) {
                $student_list = $this->student->getStudentDetails( $department_id, $batch_id );
                $course_list    = $this->course->getAllCourses( $department_id );
            }


            ob_start();
                if( ! is_wp_error( $student_list ) && ! empty( $student_list ) ) :

                    foreach ( $student_list as $student_id => $student_data ) {

                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="student_id[]" value="<?php echo $student_id; ?>">
                            </td>
                            <td>
                                <p><?php echo __( '<strong>Name:</strong> ', $this->TD ) .  $student_data['name']; ?></p>
                                <p><?php echo __( '<strong>Roll:</strong> ', $this->TD) . $student_data['roll_no'] . ' ' . __('<strong>Registration Number:</strong> ', $this->TD) . $student_data['registration_no'];  ?></p>
                            </td>
                        </tr>
                        <?php
                    }
                else:
                    echo '<tr><td>&nbsp;</td><td><strong>'. $student_list->get_error_message() .'</strong></td></tr>';
                endif;
            $student = ob_get_clean();


            ob_start();
                if( ! is_wp_error( $course_list ) && ! empty( $course_list ) ) :

                    foreach ( $course_list as $course_id => $course_data ) {

                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="course_id[]" value="<?php echo $course_id; ?>">
                            </td>
                            <td>
                                <p><?php echo __( '<strong>Course Code:</strong> ', $this->TD ) . $course_data['course_code']; ?></p>
                                <p><?php echo __( '<strong>Course Name:</strong> ', $this->TD)  . $course_data['name'];  ?></p>
                            </td>
                        </tr>
                        <?php
                    }
                else:
                    echo '<tr><td>&nbsp;</td><td><strong>'. $course_list->get_error_message() .'</strong></td></tr>';
                endif;
            $course = ob_get_clean();



            $result['student'] = $student;
            $result['course'] = $course;
            $result['type'] = 'success';

        }
        else {
            $result['type'] = "Nonce Error";
        }

        header( 'Content-Type: application/json; charset=utf-8' );
        //echo result
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        }
        else {
            header("Location: ".$_SERVER["HTTP_REFERER"]);
        }

        die();
    }
    
    public function ajaxTemplate() {
        $result['type'] = '';
        //check nonce
        if( check_ajax_referer( 'ajax_nonce', 'nonce',false ) ) {
           
           
            
            
            
            $result['type'] = 'success';
        }
        else {
            $result['type'] = "Nonce Error";
        }
        
        header( 'Content-Type: application/json; charset=utf-8' );
        //echo result 
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
         }
         else {
            header("Location: ".$_SERVER["HTTP_REFERER"]);
         }

         die();
    }
    
}

