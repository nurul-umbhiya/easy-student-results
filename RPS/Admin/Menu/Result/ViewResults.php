<?php
if( ! defined('WPINC') ) {
	die();
}

final class RPS_Admin_Menu_Result_ViewResults {

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
		if( isset($_REQUEST['page'], $_REQUEST['view_results']) && $_REQUEST['page'] === $this->page ) {
			$this->getExamRecord();
			ob_start();

			if ( isset ( $_REQUEST['view_results'] ) && RPS_Helper_Function::is_numeric( $_REQUEST['view_results'] ) ) {
				$this->view_results( $_REQUEST['view_results'] );
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
		$exam_id = isset( $_REQUEST['view_results'] ) ? intval( $_REQUEST['view_results'] ) : 0;

		if ( RPS_Helper_Function::is_numeric( $exam_id ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `id` = %d LIMIT 1", array( $exam_id ) );

			$this->exam_record = $wpdb->get_row( $query, ARRAY_A );
		} else {
			$this->exam_record = null;
		}
	}

	private function view_results( $exam_record_id ) {
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
		$table = new RPS_Admin_Menu_Result_ViewResultsTable(  $exam_record_id, $this->exam_record['department_id'], $this->exam_record['batch_id'], $this->exam_record['semester_id'] );
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

			<p class="submit">
				<?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) . '</a>';  ?>
			</p>
		</form>
		<?php
	}
}
