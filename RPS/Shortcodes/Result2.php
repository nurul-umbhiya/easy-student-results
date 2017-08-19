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

        ob_start();

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

        $res = ob_get_clean();

        echo $res;

    }

    private function search_form() {

        //get exam ids
        $exam_ids = $this->result->getExamID(1,1);
        $nonce = wp_nonce_field( 'view_student_result2_nonce' , 'view_student_result2', true, false );
        ?>
        <form class="form-horizontal" method="post">
            <?php echo $nonce; ?>
            <div class="form-group">
                <label for="exam_name" class="col-sm-2 control-label"><?php _e('Examination', $this->TD); ?></label>
                <div class="col-sm-10">
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
                <label for="exam_roll" class="col-sm-2 control-label"><?php $this->atts['search_by'] == 'roll' ? _e('Roll No', $this->TD) : _e('Registration No', $this->TD); ?></label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="exam_roll" name="exam_roll" placeholder="Enter <?php $this->atts['search_by'] == 'roll' ? _e('Roll No', $this->TD) : _e('Registration No', $this->TD); ?>" value="<?php echo $this->exam_roll ? $this->exam_roll : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
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
            $sql = $wpdb->prepare('SELECT * FROM `' . $wpdb->rps_exam_record .'` WHERE exam_id = %d', array($this->exam_id) );
            $all_exam_record_data = $wpdb->get_results($sql);


            if ( $all_exam_record_data == NULL || empty($all_exam_record_data) ) {
                $error_message = __('Invalid exam selected. Please check your input.', $this->TD);
                $error = true;
            }

            //now get student id by roll number
            $student_info = "";
            if ( $error == false ) {

                foreach ( $all_exam_record_data as $exam_record_data ) {
                    if ( $this->atts['search_by'] == 'roll' ) {
                        $student_info = $this->student->getStudentByRoll($exam_record_data->department_id, $exam_record_data->batch_id, $exam_record_data->semester_id, $this->exam_roll);
                    } else {
                        $student_info = $this->student->getStudentByRegNo($exam_record_data->department_id, $exam_record_data->batch_id, $exam_record_data->semester_id, $this->exam_roll);
                    }

                    if ( !is_wp_error($student_info) ) {
                        break;
                    }
                }

                if ( is_wp_error($student_info) || $student_info == "") {
                    $error = true;
                    $error_message = __('Student not found on database. Please check your input.', $this->TD);
                }



            }

            //now get student result
            if ( $error == false ) {
                $query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d", array( $exam_record_data->id, $student_info['id'] )  );
                $data = $wpdb->get_results( $query, ARRAY_A );

                //getmetadata
                $metadata = $this->result->get_exam_record_meta( $exam_record_data->id, $student_info['id'] );

                //add total_marks_obtained meta data
                if ( is_array( $metadata ) && !array_key_exists('total_marks_obtained', $metadata) ) {
                    $metadata['total_marks_obtained'] = 0;
                }

                //fix final result metadata
                if ( is_array($metadata) && array_key_exists('final_grade', $metadata) ) {
                    $grade = $this->result->getGradeList();
                    if ( array_key_exists( $metadata['final_grade'], $grade ) ) {
                        $metadata['final_grade'] = $grade[ $metadata['final_grade'] ];
                    }
                }


                $department_info = $this->dbs->getDepartmentInfo( $exam_record_data->department_id );
                $batch_info = $this->dbs->getBatchInfo($exam_record_data->department_id, $exam_record_data->batch_id);
                $semester_info = $this->dbs->getSemesterInfo( $exam_record_data->department_id, $exam_record_data->semester_id );

                if ( is_wp_error($semester_info) )
                    $semester_info = array();

                $student_info = $this->student->getStudentInfo2( $student_info['id'] );
            }

        } else {
            $error_message = __('Invalid Exam or Student. Please check your input.', $this->TD);
        }


        if ($error_message == ''):

        ?>
        <div id="res_result_print_data">

            <?php if ( isset($this->options['show_header_footer']) && $this->options['show_header_footer'] == 'on' ) { ?>
                <header id="result_header" class="avoid-this">
                    <?php echo  $this->options['print_header']; ?>
                </header>
            <?php } ?>

            <table class="table">
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

                    if( $meta_key == 'department' ) {
                        echo "<tr><th>$meta_value</th><td>";
                        echo isset( $department_info['full_name']) ? esc_attr( $department_info['full_name'] ) : '';

                    } elseif ( $meta_key == 'batch' ) {
                        echo "<tr><th>$meta_value</th><td>";
                        echo isset( $batch_info['name'] ) ? esc_attr( $batch_info['name'] ) : '';

                    } elseif( $meta_key == 'semester' ) {
                        echo "<tr><th>$meta_value</th><td>";
                        echo isset( $semester_info['name'] ) ? esc_attr( $semester_info['name'] ) : '';

                    } elseif (is_array($metadata) && array_key_exists($meta_key, $metadata)) {
                        echo "<th>{$meta_value}</th><td>{$metadata[ $meta_key ]}";
                    } else {
                        echo "<tr><th>$meta_value</th><td>";
                        echo isset( $student_info[ $meta_key ] ) ? esc_attr( $student_info[ $meta_key ] ) : '';
                    }
                    echo "</td></tr>";
                }
                ?>
                </tbody>
            </table>

            <br>
            <?php
            $subject_meta = $this->options['subject_meta'];
            $subject_meta = trim($subject_meta);
            $subject_meta = trim($subject_meta, ',');
            $meta_array = explode(',', $subject_meta);

            $subject_keys = array();
            $subject_values = array();
            $i = 0;
            foreach ( $meta_array as $meta) {
                $meta       = trim( $meta );
                $meta       = explode( ':', $meta );
                if ( count($meta) <= 1 ) {
                    break;
                }
                $meta_key   = isset($meta[0]) ? trim($meta[0]) : '';
                $meta_value = isset($meta[1]) ? trim($meta[1]) : '';
                $subject_keys[ $i ] = esc_attr( $meta_key );
                $subject_values[ $i ] = esc_attr( $meta_value );
                $i++;
            }
            if ( !empty( $subject_keys ) ) {
                ?>
                <table class="table table-striped table-hover table-bordered">

                    <thead>
                    <tr>
                        <th><?php  _e('SL', $this->TD); ?></th>
                        <?php
                        foreach ( $subject_values as $key => $value ) {
                            echo "<th>$value</th>" ;
                        }
                        ?>
                    </tr>
                    </thead>

                    <tbody>
                    <?php

                    if (is_array($data) && !empty($data)) {
                        $i = 0;
                        $grades = $this->result->getGradeList();
                        $grade_points = $this->result->getGradePointList();

                        $this->course = new RPS_Helper_Course();

                        foreach ($data as $row) {

                            $course_info = $this->course->getCourseDetails($exam_record_data->department_id, $row['subject_id'], $exam_record_data->semester_id);

                            if (is_wp_error($course_info)) {
                                $course_info = $this->course->getCourseInfo($row['subject_id']);
                            }

                            $i++;

                            $course_name = isset ($course_info['name']) ? $course_info['name'] : '';
                            $course_code = isset ($course_info['course_code']) && $course_info['course_code'] != '' ? $course_info['course_code'] : 'N/A';
                            $total_marks = isset ($course_info['total_marks']) && $course_info['total_marks'] != '' ? $course_info['total_marks'] : 'N/A';
                            $marks_obtained = $row['marks_obtained'];
                            $grade = $grades[$row['grade_id']];
                            $grade_point = $grade_points[$row['grade_id']];

                            echo "<tr><td>$i</td>";

                            foreach ($subject_keys as $key => $value) {

                                echo '<td>';
                                switch ($value) {
                                    case 'course_name':
                                        echo esc_attr($course_name);
                                        break;
                                    case 'course_code':
                                        echo esc_attr($course_code);
                                        break;
                                    case 'total_marks':
                                        echo esc_attr($total_marks);
                                        break;
                                    case 'marks_obtained':
                                        echo esc_attr($marks_obtained);
                                        break;
                                    case 'grade':
                                        echo esc_attr($grade);
                                        break;
                                    case 'grade_point':
                                        echo esc_attr($grade_point);
                                        break;
                                    default:
                                        echo '&nbsp;';
                                }
                                echo '</td>';

                            }

                            echo "</tr>";
                        }

                    } else {
                        $count = count($subject_keys) - 1;
                        echo "<tr><td colspan='$count'>" . __('No result found.', $this->TD) . "</td>></tr>";

                    }

                    ?>
                    </tbody>

                </table>

                <?php
            }
            $result_meta = $this->options['result_meta'];
            $result_meta = trim($result_meta);
            $result_meta = trim($result_meta, ',');
            $meta_array = explode(',', $result_meta);
            $result_keys = array();
            $result_values = array();
            $i = 0;
            foreach ( $meta_array as $meta) {
                $meta       = trim( $meta );
                $meta       = explode( ':', $meta );
                if ( count($meta) <= 1 ) {
                    break;
                }
                $meta_key   = isset($meta[0]) ? trim($meta[0]) : '';
                $meta_value = isset($meta[1]) ? trim($meta[1]) : '';
                $result_keys[ $i ] = $meta_key;
                $result_values[ $i ] = $meta_value;
                $i++;
            }
            if ( !empty( $result_keys ) ) {
            ?>
            <table class="table table-bordered">
                <tbody>
                <tr>
                    <?php
                    for( $i = 0; $i < count($result_keys); $i++ ) {
                        echo "<th>{$result_values[$i]}</th><td>{$metadata[ $result_keys[ $i ] ]}</td>";
                    }
                    ?>
                </tr>
                </tbody>
            </table>
            <?php } ?>

            <?php if ( isset($this->options['show_header_footer']) && $this->options['show_header_footer'] == 'on' ) { ?>
                <footer id="result_footer" class="avoid-this">
                    <?php echo  $this->options['print_footer']; ?>
                </footer>
            <?php } ?>

        </div>



        <?php if ( $this->options['print_button'] == 'on' ): ?>
            <button type="button" class="btn btn-primary" id="res_result_print"><?php echo $this->options['print_button_text']; ?></button>
            <?php if ( isset($this->options['hide_search_fields']) && $this->options['hide_search_fields'] == 'on' ) { ?>
                <a href="<?php echo get_the_permalink(get_the_ID()); ?>" class="btn btn-primary avoid-this"><?php _e('Back', RPS_Result_Management::TD) ?></a>
            <?php } ?>
            <div class="visible-print-block" id="result_prepend"><?php echo $this->options['print_header'];  ?></div>
            <div class="visible-print-block" id="result_append"><?php echo $this->options['print_footer'];  ?></div>
            <script type="text/javascript">
                var bs_url = '<?php echo $this->URL . '/assets/bootstrap-3.3.5/css/bootstrap.min.css'; ?>';
            </script>
        <?php endif; ?>

        <?php
        else:
            echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
        endif;

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