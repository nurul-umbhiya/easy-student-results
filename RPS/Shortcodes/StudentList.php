<?php

if( !defined( 'WPINC' ) ) {
    die();
}

final class RPS_Shortcodes_StudentList extends RPS_Shortcodes_Abstract {

    private $department_id, $batch_id, $semester_id, $options;

    public function __construct( $atts ) {

        $this->atts = shortcode_atts( array(
            'foo' => 'no foo',
            'baz' => 'default baz'
        ), $atts, 'bartag' );

        parent::__construct();

        add_action('wp_footer', array($this,'wpFooter'));

        $this->actions();

    }

    private function actions() {

        $this->const = true;

        $this->department_id  = isset( $_REQUEST['department_id'] )   ? intval( $_REQUEST['department_id'] ) : '';
        $this->batch_id       = isset( $_REQUEST['batch_id'] )        ? intval( $_REQUEST['batch_id'] ) : '';
        $this->semester_id    = isset( $_REQUEST['semester_id'] )     ? intval( $_REQUEST['semester_id'] ) : null;

        $this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_students', array() );

        //ob_start();

        echo '<div class="rps_result">';
        echo '<div class="container-fluid">';
        echo '<div id="rps_result_errors"></div>';

        $this->searchForm();

        if ( ! empty( $_POST ) && check_admin_referer( 'search_student_nonce', 'search_student' ) ) {
            $this->student_list();
        }

        echo "</div><!--end .container-fluid-->";
        echo "</div><!--end #rps_result-->";

        //$res = ob_get_clean();

       // echo $res;

    }

    private function searchForm() {

        $departments = $this->dbs->getDepartmentList();

        $dept_ids = array();
        $semesters = array();
        $batches = array();

        if( !is_wp_error( $departments ) ) {
            $dept_ids = array_keys( $departments );

            if ( !empty( $dept_ids ) ) :
                foreach ( $dept_ids as $id ):
                    //get batch
                    $batch_data = $this->dbs->getAllBatchListByDepartment( $id, true );
                    $batches[$id] = is_wp_error($batch_data) ? array() : $batch_data;
                    //get semester
                    $sem = $this->dbs->getSemesterList($id);
                    $semesters[$id] = is_wp_error($sem) ? array() : $sem;
                endforeach;
            endif;
        }

        $department_id = RPS_Helper_Function::is_numeric( $this->department_id ) ?  $this->department_id : ( empty($dept_ids) ? '' : $dept_ids[0] );
        $semester_id = RPS_Helper_Function::is_numeric($this->semester_id) ? $this->semester_id : ( empty($semesters) ? '' : key( $semesters[ $department_id ] ) );
        $batch_id = RPS_Helper_Function::is_numeric($this->batch_id) ? $this->batch_id : ( empty($batches) ? '' : key( $batches[ $department_id ] ) );

        $nonce = wp_nonce_field( 'search_student_nonce' , 'search_student', true, false );

        ?>
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">

                    <form method="post" class="navbar-form navbar-left" role="search" id="search_students_form">
                        <?php  echo $nonce; ?>
                       <!-- department/class -->
                        <div class="form-group">
                            <?php if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ): ?>
                                <select name="department_id" id="department_id" class="form-control">
                                    <option value=""><?php echo isset($this->options['department_text']) ? $this->options['department_text'] : __('Select Department', $this->TD); ?></option>
                                    <?php
                                    foreach ($departments as $id => $name):
                                        echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                                    endforeach;
                                    ?>
                                </select>
                            <?php else: ?>
                                <!-- no department found -->
                            <?php endif; ?>
                        </div>

                        <!-- batch/year -->
                        <div class="form-group">
                            <?php if(!empty($batches)) : ?>
                                <select name="batch_id" id="batch_id" class="form-control">
                                    <option value=""><?php echo isset($this->options['batch_text']) ? $this->options['batch_text'] : __('Select Batch', $this->TD); ?></option>
                                    <?php
                                    if($department_id != '' && RPS_Helper_Function::is_numeric($department_id)) {
                                        $batch = isset($batches[$department_id]) ? $batches[$department_id] : '';
                                        if(is_array($batch) && !empty($batch)) {
                                            foreach ($batch as $key => $name):
                                                echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
                                            endforeach;
                                        }

                                    }
                                    ?>
                                </select>
                            <?php else: ?>
                                <!-- no batch found -->
                            <?php endif; ?>
                        </div>

                        <!-- semester/ section -->
                        <div class="form-group">
                            <?php if(!empty($semesters)): ?>
                                <select name="semester_id" id="semester_id" class="form-control">
                                    <option value=""><?php echo isset($this->options['semester_text']) ? $this->options['semester_text'] : __('Select Semester', $this->TD); ?></option>
                                    <?php
                                    if( $department_id != '' && RPS_Helper_Function::is_numeric($department_id) ) {
                                        $semester = isset($semesters[$department_id]) ? $semesters[$department_id] : array();
                                        if( is_array($semester) && !empty($semester) ) {
                                            foreach ($semester as $id => $name):
                                                echo "<option value='{$id}' ".selected($semester_id, $id, false).">{$name}</option>";
                                            endforeach;
                                        }
                                    }
                                    ?>
                                </select>

                            <?php else: ?>
                                <!-- No Semester found -->
                            <?php endif; ?>
                        </div>

                        <button type="submit" id="search_students" class="btn btn-default"><?php _e('Search', $this->TD); ?></button>
                    </form>
                </div>
            </div>
        </nav>

        <script type="text/javascript">
            var semesters = <?php echo json_encode($semesters) ?>;
            var batches = <?php echo json_encode($batches) ?>;
            var select_department   = '<?php echo isset($this->options['department_text'])  ? $this->options['department_text']     : __('Select Department', $this->TD); ?>';
            var select_batch        = '<?php echo isset($this->options['batch_text'])       ? $this->options['batch_text']          : __('Select Batch', $this->TD); ?>';
            var select_semester     = '<?php echo isset($this->options['semester_text'])    ? $this->options['semester_text']       : __('Select Semester', $this->TD); ?>';
            var department_error_text   = '<?php echo isset($this->options['department_error_text'])    ? esc_sql( $this->options['department_error_text'] )    : __('Please Select a Department From List.', $this->TD); ?>';
            var batch_error_text   = '<?php echo isset($this->options['batch_error_text'])              ? esc_sql( $this->options['batch_error_text'] )         : __('Please Select a Batch From List.', $this->TD); ?>';
            var semester_error_text   = '<?php echo isset($this->options['semester_error_text'])        ? esc_sql( $this->options['semester_error_text'] )      : __('Please Select a Semester From List.', $this->TD); ?>';
        </script>
        <?php
    }

    private function student_list() {

        $student_list = array();
        $department_info = array();
        $batch_info = array();
        $semester_info = array();

        if( RPS_Helper_Function::is_numeric($this->department_id) && RPS_Helper_Function::is_numeric($this->batch_id) ) {

            $student_list = $this->student->getStudentDetails( $this->department_id, $this->batch_id, $this->semester_id, 'all' );

            //get department, batch and semester
            $department_info = $this->dbs->getDepartmentInfo( $this->department_id );
            $batch_info = $this->dbs->getBatchInfo( $this->department_id, $this->batch_id );
            if ( RPS_Helper_Function::is_numeric($this->semester_id) ) {
	            $semester_info = $this->dbs->getSemesterInfo( $this->department_id, $this->semester_id );
            }
        } else {
            //invalid request
            echo '<div class="alert alert-danger" role="alert">' . __('Invalid Request', $this->TD) .'</div>';
            return;
        }

        if ( is_wp_error($student_list) ) {
            //print error message and return
            echo '<div class="alert alert-danger" role="alert">' . $student_list->get_error_message() .'</div>';
            return;
        }

        foreach ( $student_list as $student ):

            $img = ($student['image'] != "" ? $student['image'] : ( $student['gender'] === "Male" ?  $this->URL . '/assets/img/user_male.png' : $this->URL . '/assets/img/user_female.png' ) );
        ?>
        <div class="col-lg-6 col-sm-6 col-xs-12">

            <div class="card hovercard people">
                <img class="cardheader" src="<?php echo esc_url_raw( $img ); ?>" />
                <div class="avatar">
                    <img alt="" src="<?php echo esc_url_raw( $img ); ?>">
                </div>
                <div class="info">

                    <div class="title">
                        <a><?php echo esc_attr( $student['name'] ); ?></a>
                    </div>

                    <div class="desc">

                        <table class="table table-user-information">
                            <tbody>
                            <?php
                                $student_meta = $this->options['student_meta'];
                                $student_meta = trim( $student_meta );
                                $student_meta = trim( $student_meta, ',' );
                                $meta_array = explode(',', $student_meta);

                                foreach ( $meta_array as $meta) {
                                    $meta = trim($meta);
                                    $meta = explode(':', $meta);
                                    $meta_key = isset($meta[0]) ? trim($meta[0]) : '';
                                    $meta_value = isset($meta[1]) ? trim($meta[1]) : '';

                                    echo '<tr><th>' . $meta_value . ':</th><td>';
                                    if( $meta_key == 'department' ) {
                                        echo isset( $department_info['full_name']) ? esc_attr( $department_info['full_name'] ) : '';

                                    } elseif ( $meta_key == 'batch' ) {
                                        echo isset( $batch_info['name'] ) ? esc_attr( $batch_info['name'] ) : '';

                                    } elseif( $meta_key == 'semester' ) {
                                        echo isset( $semester_info['name'] ) ? esc_attr( $semester_info['name'] ) : '';

                                    } elseif ( isset( $student[ $meta_key ] ) ) {
                                        echo isset( $student[ $meta_key ] ) ? esc_attr( $student[ $meta_key ] ) : '';
                                    }
                                    else {
                                        do_action(RPS_Result_Management::PLUGIN_SLUG . '_student_section_meta_single_print', $student['id'], $meta_key);
                                    }

                                    echo "</td></tr>";
                                }

                            ?>
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>
        </div>
        <?php
        endforeach;
    }

    public function wpFooter() {
        if ( $this->const ) {
            //add js here
            $general = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );

            if ( $general['bootstrap_js'] != 'on') {
                wp_enqueue_script('rps_bootstrap');
            }


            wp_enqueue_script('rps_sc_student_list');
        }
    }
}