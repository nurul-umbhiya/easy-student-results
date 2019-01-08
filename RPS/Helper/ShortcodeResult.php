<?php
if( ! defined( 'WPINC' ) ) {
	die();
}

class RPS_Helper_ShortcodeResult extends RPS_Shortcodes_Abstract {

	private $exam_id, $exam_record_id, $department_id, $batch_id, $semester_id, $student_id, $options;

	private $result_data, $metadata, $student_info;

	private $student_section_data, $subject_section_data, $result_section_data;

	private $department_data, $batch_data, $semester_data;

	public function __construct( $exam_id, $exam_record_id, $department_id, $batch_id, $semester_id = null, $student_id ) {

		if (    !RPS_Helper_Function::is_numeric( $exam_record_id ) ||
				!RPS_Helper_Function::is_numeric( $department_id ) ||
		        !RPS_Helper_Function::is_numeric( $batch_id ) ||
		        !RPS_Helper_Function::is_numeric( $student_id )
		)
		{
			throw new Exception(__('Please Check Constructor values', $this->TD) );
		}

		/*
		if ( null !== $semester_id ) {
		    if ( !RPS_Helper_Function::is_numeric( $semester_id ) ) {
			    throw new Exception(__('Please Check Constructor Values', $this->TD) );
            }

		}
		*/

		parent::__construct();

		$this->exam_id = $exam_id;
		$this->exam_record_id = $exam_record_id;
		$this->department_id = $department_id;
		$this->batch_id = $batch_id;
		$this->semester_id = $semester_id;
		$this->student_id = $student_id;

		$this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );

		$this->department_data = $this->semester_data = $this->batch_data = array();
		$this->student_section_data = $this->subject_section_data = $this->result_section_data = array();


		//check department info exist
		if ( $this->department_id != '' ) {
			$this->get_department_info();
		}

		//check batch info exist
		if ( $this->batch_id != '' ) {
			$this->get_batch_info();
		}

		//check semester info exist
		if ( $this->semester_id != '' ) {
			$this->get_semester_info();
		}

		//get student info
		if ( $this->student_id != '') {
			$this->get_student_info();
		}

		//check metadata
		if ( empty($this->metadata) ) {
			$this->get_meta_data();
		}

		//get result data
		if ( $this->exam_record_id != '' && $this->student_id != '' ) {
			$this->get_result_data();
		}

	}

	public function get_result_data() {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d", array( $this->exam_record_id, $this->student_id )  );
		$this->result_data = $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_meta_data() {
		$this->metadata = $this->result->get_exam_record_meta( $this->exam_record_id, $this->student_id );

		//fix final result metadata
		if ( is_array($this->metadata) && array_key_exists('final_grade', $this->metadata) ) {
			$grade = $this->result->getGradeList();
			if ( array_key_exists( $this->metadata['final_grade'], $grade ) ) {
				$this->metadata['final_grade'] = $grade[ $this->metadata['final_grade'] ];
			}
		}
		//fix highest_marks
        $this->metadata['highest_marks'] = 0.00;

		//fix total_marks_obtained meta data
		if ( is_array($this->metadata) && !array_key_exists('total_marks_obtained', $this->metadata) ) {
			$this->metadata['total_marks_obtained'] = 0;
		}
	}

	public function get_student_info() {
		$this->student_info = $this->student->getStudentInfo( $this->department_id, $this->batch_id, $this->semester_id, $this->student_id, 'all' );
	}

	public function get_department_info() {
		$this->department_data = $this->dbs->getDepartmentInfo( $this->department_id );
	}

	public function get_semester_info() {
		$this->semester_data = $this->dbs->getSemesterInfo( $this->department_id, $this->semester_id );
		if ( is_wp_error($this->semester_data) )
			$this->semester_data = array();
	}

	public function get_batch_info() {
		$this->batch_data = $this->dbs->getBatchInfo($this->department_id, $this->batch_id);
	}

	public function get_student_info_section_data( $return = false ) {

		$this->student_section_data = array(
            'school_logo'       => $this->options['school_logo'],
            'student_image'     => esc_url(get_the_post_thumbnail_url($this->student_info['id'], $this->options['image_size'])),
            'result_header'     => $this->options['result_header'],
            'result_footer'     => $this->options['result_footer'],
            'result_custom_css' => $this->options['result_custom_css'],
            'department' => isset( $this->department_data['full_name']) ? esc_attr( $this->department_data['full_name'] ) : '',
            'batch' => isset( $this->batch_data['name'] ) ? esc_attr( $this->batch_data['name'] ) : '',
            'semester' => isset( $this->semester_data['name'] ) ? esc_attr( $this->semester_data['name'] ) : '',
            'name' => is_array( $this->student_info ) && array_key_exists( 'name', $this->student_info ) ? $this->student_info['name'] : '',
            'roll_no' => is_array( $this->student_info ) && array_key_exists( 'roll_no', $this->student_info ) ? $this->student_info['roll_no'] : '',
            'registration_no' => is_array( $this->student_info ) && array_key_exists( 'registration_no', $this->student_info ) ? $this->student_info['registration_no'] : '',
            'dob' => is_array( $this->student_info ) && array_key_exists( 'dob', $this->student_info ) ? $this->student_info['dob'] : '',

        );

		if ( $return ) {
			return $this->student_section_data;
		}
	}

	public function get_subject_section_data( $return = false ) {

		$subject_keys = array();
		$subject_headings = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_marks_table_headings', array(
			'course_name'       => __('Subject Name', $this->TD),
			'course_code'       => __('Subject Code', $this->TD),
			'ca1'               => __('Ca1', $this->TD),
			'ca2'               => __('Ca2', $this->TD),
			'ca3'               => __('Ca3', $this->TD),
			'exams'             => __('Exams', $this->TD),
			'marks_obtained'    => __('total', $this->TD),
			'position'          => __('Pos', $this->TD),
			'average'           => __('Avg', $this->TD),
			'maximum'           => __('Max', $this->TD),
			'minimum'           => __('Min', $this->TD),
			'grade'             => __('Grade', $this->TD),
			'remark'            => __('Remark', $this->TD)
		)); //need to return

		$active_fields = RPS_Result_ResultFields::get_marks_fields_single();


        foreach ( $subject_headings as $field_id => $field_value ) {
            if ( !in_array($field_id, array( 'course_name', 'course_code', 'marks_obtained') ) && !array_key_exists($field_id, $active_fields) ) {
                unset($subject_headings[ $field_id ]);
            }
        }


		$subject_values = array(); //need to return

		if ( !empty( $subject_headings ) ) {
			if ( is_array($this->result_data) && !empty($this->result_data) ) {
				$i = 0;
				$grades = $this->result->getGradeList();
				$grade_points = $this->result->getGradePointList();

				$this->course = new RPS_Helper_Course();

				foreach ($this->result_data as $row) {

					$course_info = $this->course->getCourseDetails($this->department_id, $row['subject_id'], $this->semester_id);

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
					$percentage = $row['percentage'];

					$temp = array();

					foreach ($subject_headings as $key => $value) {

						switch ($key) {
							case 'course_name':
								$temp[$key] = esc_attr($course_name);
								break;
							case 'course_code':
								$temp[$key] = esc_attr($course_code);
								break;
							case 'total_marks':
								$temp[$key] = intval($total_marks);
								break;
							case 'marks_obtained':
								$temp[$key] = intval($marks_obtained);
								break;
							case 'grade':
								$temp[$key] = esc_attr($grade);
								break;
							case 'grade_point':
								$temp[$key] = esc_attr($grade_point);
								break;
                            case 'percentage':
                                $temp[$key] = intval($percentage) . '%';
                                break;
							default:
								$temp[$key] = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_sc_subject_meta', '&nbsp;', $key, $row );
						}
					}

					$subject_values[] = $temp;
				}

			} else {
				$subject_values[] = __('No result found.', $this->TD);
			}
		}

		$this->subject_section_data = array(
			'subject_headings' => $subject_headings,
			'subject_value' => $subject_values
		);

		//echo "<pre>"; print_r($this->subject_section_data); echo "</pre>";

		if ( $return ) {
			return $this->subject_section_data;
		}
	}

	public function get_result_section_data( $return = false ) {
		$result_section_headings = array(
			'no_of_subjects'        => 'No of Subjects',
			'no_in_class'           => 'No In Class',
			'student_average'       => 'Student Ave',
			'class_average'         => 'Class Ave',
			'next_term_begins'      => 'Next Term Begins',
			'class_teacher_remark'  => 'Class Teacher Remark',
			'head_teacher_remark'   => 'Head Teacher/Principal Remark',
			'teacher_name'          => 'Teacher Name/Tel',
			'appearance'        => 'Appearance',
			'general_conduct'   => 'General Conduct',
			'attendance'        => 'Attendance',
			'leadership'        => 'Leadership',
			'games_sports'      => 'Games/Sports',
			'peer_relationship' => 'Peer Relationship',
			'hand_writing'      => 'Hand Writing',
			'punctuality'       => 'Punctuality',
			'fluency'           => 'Fluency',
			'neatness'          => 'Neatness',
			'drawing'           => 'Drawing',
			'honesty'           => 'Honesty',
			'painting'          => 'Painting',
			'attentiveness'     => 'Attentiveness',
			'musical_skills'    => 'Musical Skills',
			'health'            => 'Health',
			'craft_work'        => 'Craft Work',
			'perseverance'      => 'Perseverance',
		);

		$active_fields = RPS_Result_ResultFields::get_results_fields_single();

		foreach ( $result_section_headings as $field_id => $field_value ) {
			if ( $field_id !== 'marks_obtained' && !array_key_exists($field_id, $active_fields) ) {
				unset($result_section_headings[ $field_id ]);
			}
		}

		//add default values
        $result_section_headings['total_marks'] = __('Total Marks', $this->TD);
		$result_section_headings['total_marks_obtained'] = __('Marks Obtained', $this->TD);
		$result_section_headings['final_grade'] = __('Final Grade', $this->TD);
		$result_section_headings['cgpa'] = __('CGPA', $this->TD);
		$result_section_headings['result'] = __('Final Result', $this->TD);
		$result_section_headings['highest_marks'] = __('Highest Marks', $this->TD);
		$result_section_headings['class_position'] = __('Class Position', $this->TD);


		$temp = array();

		if ( !empty( $result_section_headings ) ) {

			foreach( $result_section_headings as $result_key => $result_value ) {
				if ( is_array( $this->metadata ) && array_key_exists( $result_key, $this->metadata  )  ) {
				    if ( $result_key == 'highest_marks' ) {
					    global $wpdb;
					    $query = $wpdb->prepare("SELECT highest_total_marks FROM `{$wpdb->rps_exam_record}` WHERE id=%d LIMIT 1", array($this->exam_record_id));
					    $res = $wpdb->get_row($query, ARRAY_A);
					    if ( is_array($res) && !empty($res) ) {
						    $temp[ $result_key ] = $res['highest_total_marks'];
					    }
				    }
					else {
						$temp[ $result_key ] = isset( $this->metadata[ $result_key ] ) ? $this->metadata[ $result_key ] : '';
                    }
				}
			}
		}

		$this->result_section_data = $temp;

		if ( $return ) {
			return $this->result_section_data;
		}
	}

	public function result_header( $echo = true ) {
		ob_start();
		?>
		<div id="res_result_print_data">
            <div class="rps_result">
		<?php
		$data = ob_get_clean();

		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}

	public function result_footer( $echo = true ) {
		ob_start();
		?>
        </div></div>
	<?php
		$data = ob_get_clean();

		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}

	public function result_print_button( $echo = true ) {
		ob_start();
		?>
            <button type="button" class="btn btn-primary" id="res_result_print">Print</button>
            <script type="text/javascript">
                var bs_url = '<?php echo $this->URL . '/assets/bootstrap-3.3.5/css/bootstrap.css'; ?>';
            </script>
        <?php
        $data = ob_get_clean();

		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}

	public function result_back_button( $echo = true ) {
		ob_start();
		$nonce = wp_nonce_field( 'search_student_nonce' , 'search_student', true, false ); ?>
			<form method="post" style="display: inline;">
				<?php echo $nonce; ?>
				<input type="hidden" name="exam_id" value="<?php echo $this->exam_id; ?>" />
				<input type="hidden" name="department_id" value="<?php echo $this->department_id; ?>" />
				<input type="hidden" name="batch_id" value="<?php echo $this->batch_id; ?>" />
				<input type="hidden" name="semester_id" value="<?php echo $this->semester_id; ?>" />
				<button type="submit" class="btn btn-primary"><?php _e('Back', $this->TD); ?></button>
			</form>
	<?php
		$data = ob_get_clean();
		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}

	public function get_table_result( $echo = true ) {
		ob_start();

		//result header section
		$this->result_header();

		$this->get_student_info_section_data();
		if ( is_array( $this->student_section_data ) && !empty( $this->student_section_data ) ):
            //top section
			?>
            <div class="row" id="result_top_section">
                <div class="col-md-4">
                    <?php
                    if ( $this->student_section_data['school_logo'] != '' ) {
                        echo "<img class='img-rounded img-responsive' width='150' height='250' src='{$this->student_section_data['school_logo']}' style='display: block; margin: 0 auto;'>";
                    }
                    ?>
                </div>
                <div class="col-md-4">
                    <table class="table border">
                        <tbody>
                        <tr>
                            <td style="text-align: center; border:none;"><?php echo $this->student_section_data['result_header']; ?></td>
                        </tr>
                        <tr>
                            <td style="border:none;"><?php echo $this->student_section_data['semester'] . ' <strong>Term</strong> ' . $this->student_section_data['batch'] . ' <strong>ACADEMIC SESSION</strong>'; ?></td>
                        </tr>
                        <tr>
                            <td style="border:none;"><?php echo '<strong>Admission No:</strong> ' . $this->student_section_data['registration_no']; ?></td>
                        </tr>
                        <tr>
                            <td style="border:none;"><?php echo '<strong>Name:</strong> ' . $this->student_section_data['name']; ?></td>
                        </tr>
                        <tr>
                            <td style="border:none;"><?php echo '<strong>Class:</strong> ' . $this->student_section_data['department']; ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <?php
                    if ( $this->student_section_data['student_image'] != '' ) {
                     echo "<img class='img-rounded img-responsive' width='150' height='250' src='{$this->student_section_data['student_image']}' style='display: block; margin: 0 auto;'>";
                    }
                    ?>
                </div>
            </div>
			<?php
		endif;

		//subject section start here
		$this->get_subject_section_data();

		/*
		 * $this->subject_section_data = array(
			'subject_headings' => $subject_headings,
			'subject_value' => $subject_values
		);
		 */
		if ( is_array( $this->subject_section_data['subject_headings'] ) && !empty( $this->subject_section_data['subject_headings'] ) ):
			?>
			<table class="table table-striped table-hover table-bordered">
				<thead>
				<tr>
					<?php
					if ( is_array($this->subject_section_data['subject_headings']) && !empty( $this->subject_section_data['subject_headings'] ) )
						foreach( $this->subject_section_data['subject_headings'] as $key => $value) {
							echo "<th>$value</th>";
						}
					?>
				</tr>
				</thead>

				<tbody>
				<?php
				if ( is_array($this->subject_section_data['subject_value']) && !empty($this->subject_section_data['subject_value']) ) {
					foreach ( $this->subject_section_data['subject_value'] as $key => $marks ) {
						foreach ( $marks as $mark ) {
							echo '<td>' . $mark . '</td>';
						}
						echo '</tr>';
					}
				} else {
					$count = count($this->subject_section_data['subject_headings']) - 1;
					echo "<tr><td colspan='$count'>" . __('No result found.', $this->TD) . "</td>></tr>";

				}
				?>
				</tbody>
			</table>
			<br>
			<?php
		endif;

		//now get result section
		$this->get_result_section_data();

		if ( is_array( $this->result_section_data ) && !empty( $this->result_section_data ) ):
			?>
            <table class="table table-bordered">
            <thead>
                <th colspan="4">SUMMARY</th>
            </thead>
            <tbody>
                <tr>
                    <th>No of Subjects</th>
                    <td><?php echo $this->result_section_data['no_of_subjects']; ?></td>
                    <th>No In Class</th>
                    <td><?php echo $this->result_section_data['no_in_class']; ?></td>
                </tr>
                <tr>
                    <th>Class Position</th>
                    <td><?php echo $this->result_section_data['class_position']; ?></td>
                    <th>Student Ave</th>
                    <td><?php echo $this->result_section_data['student_average']; ?></td>
                </tr>
                <tr>
                    <th>Class Ave</th>
                    <td><?php echo $this->result_section_data['class_average']; ?></td>
                    <th>Next Term Begins</th>
                    <td><?php echo $this->result_section_data['next_term_begins']; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Class Teacher Remark</th>
                    <td colspan="2"><?php echo $this->result_section_data['class_teacher_remark']; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Head Teacher/Principal Remark</th>
                    <td colspan="2"><?php echo $this->result_section_data['head_teacher_remark']; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Teacher Name/Tel</th>
                    <td colspan="2"><?php echo $this->result_section_data['teacher_name']; ?></td>
                </tr>
            </tbody>


			<table class="table table-bordered">
                <thead>
                <th colspan="6">Psychomotor Domain</th>
                <th colspan="6">Affective Domain</th>
                </thead>
				<tbody>
                    <tr>
                        <td>Appearance</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['appearance']); ?></td>

                        <td>General Conduct</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['general_conduct']); ?></td>
                    </tr>

                    <tr>
                        <td>Attendance</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['attendance']); ?></td>

                        <td>Leadership</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['leadership']); ?></td>
                    </tr>

                    <tr>
                        <td>Games/Sports</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['games_sports']); ?></td>

                        <td>Peer Relationship</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['peer_relationship']); ?></td>
                    </tr>

                    <tr>
                        <td>Hand Writing</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['hand_writing']); ?></td>

                        <td>Punctuality</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['punctuality']); ?></td>
                    </tr>

                    <tr>
                        <td>Fluency</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['fluency']); ?></td>

                        <td>Neatness</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['neatness']); ?></td>
                    </tr>

                    <tr>
                        <td>Drawing</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['drawing']); ?></td>

                        <td>Honesty</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['honesty']); ?></td>
                    </tr>

                    <tr>
                        <td>Painting</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['painting']); ?></td>

                        <td>Attentiveness</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['attentiveness']); ?></td>
                    </tr>

                    <tr>
                        <td>Musical Skills</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['musical_skills']); ?></td>

                        <td>Health</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['health']); ?></td>
                    </tr>

                    <tr>
                        <td>Craft Work</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['craft_work']); ?></td>

                        <td>Perseverance</td>
                        <td colspan="5"><?php echo RPS_Helper_Function::returnDomain($this->result_section_data['perseverance']); ?></td>
                    </tr>

				</tbody>
			</table>
			<?php echo '<div style="text-align: center;">' . $this->student_section_data['result_footer'] . '</div>'; ?>
		<?php
		endif;

		//get result footer
		$this->result_footer();

		//get print button
		$this->result_print_button();

		//ger result back button
		$this->result_back_button();

		$data = ob_get_clean();

		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}




}