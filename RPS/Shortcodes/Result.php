<?php
if( !defined( 'WPINC' ) ) {
    die();
}

final class RPS_Shortcodes_Result extends RPS_Shortcodes_Abstract
{

    private $exam_id, $exam_record_id, $department_id, $batch_id, $semester_id, $student_id, $options;

    public function __construct($atts) {

        $this->atts = shortcode_atts(array(
            'foo' => 'no foo',
            'baz' => 'default baz'
        ), $atts, 'bartag');

        parent::__construct();

        add_action('wp_footer', array($this, 'wpFooter'));

        $this->actions();

    }

    private function actions() {

        $this->const = true;
        $this->exam_id = isset($_REQUEST['exam_id']) ? intval($_REQUEST['exam_id']) : '';
        $this->exam_record_id = isset($_REQUEST['exam_record_id']) ? intval($_REQUEST['exam_record_id']) : '';
        $this->department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : '';
        $this->batch_id = isset($_REQUEST['batch_id']) ? intval($_REQUEST['batch_id']) : '';
        $this->semester_id = isset($_REQUEST['semester_id']) ? intval($_REQUEST['semester_id']) : null;
        $this->student_id = isset($_REQUEST['student_id']) ? intval($_REQUEST['student_id']) : '';
        $this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_results', array() );


        //ob_start();

        if ( trim( $this->options['custom_css'] ) != '' ) {
            echo '<style type="text/css" rel="stylesheet">' . trim( $this->options['custom_css'] ) . '</style>';
        }

        echo '<div class="rps_result" id="rps_result">';
        echo '<div class="container-fluid">';
        echo '<div id="rps_result_errors"></div>';

        if ( ! empty( $_POST ) && isset($_POST['search_student']) && check_admin_referer( 'search_student_nonce', 'search_student' ) ) {
            $this->search_form();
            $this->student_list();
        }
        elseif ( ! empty( $_POST ) && isset($_POST['view_student_result']) && check_admin_referer( 'view_student_result_nonce', 'view_student_result' ) ) {
            //check new option
            if ( isset($this->options['hide_search_fields']) && $this->options['hide_search_fields'] == 'on' ) {

            } else {
                $this->search_form();
            }

            $this->display_student_result();
        }
        else {
            $this->search_form();
        }

        echo "</div><!--end .container-fluid-->";
        echo "</div><!--end #rps_result-->";

        //$res = ob_get_clean();

        //echo $res;

    }

    private function search_form() {

        //get exam ids
        $exam_ids = $this->result->getExamID(1,1);


        $exam_id = ( RPS_Helper_Function::is_numeric($this->exam_id) ) ? $this->exam_id : (!empty( $exam_ids ) ? $exam_ids[0]['id'] : "");

        //get departments, batches and semesters for dropdown list
        $exam_list_data = $this->result->getExamListData();

        //echo "<pre>"; print_r($exam_list_data); echo "</pre>";

        $departments = ( RPS_Helper_Function::is_numeric($this->exam_id) ? $exam_list_data[$this->exam_id]['department_ids'] :
            ( !empty( $exam_ids ) ? $exam_list_data[ $exam_ids[0]['id'] ]['department_ids'] : array() ) );

        $department_id = ( RPS_Helper_Function::is_numeric($this->department_id) ) ? $this->department_id : (
        !empty( $exam_ids ) && !empty($exam_list_data[ $exam_ids[0]['id'] ]['department_ids']) ? key($exam_list_data[ $exam_ids[0]['id'] ]['department_ids']) : ""
        );

        $batchs = ( RPS_Helper_Function::is_numeric($this->exam_id) && isset($exam_list_data[ $this->exam_id ][ $department_id ]['batch_ids']) ? $exam_list_data[ $this->exam_id ][ $department_id ]['batch_ids'] :
            ( !empty( $exam_ids ) ? $exam_list_data[ $exam_ids[0]['id'] ][$department_id]['batch_ids'] : array() ) );

        $batch_id = ( RPS_Helper_Function::is_numeric($this->batch_id) ) ? $this->batch_id : (
        !empty( $exam_ids ) && !empty($exam_list_data[ $exam_ids[0]['id']][$department_id]['batch_ids']) ? key($exam_list_data[ $exam_ids[0]['id'] ][$department_id]['batch_ids']) : ""
        );

        $semesters = ( RPS_Helper_Function::is_numeric($this->exam_id) && isset( $exam_list_data[$this->exam_id][$department_id]['semester_ids'] ) ? $exam_list_data[$this->exam_id][$department_id]['semester_ids'] :
            ( !empty( $exam_ids ) ? $exam_list_data[ $exam_ids[0]['id'] ][$department_id]['semester_ids'] : array() ) );

        $semester_id = ( RPS_Helper_Function::is_numeric($this->semester_id) ) ? $this->semester_id : (
        !empty( $exam_ids ) && !empty($exam_list_data[ $exam_ids[0]['id'] ][$department_id]['semester_ids']) ? key($exam_list_data[ $exam_ids[0]['id'] ][$department_id]['semester_ids']) : ""
        );
        //select current exam_id, department_id, batch_id and semester_id


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

                    <form method="post" class="navbar-form navbar-left" role="search" id="search_result_form">
                    <?php  echo $nonce; ?>

                        <div class="form-group">
                        <?php if ( !empty( $exam_ids ) ) : ?>
                            <select name="exam_id" id="exam_id" class="form-control">
                                <option value=""><?php echo isset($this->options['exam_text']) ? esc_attr( $this->options['exam_text'] ) : __('Select Exam', $this->TD); ?></option>
                                <?php
                                foreach ( $exam_ids as $row ) {
                                    echo "<option value='{$row['id']}'" . selected($exam_id, $row['id'], false) . ">{$row['name']}</option>";
                                }
                                ?>
                            </select>

                        <?php else: ?>

                        <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <select name="department_id" id="department_id" class="form-control">
                                <option
                                    value=""><?php echo isset($this->options['department_text']) ? esc_attr($this->options['department_text']) : __('Department', $this->TD); ?></option>
                                <?php
                                if (!empty($departments))
                                    foreach ($departments as $dept_id => $dept_name) {
                                        echo "<option value='{$dept_id}'" . selected($department_id, $dept_id, false) . ">{$dept_name}</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="batch_id" id="batch_id" class="form-control">
                                <option
                                    value=""><?php echo isset($this->options['batch_text']) ? esc_attr($this->options['batch_text']) : __('Batch', $this->TD); ?></option>
                                <?php
                                if (is_array($batchs) && !empty($batchs))
                                    foreach ($batchs as $bid => $bname) {
                                        echo "<option value='{$bid}'" . selected($batch_id, $bid, false) . ">{$bname}</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="semester_id" id="semester_id" class="form-control">
                                <option
                                    value=""><?php echo isset($this->options['semester_text']) ? $this->options['semester_text'] : __('Semester', $this->TD); ?></option>
                                <?php
                                if (!empty($semesters))
                                    foreach ($semesters as $sem_id => $sem_name) {
                                        echo "<option value='{$sem_id}'" . selected($semester_id, $sem_id, false) . ">{$sem_name}</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        <button type="submit" id="search_students" class="btn btn-default"><?php _e('Search', $this->TD); ?></button>
                    </form>
                </div>
            </div>
        </nav>

        <br>

        <script>
            var exam_list_data = <?php echo json_encode( $exam_list_data ); ?>;
            var select_exam         = '<?php echo isset($this->options['exam_text']) ? esc_attr( $this->options['exam_text'] ) : __('Select Exam', $this->TD); ?>';
            var select_department   = '<?php echo isset($this->options['department_text'])  ? esc_sql( $this->options['department_text'] )    : __('Select Department', $this->TD); ?>';
            var select_batch        = '<?php echo isset($this->options['batch_text'])       ? esc_sql( $this->options['batch_text'] )         : __('Select Batch', $this->TD); ?>';
            var select_semester     = '<?php echo isset($this->options['semester_text'])    ? esc_sql( $this->options['semester_text'] )      : __('Select Semester', $this->TD); ?>';

            var exam_error_text   = '<?php echo isset($this->options['exam_error_text'])                ? esc_sql( $this->options['exam_error_text'] )          : __('Please Select a Exam From List.', $this->TD); ?>';
            var department_error_text   = '<?php echo isset($this->options['department_error_text'])    ? esc_sql( $this->options['department_error_text'] )    : __('Please Select a Department From List.', $this->TD); ?>';
            var batch_error_text   = '<?php echo isset($this->options['batch_error_text'])              ? esc_sql( $this->options['batch_error_text'] )         : __('Please Select a Batch From List.', $this->TD); ?>';
            var semester_error_text   = '<?php echo isset($this->options['semester_error_text'])        ? esc_sql( $this->options['semester_error_text'] )      : __('Please Select a Semester From List.', $this->TD); ?>';
        </script>

    <?php

    }

    private function student_list() {

        if( RPS_Helper_Function::is_numeric( $this->exam_id ) && RPS_Helper_Function::is_numeric( $this->department_id )
            && RPS_Helper_Function::is_numeric( $this->batch_id ) )
        {
            $student_list = $this->result->getStudentIDsBySearchForm( $this->exam_id, $this->department_id, $this->batch_id, $this->semester_id );


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

        $nonce = wp_nonce_field( 'view_student_result_nonce' , 'view_student_result', true, false );

    ?>
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th><?php  _e('Name', $this->TD); ?></th>
                    <th><?php  _e('Roll No', $this->TD); ?></th>
                    <th><?php  _e('Registration No', $this->TD); ?></th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
            <?php
                if ( !is_wp_error( $student_list ) ) {
                    foreach ( $student_list['data'] as $student_id => $student_info ) {
                        if( !is_wp_error($student_info) || !empty($student_info) ) {
                            $name = $student_info['name'];
                            $roll_no = $student_info['roll_no'];
                            $registration_no = $student_info['registration_no'];

                            echo <<< EOD
                            <tr>
                                <td>$name</td>
                                <td>$roll_no</td>
                                <td>$registration_no</td>
                                <td>
                                    <form method="post">
                                        {$nonce}
                                        <input type="hidden" name="exam_id" value="{$this->exam_id}" />
                                        <input type="hidden" name="exam_record_id" value="{$student_list['exam_record_id']}" />
                                        <input type="hidden" name="department_id" value="{$this->department_id}" />
                                        <input type="hidden" name="batch_id" value="{$this->batch_id}" />
                                        <input type="hidden" name="semester_id" value="{$this->semester_id}" />
                                        <input type="hidden" name="student_id" value="{$student_id}" />
                                        <button type="submit" class="btn btn-primary">View Result</button>
                                    </form>
                                </td>
                            </tr>
EOD;
                        } else {
                            echo "<tr><td colspan='4'>&nbsp;</td></tr>";
                        }

                    }
                } else {
                    echo '<tr><td colspan="4">' . __('No Students found.', $this->TD) . '</td></tr>';
                }

            ?>
            </tbody>
        </table>

    <?php


    }

    private function display_student_result() {
        if( !RPS_Helper_Function::is_numeric( $this->exam_record_id ) && !RPS_Helper_Function::is_numeric( $this->student_id ) ) {
            echo '<div class="alert alert-danger" role="alert">' . __('Invalid Request', $this->TD) .'</div>';
            return;
        }

        try {
            $obj = new RPS_Helper_ShortcodeResult( $this->exam_id, $this->exam_record_id, $this->department_id,
                $this->batch_id, $this->semester_id, $this->student_id);

            $obj->get_table_result();

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function wpFooter() {
        if ( $this->const ) {
            //add js here
            $general = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );

            if ( $general['bootstrap_js'] != 'on') {
                wp_enqueue_script('rps_bootstrap');
            }

            wp_enqueue_script('rps_sc_result');
            wp_enqueue_script('jquery_print');
        }
    }
}