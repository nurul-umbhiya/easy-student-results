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
			$metadata = isset( $_POST['metadata']) ? $_POST['metadata'] : array();

			//update result table by single sql query
			$result_ids = implode( ',', array_keys( $result_data ) );
			$marks_obtained_data = array();
			$grade_data = array();
			$percentage_data = array();

			foreach ( $result_data  as $key => $val  ) {
				$marks_obtained_data[ $key ] = floatval( $val['marks_obtained'] );
				$grade_data[ $key ] = intval( $val['grade'] );
				$percentage_data[ $key ] = floatval( $val['percentage'] );
			}

			$sql = "UPDATE `{$wpdb->rps_marks}` SET `marks_obtained` = CASE `id` ";

			//marks_obtained
			foreach ($marks_obtained_data as $id => $ordinal) {
				$sql .= sprintf("WHEN %d THEN %f ", $id, $ordinal);
			}

			$sql .= "END, `percentage` = CASE `id` ";

			//percentage
			foreach ($percentage_data as $id => $ordinal) {
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
            if ( !empty($metadata) ) {

	            foreach ( $metadata as $meta_key => $meta_value ) {
	                switch ($meta_key) {
                        case 'total_marks':
	                        $total_marks = $meta_value != '' ? floatval( $meta_value ) : '0.00';
	                        $this->result->update_exam_record_meta( $exam_record_id, $student_id, 'total_marks', $total_marks );
                            break;

		                case 'cgpa':
			                $cgpa = $meta_value != '' ? floatval( $meta_value ) : '0.00';
			                $this->result->update_exam_record_meta( $exam_record_id, $student_id, 'cgpa', $cgpa );
			                break;

		                case 'final_result':
			                $final_result = $meta_value != '' ? strip_tags( trim($meta_value) ) : '';
			                $this->result->update_exam_record_meta( $exam_record_id, $student_id, 'final_result', $final_result );
			                break;

		                case 'final_grade':
			                $final_grade = $meta_value != '' ?  intval( $meta_value ) : '';
			                $this->result->update_exam_record_meta( $exam_record_id, $student_id, 'final_grade', $final_grade );
			                break;

		                case 'total_marks_obtained':
			                $total_marks_obtained = $meta_value != '' ? floatval( $meta_value ) : '';
			                $this->result->update_exam_record_meta( $exam_record_id, $student_id, 'total_marks_obtained', $total_marks_obtained );
			                break;

		                default:
                            $value = $meta_value != '' ? strip_tags( trim($meta_value) ) : '';
			                $this->result->update_exam_record_meta( $exam_record_id, $student_id, $meta_key, $value );
			                break;
                    }

                }
            }

			do_action(RPS_Result_Management::PLUGIN_SLUG . '_marks_table_data_saved', $_POST, $exam_record_id);
            
			//marks=1&student_id=6
			$url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'updated' => '1', 'marks' => $exam_record_id, 'student_id' => $student_id  ), 'admin.php?' ) );
			RPS_Helper_Function::javascript_redirect($url);

		} else {

			$html = "";
			$nonce = wp_nonce_field( 'update_student_marks_nonce_' . $exam_record_id . '_' . $student_id , 'update_student_marks', true, false );

			try {
				$marks_obj = new RPS_Helper_Marks($exam_record_id, $student_id, $this->plugin_slug, $this->page);

			} catch (Exception $ex) {
				die($ex->getMessage());
			}

			ob_start();?>
            <div class='rps_result'>
                <div class='container-fluid'>
                    <?php $marks_obj->exam_table_html(); ?>
                    <form method="post">
                        <?php echo $nonce; ?>
                        <?php $marks_obj->get_marks_html(); ?>
                    </form>
			    </div>
            </div>
            <?php
			$html = ob_get_clean();
			echo $html;

            $marks_obj->get_js();




		}
	}
}
