<?php
if( ! defined('WPINC') ) {
	die();
}

final class RPS_Admin_Menu_Result_Marks {

	protected static $instance;
	protected $pagehook, $TD;
	protected $page, $plugin_slug;
	protected $exam_record, $student, $result, $course;


	public static function getInstance( $slug ) {
		if ( self::$instance == null ) {
			self::$instance           = new self;
			self::$instance->pagehook = $slug;
			self::$instance->page     = 'rps_result_results';

			self::$instance->TD =  RPS_Result_Management::TD;
			self::$instance->plugin_slug = RPS_Result_Management::PLUGIN_SLUG;

			self::$instance->student = new RPS_Helper_Student();
			self::$instance->result = new RPS_Helper_Result();
			self::$instance->course = new RPS_Helper_Course();
			self::$instance->dbs = new RPS_Helper_DBS();

			self::$instance->mainDiv();


		}

		return self::$instance;
	}

	/**
	 * This function will hold all html related functions
	 */
	private function mainDiv() {
		if( isset($_REQUEST['page'], $_REQUEST['marks']) && $_REQUEST['page'] === $this->page ) {
			$this->getExamRecord();
			ob_start();

			if ( isset( $_REQUEST['marks'], $_REQUEST['student_id'] )  && RPS_Helper_Function::is_numeric( $_REQUEST['marks'] ) && RPS_Helper_Function::is_numeric( $_REQUEST['student_id'] ) ) {
				$this->add_student_marks();

			} elseif ( isset ( $_REQUEST['marks'] ) && RPS_Helper_Function::is_numeric( $_REQUEST['marks'] ) ) {
				$this->student_list( $_REQUEST['marks'] );
			} else {
				echo "Invalid Request";
			}
			$content = ob_get_clean();
			//global $wpdb;
			//$wpdb->show_errors();
			//$wpdb->print_error();

			echo $content;
		}

	}

	private function getExamRecord() {
		$exam_id = isset( $_REQUEST['marks'] ) ? intval( $_REQUEST['marks'] ) : 0;

		if ( RPS_Helper_Function::is_numeric( $exam_id ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `id` = %d LIMIT 1", array( $exam_id ) );

			$this->exam_record = $wpdb->get_row( $query, ARRAY_A );
		} else {
			$this->exam_record = null;
		}
	}

	private function student_list( $exam_record_id ) {
		global $wpdb;

		if ( $this->exam_record == null || empty( $this->exam_record ) ) {
			echo __("Invalid Exam Record ID", $this->TD);
			return;
		}

		//get exam record data
		$query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_exam_record} WHERE id= %d", array($exam_record_id));
		$exam_record_data = $wpdb->get_row($query,ARRAY_A);

		if ( is_array($exam_record_data) && !empty($exam_record_data) ) {
			//get exam infomartion
			$query = $wpdb->prepare("SELECT `name`, `exam_year` FROM {$wpdb->rps_exam} WHERE id= %d", array($exam_record_data['exam_id']));
			$exam_name = $wpdb->get_row($query,ARRAY_A);
			$exam_name = isset($exam_name['name']) ? $exam_name['name'] . ' - ' . $exam_name['exam_year'] : '';

			$info = $this->dbs->getDepartmentInfo($exam_record_data['department_id']);
			$dept_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];

			$info = $this->dbs->getBatchInfo( $exam_record_data['department_id'], $exam_record_data['batch_id'] );
			$batch_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];

			if ( RPS_Helper_Function::is_numeric($exam_record_data['department_id']) && RPS_Helper_Function::is_numeric($exam_record_data['semester_id']) ) {
				$info           = $this->dbs->getSemesterInfo($exam_record_data['department_id'], $exam_record_data['semester_id']);
				$semester_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];
			} else {
				$semester_name = __('N/A', $this->TD);
			}

		} else {
			echo __("Invalid Exam Record ID", $this->TD);
			return;
		}

		//this will be a wp list table
		$table = new RPS_Admin_Menu_Result_MarksTable(  $exam_record_id, $this->exam_record['department_id'], $this->exam_record['batch_id'], $this->exam_record['semester_id'] );
		//Fetch, prepare, sort, and filter our data...
		$table->prepare_items();
		?>
		<div class="rps_result">
			<div class="panel panel-default">
				<!-- Table -->
				<table class="table">
					<tbody>
					<tr>
						<th>
							<?php _e('Exam', $this->TD); ?>:
						</th>
						<td>
							<?php echo $exam_name; ?>
						</td>

						<th>
							<?php _e('Department / Class', $this->TD); ?>:
						</th>
						<td>
							<?php echo $dept_name; ?>
						</td>
					</tr>

					<tr>
						<th>
							<?php _e('Batch / Year', $this->TD); ?>:
						</th>
						<td>
							<?php echo $batch_name; ?>
						</td>

						<th>
							<?php _e('Semester / Section', $this->TD); ?>:
						</th>
						<td>
							<?php echo $semester_name; ?>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<form id="movies-filter" method="get">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<!-- Now we can render the completed list table -->
			<?php $table->display() ?>
		</form>
		<?php
	}

	private function add_student_marks() {

		global $wpdb;
		$exam_record_id = isset( $_GET['marks'] ) ? intval($_GET['marks']) : '';
		$student_id = isset( $_GET['student_id'] ) ? intval($_GET['student_id']) : '';

		if ( ! empty( $_POST ) && check_admin_referer( 'update_student_marks_nonce_' . $exam_record_id . '_' . $student_id, 'update_student_marks' ) ) {
			//update result
			$result_data = isset( $_POST['results'] ) ? $_POST['results'] : array();

			//metadata
			$total_marks = isset( $_POST['total_marks'] ) ? floatval( $_POST['total_marks'] ) : '0.00';
			$cgpa = isset( $_POST['cgpa'] ) ? floatval( $_POST['cgpa'] ) : '0.00';
			$final_result = isset( $_POST['final_result'] ) ? stripslashes( $_POST['final_result'] ) : '';
			$final_grade = isset ( $_POST['final_grade'] ) ?  intval( $_POST['final_grade'] ) : '';
			$total_marks_obtained = isset( $_POST['total_marks_obtained'] ) ? floatval( $_POST['total_marks_obtained'] ) : '';

			//update result table by single sql query
			$result_ids = implode( ',', array_keys( $result_data ) );
			$marks_obtained_data = array();
			$grade_data = array();

			foreach ( $result_data  as $key => $val  ) {
				$marks_obtained_data[ $key ] = floatval( $val['marks_obtained'] );
				$grade_data[ $key ] = intval( $val['grade'] );
			}

			$sql = "UPDATE `{$wpdb->rps_marks}` SET `marks_obtained` = CASE `id` ";
			foreach ($marks_obtained_data as $id => $ordinal) {
				$sql .= sprintf("WHEN %d THEN %f ", $id, $ordinal);
			}

			$sql .= "END, `grade_id` = CASE `id` ";

			foreach ($grade_data as $id => $ordinal) {
				$sql .= sprintf("WHEN %d THEN %d ", $id, $ordinal);
			}

			$sql .= "END WHERE `id` IN ($result_ids) AND `exam_record_id` = $exam_record_id AND `student_id` = $student_id";
			//echo $sql;

			$wpdb->query( $sql );

			//update metadata
			$this->result->update_exam_record_meta( $exam_record_id, $student_id, 'total_marks', $total_marks );
			$this->result->update_exam_record_meta( $exam_record_id, $student_id, 'cgpa', $cgpa );
			$this->result->update_exam_record_meta( $exam_record_id, $student_id, 'final_result', $final_result );
			$this->result->update_exam_record_meta( $exam_record_id, $student_id, 'final_grade', $final_grade );
			$this->result->update_exam_record_meta( $exam_record_id, $student_id, 'total_marks_obtained', $total_marks_obtained );

			//marks=1&student_id=6
			$url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'updated' => '1', 'marks' => $exam_record_id, 'student_id' => $student_id  ), 'admin.php?' ) );
			RPS_Helper_Function::javascript_redirect($url);

		} else {
			$query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d", array( $exam_record_id, $student_id )  );
			$data = $wpdb->get_results( $query, ARRAY_A );

			//getmetadata
			$metadata = $this->result->get_exam_record_meta( $exam_record_id, $student_id );

			$this->student_marks_html($data, $metadata, $exam_record_id, $student_id);

		}


	}

	private function student_marks_html( $data, $metadata, $exam_record_id = null, $student_id = null ) {

		//Table Headings
		$lbl_sl             = __('SL', $this->TD);
		$lbl_course_name    = __('Subject Name', $this->TD);
		$lbl_course_code    = __('Subject Code', $this->TD);
		$lbl_total_marks    = __('Total Marks', $this->TD);
		$lbl_marks_obtained = __('Marks Obtained', $this->TD);
		$lbl_grade          = __('Grade', $this->TD);
		$lbl_grade_point    = __('Grade Point', $this->TD);

		//labels
		$lbl_final_grade = __('Final Grade', $this->TD);
		$lbl_cgpa = __( 'CGPA', $this->TD );
		$lbl_result = __( 'Final Result', $this->TD );
		$lbl_update = __( 'Update', $this->TD );
		$lbl_back = __( 'Back', $this->TD );
		$lbl_total_marks_obtained = __( 'Marks Obtained', $this->TD );

		//metadata
		$total_marks = isset( $metadata['total_marks'] ) ? floatval($metadata['total_marks']) : 0.00;
		$cgpa = isset( $metadata['cgpa'] ) ? floatval($metadata['cgpa']) : "";
		$result = isset( $metadata['final_result'] ) ? esc_attr($metadata['final_result']) : "";
		$final_grade = isset( $metadata['final_grade'] ) ? intval($metadata['final_grade']) : "";
		$total_marks_obtained = isset( $metadata['total_marks_obtained'] ) ? floatval( $metadata['total_marks_obtained'] ) : '';


		$back_url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'marks' => $exam_record_id ), 'admin.php' ) );

		$grades = $this->result->getGradeList();

		$grade_options = "";

		if( !is_wp_error( $grades ) ) {
			foreach ( $grades as $grade_id => $grade ) {
				$grade_options .= "<option value='{$grade_id}'";
				if( $final_grade == $grade_id ) {
					$grade_options .= " selected='selected'";
				}
				$grade_options .= ">{$grade}</option>";
			}
		}

		$nonce = wp_nonce_field( 'update_student_marks_nonce_' . $exam_record_id . '_' . $student_id , 'update_student_marks', true, false );

		//get student meta


		$thead = <<<EOD
		<form method="post">
		{$nonce}
<table class="wp-list-table widefat fixed striped" id="marks_table">
	<thead>
		<tr>
			<th scope="col" class="manage-column column-sl" style="width:50px;">{$lbl_sl}</th>
			<th scope="col" class="manage-column column-course_name" style="">{$lbl_course_name}</th>
			<th scope="col" class="manage-column column-course_code" style="">{$lbl_course_code}</th>
			<th scope="col" class="manage-column column-total_marks" style="">{$lbl_total_marks}</th>
			<th scope="col" class="manage-column column-marks_obtained" style="">{$lbl_marks_obtained}</th>
			<th scope="col" class="manage-column column-grade" style="">{$lbl_grade}</th>
			<th scope="col" class="manage-column column-grade_point" style="">{$lbl_grade_point}</th>
		</tr>
	</thead>
EOD;

		$calculate_total_marks = 0;

		ob_start();

		if( is_array( $data ) && !empty( $data ) ) {

			$department_id  = isset($this->exam_record['department_id']) ? $this->exam_record['department_id'] : 0;
			$batch_id       = isset($this->exam_record['batch_id']) ? $this->exam_record['batch_id'] : 0;
			$semester_id    = isset($this->exam_record['semester_id']) ? $this->exam_record['semester_id'] : 0;
			$i = 0;
			foreach ( $data as $row ) {
				//print_r($row);
				$course_info = $this->course->getCourseDetails( $department_id, $row['subject_id'], $semester_id );
				if( is_wp_error($course_info) ) {
					$course_info = $this->course->getCourseInfo( $row['subject_id'] );
				}

				$i++;

				$course_name = isset ( $course_info['name'] ) ? $course_info['name'] : '';
				$course_code = isset ( $course_info['course_code'] ) && $course_info['course_code'] != '' ? $course_info['course_code'] : 'N/A';
				$total = isset ( $course_info['total_marks'] ) && $course_info['total_marks'] != '' ? $course_info['total_marks'] : 'N/A';

				if ( isset ( $course_info['total_marks'] ) && $course_info['total_marks'] != '' ) {
					$calculate_total_marks += floatval( $course_info['total_marks'] );
				}

				$grade_options = "";

				if( !is_wp_error( $grades ) ) {
					foreach ( $grades as $grade_id => $grade ) {
						$grade_options .= "<option value='{$grade_id}'";
						if( $row['grade_id'] == $grade_id ) {
							$grade_options .= " selected='selected'";
						}
						$grade_options .= ">{$grade}</option>";
					}
				}



				echo "<tr>
	<td><strong>{$i}</strong></td>
	<td>{$course_name}</td>
	<td>{$course_code}</td>
	<td>{$total}</td>
	<td><input type='text' name='results[{$row['id']}][marks_obtained]' class='small-text rps_marks' value='{$row['marks_obtained']}'></td>
	<td><select class='grades' name='results[{$row['id']}][grade]'>{$grade_options}</select></td>
	<td><input type='text' readonly='readonly' class='small-text rps_grade'></td>
</tr>";


			}
		} else {
			$text = __( 'No Row found.', $this->TD );
			echo "<tr><td colspan='7'>{$text}</td></tr>";
		}


		$tbody = ob_get_clean();

		if ( $total_marks == '' || $total_marks == 0 || floatval($total_marks) < floatval($calculate_total_marks) ) {
			$total_marks = $calculate_total_marks;
		}

		$tfoot = <<<EOD
	<tfoot>
		<tr>
			<th scope="col" class="manage-column column-sl" style="text-align:right;" colspan="7">
				<label for="total_marks"><strong>{$lbl_total_marks}</strong>:</label>
				<input  id="total_marks" type="text" placeholder="{$lbl_total_marks}" class="small-text" name="total_marks" value="{$total_marks}" style="padding:2px;" />&nbsp;

				<label for="total_marks_obtained"><strong>{$lbl_total_marks_obtained}</strong>:</label>
				<input  id="total_marks_obtained" type="text" placeholder="{$lbl_total_marks_obtained}" class="small-text" name="total_marks_obtained" value="{$total_marks_obtained}" style="padding:2px;" />&nbsp;

				<label for="final_grade"><strong>{$lbl_final_grade}</strong>:</label>
				<select name="final_grade" id="final_grade" style="padding: 2px;">{$grade_options}</select>

				<label for="cgpa"><strong>{$lbl_cgpa}</strong>:</label>
				<input type="text" placeholder="eg: 3.16" name="cgpa" value="{$cgpa}" id="cgpa" class="small-text" style="padding: 2px;" />&nbsp;

				<label for="final_result"><strong>{$lbl_result}</strong>:</label>
				<input type="text" placeholder="eg: passed" name="final_result" value="{$result}" id="final_result" class="" style="padding: 2px;" />&nbsp;

				<input type="submit" class="button button-primary" value="{$lbl_update}" />
				<a href="{$back_url}" class="button button-secondary">{$lbl_back}</a>

			</th>
		</tr>
	</tfoot>
</table>
</form>
EOD;

		ob_start();
		$grade_points = $this->result->getGradePointList();
		if( !is_wp_error($grade_points) ) {
			$grade_points = json_encode( $grade_points );
		} else {
			$grade_points = json_encode( array() );
		}

		echo <<<EOD
		<script type="text/javascript">
			var grade_points = $grade_points;
		</script>
EOD;
		$js = ob_get_clean();

		echo $thead . $tbody . $tfoot . $js;

	}

}
