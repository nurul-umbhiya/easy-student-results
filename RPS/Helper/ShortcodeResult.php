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

		$this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_results', array() );

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

		$this->student_section_data = array();

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
				$this->student_section_data[] = array(
					'key' => $meta_value,
					'value' => trim( $this->department_data['full_name']) != '' ? esc_attr( $this->department_data['full_name'] ) : esc_attr( $this->department_data['name'] )
				);
			} elseif ( $meta_key == 'batch' ) {
				$this->student_section_data[] = array(
					'key' => $meta_value,
					'value' => isset( $this->batch_data['name'] ) ? esc_attr( $this->batch_data['name'] ) : ''
				);
			} elseif( $meta_key == 'semester' ) {
				$this->student_section_data[] = array(
					'key' => $meta_value,
					'value' => isset( $this->semester_data['name'] ) ? esc_attr( $this->semester_data['name'] ) : ''
				);

			} elseif (is_array($this->metadata) && array_key_exists($meta_key, $this->metadata)) {
				if ( $meta_key == 'total_percentage' ) {
					$this->student_section_data[] = array(
						'key' => $meta_value,
						'value' => $this->metadata[ $meta_key ] . '%'
					);
				}
                elseif ( $meta_key == 'highest_marks' ) {
					global $wpdb;
					$query = $wpdb->prepare("SELECT highest_total_marks FROM `{$wpdb->rps_exam_record}` WHERE id=%d LIMIT 1", array($this->exam_record_id));
					$res = $wpdb->get_row($query, ARRAY_A);
					if ( is_array($res) && !empty($res) ) {
						$this->student_section_data[] = array(
							'key' => $meta_value,
							'value' => $res['highest_total_marks']
						);
					}

				}
				else {
					$this->student_section_data[] = array(
						'key' => $meta_value,
						'value' => $this->metadata[ $meta_key ]
					);
                }




			} elseif ( is_array( $this->student_info ) && array_key_exists( $meta_key, $this->student_info ) ) {
				$this->student_section_data[] = array(
					'key' => $meta_value,
					'value' => isset( $this->student_info[ $meta_key ] ) ? esc_attr( $this->student_info[ $meta_key ] ) : ''
				);
			} else {
			    //probably a hook to add other data
                $hook_temp = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_student_section_meta_single', 'temp_string', $this->student_id, $meta_key );

                if ( $hook_temp != 'temp_string' ) {
	                $this->student_section_data[] = array(
		                'key' => $meta_value,
		                'value' => $hook_temp
	                );
                }
            }
		}

		//echo "<pre>"; print_r($this->student_section_data); echo "</pre>";

		if ( $return ) {
			return $this->student_section_data;
		}
	}

	public function get_subject_section_data( $return = false ) {

		$subject_keys = array();
		$subject_headings = array(); //need to return
		$subject_values = array(); //need to return


		$subject_meta = $this->options['subject_meta'];
		$subject_meta = trim($subject_meta);
		$subject_meta = trim($subject_meta, ',');
		$meta_array = explode(',', $subject_meta);


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
			$subject_headings[ $i ] = esc_attr( $meta_value );
			$i++;
		}

		if ( !empty( $subject_keys ) ) {
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

					foreach ($subject_keys as $key => $value) {

						switch ($value) {
							case 'course_name':
								$temp[] = esc_attr($course_name);
								break;
							case 'course_code':
								$temp[] = esc_attr($course_code);
								break;
							case 'total_marks':
								$temp[] = intval($total_marks);
								break;
							case 'marks_obtained':
								$temp[] = intval($marks_obtained);
								break;
							case 'grade':
								$temp[] = esc_attr($grade);
								break;
							case 'grade_point':
								$temp[] = esc_attr($grade_point);
								break;
                            case 'percentage':
                                $temp[] = intval($percentage) . '%';
                                break;
							default:
								$temp[] = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_sc_subject_meta', '&nbsp;', $value, $row );
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
		//get student result values
		$result_keys = array();
		$result_values = array();

		$result_meta = $this->options['result_meta'];
		$result_meta = trim($result_meta);
		$result_meta = trim($result_meta, ',');
		$meta_array = explode(',', $result_meta);

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

			for( $i = 0; $i < count($result_keys); $i++ ) {
				if ( is_array( $this->metadata ) && array_key_exists( $result_keys[ $i ], $this->metadata  )  ) {
				    if ( $result_keys[ $i ] == 'total_percentage' ) {
					    $this->result_section_data[] = array(
						    'key' => $result_values[$i],
						    'value' => $this->metadata[ $result_keys[ $i ] ] . '%'
					    );
                    }
                    elseif ( $result_keys[ $i ] == 'highest_marks' ) {
					    global $wpdb;
					    $query = $wpdb->prepare("SELECT highest_total_marks FROM `{$wpdb->rps_exam_record}` WHERE id=%d LIMIT 1", array($this->exam_record_id));
					    $res = $wpdb->get_row($query, ARRAY_A);
					    if ( is_array($res) && !empty($res) ) {
						    $this->result_section_data[] = array(
							    'key' => $result_values[$i],
							    'value' => $res['highest_total_marks']
						    );
					    }

				    }
					else {
						$this->result_section_data[] = array(
							'key' => $result_values[$i],
							'value' => $this->metadata[ $result_keys[ $i ] ]
						);
                    }
				}
			}
		}

		if ( $return ) {
			return $this->result_section_data;
		}
	}

	public function result_header( $echo = true ) {
		ob_start();
		?>
		<div id="res_result_print_data" class="rps_result">

		<?php if ( isset($this->options['show_header_footer']) && $this->options['show_header_footer'] == 'on' ) { ?>
			<header id="result_header" class="avoid-this">
				<?php echo  $this->options['print_header']; ?>
			</header>
		<?php } ?>
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
		if ( isset($this->options['show_header_footer']) && $this->options['show_header_footer'] == 'on' ) { ?>
			<footer id="result_footer" class="avoid-this">
				<?php echo  $this->options['print_footer']; ?>
			</footer>
		<?php } ?>
		</div>
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
		if ( $this->options['print_button'] == 'on' ): ?>
            <button type="button" class="btn btn-primary" id="res_result_print"><?php echo $this->options['print_button_text']; ?></button>
            <div class="visible-print-block" id="result_prepend"><?php echo $this->options['print_header'];  ?></div>
            <div class="visible-print-block" id="result_append"><?php echo $this->options['print_footer'];  ?></div>
            <script type="text/javascript">
                var bs_url = '<?php echo $this->URL . '/assets/bootstrap-3.3.5/css/bootstrap.css'; ?>';
            </script>
        <?php endif;
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
            //add student picture here
            if ( isset($this->options['show_picture']) && $this->options['show_picture'] == 'on'
                 && isset( $this->student_info['image']) && $this->student_info['image'] != '' ) {
	            $featured_img_url = esc_url(get_the_post_thumbnail_url($this->student_info['id'], $this->options['image_size']));
		        echo "<p><img class='img-rounded img-responsive' src='{$featured_img_url}' style='display: block; margin: 0 auto;'></p>";
            }


			//student section starts here
			?>
			<table class="table">
				<tbody>
				<?php
				foreach ( $this->student_section_data as $data ) {
					echo '<tr><th>' . $data['key'] . '</th><td>' . $data['value'] . '</td></tr>';
				}
				?>
				</tbody>
			</table>
			<br>
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
					<th><?php _e('SL', $this->TD); ?></th>
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
					$i = 0;
					foreach ( $this->subject_section_data['subject_value'] as $key => $marks ) {
						$i++;
						echo "<tr><td>$i</td>";
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
				<tbody>
					<?php
                    $i = 1;
                    $count = count( $this->result_section_data );
                    echo '<tr>';
					foreach ( $this->result_section_data as $row ) {
						echo '<th>' . $row['key'] . '</th><td>' . $row['value'] . '</td>';
					    if ( $i % 2 == 0  ) {
					        echo '</tr>';
					        if ( $i < $count ) {
					            echo '<tr>';
                            }
                        }
                        $i++;
					}
					if ( $i % 2 == 0 ) {
					    echo '</tr>';
                    }
					?>
				</tbody>
			</table>
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