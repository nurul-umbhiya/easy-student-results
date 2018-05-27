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
	    add_action( 'wp_ajax_rps_result_load_courses', array($this,'load_courses') );
	    add_action( 'wp_ajax_rps_result_check_existing_results', array($this,'check_existing_results') );
	    add_action( 'wp_ajax_rps_result_add_student_results', array($this,'add_student_results') );

	    //migrate course data.. ie: add _course_priority meta field
	    add_action( 'wp_ajax_' . RPS_Result_Management::PLUGIN_SLUG . '_collect_course_data', array($this, 'collect_course_data') );
	    add_action( 'wp_ajax_' . RPS_Result_Management::PLUGIN_SLUG . '_migrate_course_data', array($this, 'migrate_course_data') );
    }

	public function collect_course_data() {
		$result['type'] = '';
		//check nonce
		if( check_ajax_referer( RPS_Result_Management::PLUGIN_SLUG . '_collect_course_data_' . get_current_user_id(), 'nonce',false ) && current_user_can('manage_options') ) {
			//get all student id

			$post_type = RPS_Result_Management::COURSE;

			$args = array(
				'fields' => 'ids',
				'post_type' => $post_type,
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => '_course_priority',
						'compare' => 'NOT EXISTS',
						'value' => ''
					),
				)
			);


			$query = new WP_Query( $args );

			$result['type'] = 'success';
			$result['count'] = $query->found_posts;
			$result['courses'] = wp_parse_id_list( $query->posts );
		}
		else {
			$result['type'] = "Nonce Error. Please refresh this page and try again.";
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

	public function migrate_course_data() {
		$result['type'] = '';
		//check nonce
		if( check_ajax_referer( RPS_Result_Management::PLUGIN_SLUG . '_migrate_course_data_' . get_current_user_id(), 'nonce',false )  && current_user_can('manage_options') ) {
			$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : '';

			$data = get_post_meta($course_id, '_course_priority', true);

			if ( $data == '' ) {
				$data = 0;
			}
			update_post_meta($course_id, '_course_priority', $data);


			$result['type'] = 'success';
		}
		else {
			$result['type'] = "Nonce Error. Please refresh this page and try again.";
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

	public function add_student_results() {
		$result['type'] = '';
		//check nonce
		if( check_ajax_referer( 'rps_result_add_student_results', 'nonce',false ) ) {
			global $wpdb;
			$exam_record_id        = ( isset( $_POST['exam_record_id'] ) )        ? intval( $_POST['exam_record_id'] ) : 0;
			$student_id        = ( isset( $_POST['student_id'] ) )        ? intval( $_POST['student_id'] ) : 0;
			$subjects        = ( isset( $_POST['subjects'] ) )        ? $_POST['subjects'] : array();

			if ( $exam_record_id > 0 && $student_id > 0 && !empty($subjects) ) {
				$query = "INSERT INTO {$wpdb->rps_marks} (`exam_record_id`, `student_id`, `subject_id`, `grade_id`) VALUES ";
				$rows = array();

				//get grade_id
				$st = "SELECT * FROM `{$wpdb->rps_grade}` LIMIT 1";
				$grade_data = $wpdb->get_row($st, ARRAY_A);
				if ( is_array($grade_data) ) {
					$grade_id = $grade_data['id'];
				} else {
					$grade_id = '1';
				}


				foreach ( $subjects as $course_index => $course_id ) {
					$rows[] = "($exam_record_id, $student_id, $course_id, $grade_id)";
				}

				$query .= implode(', ', $rows);

				if( $wpdb->query( $query ) ) {
					$result['type'] = 'success';
				}
				else {
				    $result['errors'][] = 'Database Error';
                }
			}
		}
		else {
			$result['errors'][] = "Nonce Error";
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

    public function check_existing_results() {
	    $result['type'] = '';
	    //check nonce
	    if( check_ajax_referer( 'rps_result_check_existing_results', 'secure',false ) ) {

	        global $wpdb;

		    $data['exam_id']        = ( isset( $_POST['exam_id'] ) )        ? intval( $_POST['exam_id'] ) : 0;
		    $data['department_id']  = ( isset( $_POST['department_id'] ) )  ? intval( $_POST['department_id'] ) : 0;
		    $data['batch_id']       = ( isset( $_POST['batch_id'] ) )       ? intval( $_POST['batch_id'] )  : 0;
		    $data['semester_id']    = ( isset( $_POST['semester_id'] ) )    ? intval( $_POST['semester_id'] ) : 0;
		    $data['display']        = ( isset( $_POST['display'] ) )        ? intval( $_POST['display'] ) : 1;
		    $data['active']         = ( isset( $_POST['active'] ) )         ? (int) $_POST['active']  : 1;

		    $flag = false;
		    //check exam_id
		    if ( ! $data['exam_id'] ) {
			    $result['errors'][] = __('Exam field is required. Please select a exam from list.', $this->TD);
			    $flag = true;
		    }

		    //check department id
		    if ( ! $data['department_id'] ) {
			    $result['errors'][] = __('Class is required. Please select a department from list.', $this->TD);
			    $flag = true;
		    }

		    //check batch_id
		    if ( ! $data['batch_id'] ) {
			    $result['errors'][] = __( 'Batch id is required. Please select a batch id from list.', $this->TD );
			    $flag = true;
		    }

		    if ( $flag == false ) {
			    //check same exam record exist on database or not
			    if ( $data['semester_id'] !== 0 ) {
				    $query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `exam_id` = %d AND `department_id` = %d
				AND `batch_id` = %d AND `semester_id` = %d", array ( $data['exam_id'], $data['department_id'], $data['batch_id'], $data['semester_id'] ) );
			    }
			    else {
				    $query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `exam_id` = %d AND `department_id` = %d
				AND `batch_id` = %d", array ( $data['exam_id'], $data['department_id'], $data['batch_id'] ) );
			    }
			    $row = $wpdb->get_row($query);
			    if ( $row !== NULL ) {
				    $result['errors'][] = __('Exam records already exists on database. Please check Exam, Department and Batch.', $this->TD);
			    }
			    else {
			        $result['type'] = 'success';

				    //get student ids
				    if ( $data['department_id'] && $data['batch_id'] && $data['semester_id'] ) {
					    $student_list   = $this->student->getStudentDetails( $data['department_id'], $data['batch_id'], $data['semester_id'] );
				    } elseif ( $data['department_id'] && $data['batch_id'] ) {
					    $student_list = $this->student->getStudentDetails( $data['department_id'], $data['batch_id'] );
				    }

				    if( ! is_wp_error( $student_list ) && ! empty( $student_list ) ) {
					    $result['students'] = array_keys($student_list);

					    //insert into exam record table
					    $data['added'] = time();
					    $format = array('%d', '%d', '%d', '%d', '%d', '%d', '%d');


					    if ( $wpdb->insert( $wpdb->rps_exam_record, $data, $format ) ) {
						    //now add result table data
						    $transient = "rps_exam_list_data";
						    delete_transient( $transient );

						    $result['exam_record_id'] = $wpdb->insert_id;
					    }
					    else {
						    $result['exam_record_id'] = 0;
					    }

				    }
				    else {
					    $result['students'] = 0;
				    }
                }
		    }
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

	public function load_courses() {
		$result['type'] = '';
		//check nonce
		if( check_ajax_referer( 'rps_result_load_courses', 'secure',false ) ) {
			$department_id = isset( $_POST['department_id'] ) ?  intval($_POST['department_id']) : 0;
			$batch_id = isset( $_POST['batch_id'] ) ?  intval($_POST['batch_id']) : 0;
			$semester_id = isset( $_POST['semester_id'] ) ?  intval($_POST['semester_id']) : 0;

			if ( $department_id && $batch_id && $semester_id ) {
				$course_list    = $this->course->getAllCourses( $department_id, $semester_id );
			} elseif ( $department_id && $batch_id ) {
				$course_list    = $this->course->getAllCourses( $department_id );
			}

			ob_start();
			if( ! is_wp_error( $course_list ) && ! empty( $course_list ) ) {
				foreach ( $course_list as $course_id => $course_data ) {
					echo "<input type=\"checkbox\" name=\"course_id[]\" value=\"{$course_id}\" checked=\"checked\"/>{$course_data['name']}<br>";
			    }
			}
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
                                <td><?php echo '<strong>'; _e('Index No', $this->TD); echo ':</strong> '. $student_data['roll_no']; ?></td>
                            </tr>

                            <?php if( ! is_wp_error( $course_list ) && ! empty( $course_list ) ) { ?>
                            <tr><td colspan="3"><?php _e('Select Subject', $this->TD); ?></td></tr>
                            <?php foreach ( $course_list as $course_id => $course_data ) { ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="course_id[student_<?php echo $student_id; ?>][]" value="<?php echo $course_id; ?>" checked="checked"/>
                                    </td>
                                    <td><?php echo __( '<strong>Subject Code:</strong> ', $this->TD ) . $course_data['course_code']; ?></td>
                                    <td><?php echo __( '<strong>Subject Name:</strong> ', $this->TD)  . $course_data['name'];  ?></td>
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
                                <p><?php echo __( '<strong>Subject Code:</strong> ', $this->TD ) . $course_data['course_code']; ?></p>
                                <p><?php echo __( '<strong>Subject Name:</strong> ', $this->TD)  . $course_data['name'];  ?></p>
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

