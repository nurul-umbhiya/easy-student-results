<?php
if( !defined( 'WPINC' ) ) {
    die();
}

final class RPS_Shortcodes_Result2 extends RPS_Shortcodes_Abstract
{

    private $exam_id, $exam_roll, $options;

    public function __construct($atts) {

        parent::__construct();

        $this->atts = shortcode_atts(array(
            'search_by' => 'roll',
            'baz' => 'default baz'
        ), $atts);

        add_action('wp_footer', array($this, 'wpFooter'));

        $this->actions();

    }

    private function actions() {

        $this->const = true;
        $this->exam_id = isset($_REQUEST['exam_id']) ? intval($_REQUEST['exam_id']) : '';
        $this->exam_roll = isset($_REQUEST['exam_roll']) ? strip_tags($_REQUEST['exam_roll']) : '';
        $this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_results', array() );

        //ob_start();

        if ( trim( $this->options['custom_css'] ) != '' ) {
            echo '<style type="text/css" rel="stylesheet">' . trim( $this->options['custom_css'] ) . '</style>';
        }

        echo '<div class="rps_result" id="rps_result">';
        echo '<div class="container-fluid">';
        echo '<div id="rps_result_errors"></div>';

        if ( ! empty( $_POST ) && isset($_POST['view_student_result2']) && check_admin_referer( 'view_student_result2_nonce', 'view_student_result2' ) ) {
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
        $nonce = wp_nonce_field( 'view_student_result2_nonce' , 'view_student_result2', true, false );
        ?>
        <form class="form-horizontal" method="post">
            <?php echo $nonce; ?>
            <div class="form-group">
                <label for="exam_name" class="col-sm-4 control-label"><?php _e('Examination', $this->TD); ?></label>
                <div class="col-sm-8">
                    <select name="exam_id" id="exam_id" class="form-control">
                        <?php if ( !empty($exam_ids) ) {
                            foreach ( $exam_ids as $exam_data ) {
                                $selected = $this->exam_id == $exam_data['id'] ? 'selected="selected"' : '';
                                echo "<option value='{$exam_data['id']}' {$selected}>{$exam_data['name']} - {$exam_data['exam_year']}</option>";
                            }
                        } else {
                            echo "<option>" . __('No Exam Found',$this->TD) . '</option>' ;
                        } ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="exam_roll" class="col-sm-4 control-label"><?php $this->atts['search_by'] == 'roll' ? _e('Roll No', $this->TD) : _e('Registration No', $this->TD); ?></label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="exam_roll" name="exam_roll" placeholder="Enter <?php $this->atts['search_by'] == 'roll' ? _e('Roll No', $this->TD) : _e('Registration No', $this->TD); ?>" value="<?php echo $this->exam_roll ? $this->exam_roll : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-8">
                    <button type="submit" class="btn btn-primary"><?php _e('Submit', $this->TD); ?></button>
                    <button type="reset" class="btn btn-default"><?php _e('Reset', $this->TD); ?></button>
                </div>
            </div>
        </form>
        <?php

    }


    private function display_student_result() {
        //check required field exist
        global $wpdb;
        $error = false;
        $error_message = "";

        if ( $this->exam_id != '' && $this->exam_roll != '' ) {
	        //now get exam record data
	        $sql                  = $wpdb->prepare( 'SELECT * FROM `' . $wpdb->rps_exam_record . '` WHERE exam_id = %d', array( $this->exam_id ) );
	        $all_exam_record_data = $wpdb->get_results( $sql );


	        if ( $all_exam_record_data == null || empty( $all_exam_record_data ) ) {
		        $error_message = __( 'Invalid exam selected. Please check your input.', $this->TD );
		        $error         = true;
	        }

	        //now get student id by roll number
	        $student_info = "";
	        if ( $error == false ) {

		        foreach ( $all_exam_record_data as $exam_record_data ) {
			        if ( $this->atts['search_by'] == 'roll' ) {
				        $student_info = $this->student->getStudentByRoll( $exam_record_data->department_id, $exam_record_data->batch_id, $exam_record_data->semester_id, $this->exam_roll );
			        } else {
				        $student_info = $this->student->getStudentByRegNo( $exam_record_data->department_id, $exam_record_data->batch_id, $exam_record_data->semester_id, $this->exam_roll );
			        }

			        if ( ! is_wp_error( $student_info ) ) {
				        break;
			        }
		        }

		        if ( is_wp_error( $student_info ) || $student_info == "" ) {
			        $error         = true;
			        $error_message = __( 'Student not found on database. Please check your input.', $this->TD );
		        }


	        }

	        //now get student result

	        if ( $error === false ) {
		        try {
			        $obj = new RPS_Helper_ShortcodeResult( $this->exam_id, $exam_record_data->id, $exam_record_data->department_id,
				        $exam_record_data->batch_id, $exam_record_data->semester_id, $student_info['id'] );

			        $obj->get_table_result();

		        } catch ( Exception $e ) {
			        $error_message = $e->getMessage();
		        }
	        }

	        if ( $error_message ) {
	            //show error
                echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
            }
        }
    }

    public function wpFooter() {
        if ( $this->const ) {
            //add js here
            $general = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );

            if ( $general['bootstrap_js'] != 'on') {
                wp_enqueue_script('rps_bootstrap');
            }

            wp_enqueue_script('jquery_print');
            wp_enqueue_script('rps_sc_result2');
        }
    }
}