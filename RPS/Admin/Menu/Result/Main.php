<?php
if(!defined('WPINC')) {
	die();
}

class RPS_Admin_Menu_Result_Main extends RPS_Admin_Menu_MenuAbstract {

	public static function getInstance($slug) {
		if( self::$instance == null ) {

			self::$instance = new self;

			self::$instance->pagehook = $slug;
			self::$instance->page = 'rps_result_results';

			self::$instance->student = new RPS_Helper_Student();
			self::$instance->result = new RPS_Helper_Result();
			self::$instance->course = new RPS_Helper_Course();
		}

		return self::$instance;
	}

	private function delete_cache() {
		$transient = "rps_exam_list_data";
		delete_transient( $transient );
	}

	/**
	 * This function will hold all html related functions
	 */
	public function mainDiv() {
		if( isset($_REQUEST['page']) && $_REQUEST['page'] === $this->page ) {
			ob_start();
			if(isset($_REQUEST['add_new']) ) {
				$this->addNew();
			}
			elseif ( isset ( $_REQUEST['edit'] ) ) {
				$this->edit();
			}
			elseif ( isset($_REQUEST['view_results'] ) ) {
			    $this->viewResults();
			}
			elseif ( isset( $_REQUEST['delete'] ) ) {
				$this->delete();
			}
			elseif ( isset( $_REQUEST['marks'] ) ) {
				RPS_Admin_Menu_Result_Marks::getInstance($this->pagehook);
			}
			elseif ( isset( $_REQUEST['extends'] ) ) {
				do_action(RPS_Result_Management::PLUGIN_SLUG . '_result_entry_list');
			}
			else {
				$this->showExamRecordList();

			}
			$content = ob_get_clean();
			global $wpdb;
			//$wpdb->show_errors();
			//$wpdb->print_error();
			echo $this->getHeader();
			echo $content;
			echo $this->getFooter();
		}
	}

	private function addNew() {
		if ( ! empty( $_POST ) && check_admin_referer( 'create_result_nonce', 'create_result' ) ) {
			global $wpdb;

			$data['exam_id']        = ( isset( $_POST['exam_id'] ) )        ? intval( $_POST['exam_id'] ) : 0;
			$data['department_id']  = ( isset( $_POST['department_id'] ) )  ? intval( $_POST['department_id'] ) : 0;
			$data['batch_id']       = ( isset( $_POST['batch_id'] ) )       ? intval( $_POST['batch_id'] )  : 0;
			$data['semester_id']    = ( isset( $_POST['semester_id'] ) )    ? intval( $_POST['semester_id'] ) : 0;
			$data['display']        = ( isset( $_POST['display'] ) )        ? intval( $_POST['display'] ) : 1;
			$data['active']         = ( isset( $_POST['active'] ) )         ? (int) $_POST['active']  : 1;
			$course_ids             = ( isset( $_POST['course_id'] ) )      ? $_POST['course_id'] : array();
			$student_ids             = ( isset( $_POST['student_ids'] ) )   ? array_map('intval', $_POST['student_ids']) : array();

			$flag = false;
			//check exam_id
			if ( ! $data['exam_id'] ) {
				$this->error['exam_id'] = __('Exam field is required. Please select a exam from list.', $this->TD);
				$flag = true;
			}

			//check department id
			if ( ! $data['department_id'] ) {
				$this->error['department_id'] = __('Department is required. Please select a department from list.', $this->TD);
				$flag = true;
			}

			//check batch_id
			if ( ! $data['batch_id'] ) {
				$this->error['batch_id'] = __( 'Batch id is required. Please select a batch id from list.', $this->TD );
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
					$this->messages[] = __('Exam records already exists on database. Please check Exam, Department and Batch.', $this->TD);
				}
			}

			if ( empty( $student_ids ) ) {
				$this->messages[] = __( 'Please select at least one <strong>Student</strong>.', $this->TD );
			}

			if ( empty( $course_ids ) ) {
				$this->messages[] = __( 'Please Select at least one <strong>Course</strong>.', $this->TD );
			}

			$this->error = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_error', $this->error, $_POST);


			if ( empty( $this->messages ) && empty( $this->error ) ) {
				$data['added'] = time();
				$format = array('%d', '%d', '%d', '%d', '%d', '%d', '%d');


				if ( $wpdb->insert( $wpdb->rps_exam_record, apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_data', $data, $_POST), apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_format', $format, $_POST ) ) ) {
					//now add result table data
					$this->delete_cache();

					$exam_record_id = $wpdb->insert_id;
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


					foreach ( $student_ids as $student_id ) {
						if ( isset($course_ids['student_' . $student_id]) && is_array( $course_ids['student_' . $student_id] ) ) {
							foreach ( $course_ids['student_' . $student_id] as $course_index => $course_id ) {
								$rows[] = "($exam_record_id, $student_id, $course_id, $grade_id)";
							}
						}
					}

					$query .= implode(', ', $rows);

					if( $wpdb->query( $query ) ) {

						$this->messages[] = __("Exam Record Inserted Successfully.", $this->TD);
						$this->showExamRecordList();

					} else {

						//$wpdb->show_errors();
						//$wpdb->print_error();

						$data['student_ids'] = $student_ids;
						$data['course_ids'] = $course_ids;

						$this->formTable($data);
					}
				}
				else {

					//$wpdb->show_errors();
					//$wpdb->print_error();

					$data['student_ids'] = $student_ids;
					$data['course_ids'] = $course_ids;

					$this->formTable($data);
				}
			}
			else {

				$data['student_ids'] = $student_ids;
				$data['course_ids'] = $course_ids;

				$this->formTable($data);

			}
		}
		else {

			$this->formTable();

		}
	}

	private function edit() {
		global $wpdb;
		$id = isset($_GET['edit']) ? (int) stripslashes($_GET['edit']) : '';

		if ( ! empty( $_POST ) && check_admin_referer( 'edit_result_nonce_' . $id, 'edit_result' ) ) {
			$data['display']        = ( isset( $_POST['display'] ) )        ? intval( $_POST['display'] ) : 1;
			$data['active']         = ( isset( $_POST['active'] ) )         ? intval( $_POST['active'] ) : 1;

			$student_ids             = ( isset( $_POST['student_ids'] ) )  ? array_map('intval', $_POST['student_ids']) : array();
			$course_ids             = ( isset( $_POST['course_id'] ) )      ? $_POST['course_id'] : array();

			if ( empty( $student_ids ) ) {
				$this->messages[] = __( 'Please select at least one <strong>Student</strong>.', $this->TD );
			}

			if ( empty( $course_ids ) ) {
				$this->messages[] = __( 'Please Select at least one <strong>Course</strong>.', $this->TD );
			}

			if ( empty($this->messages) && empty($this->error) ) {
				$format = array( '%d', '%d');
				$where = array( 'id' => $id );
				$format_where = array( '%d' );

				$wpdb->update( $wpdb->rps_exam_record, apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_data', $data, $_POST), $where, apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_format', $format, $_POST ), $format_where );

				//delete existing records

				//delete old records if student id doesn't exist anymore
				$stu_ids = implode( ', ', $student_ids );
				$query = $wpdb->prepare("DELETE FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` NOT IN ($stu_ids)",
				array($id));
				//echo $query;
				$wpdb->query( $query );

				//delete individual students courses
				foreach ( $student_ids as $student_id ) {
					if ( isset($course_ids['student_' . $student_id]) && is_array( $course_ids['student_' . $student_id] ) ) {
						$cs_ids = implode( ', ', $course_ids['student_' . $student_id] );
						$query = $wpdb->prepare("DELETE FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d AND `subject_id` NOT IN ($cs_ids)",
						array($id, $student_id));
						//echo $query;
						$wpdb->query( $query );
					}

				}

				$exam_record_id = $id;

				foreach ( $student_ids as $student_id ) {
					if ( isset($course_ids['student_' . $student_id]) && is_array( $course_ids['student_' . $student_id] ) ) {
						foreach ( $course_ids['student_' . $student_id] as $course_id ) {
							//insert if no exits
							$query1 = "INSERT INTO `{$wpdb->rps_marks}` (`exam_record_id`, `student_id`, `subject_id`)
							SELECT * FROM (SELECT $exam_record_id, $student_id, $course_id ) AS tmp
							WHERE NOT EXISTS (
								SELECT id FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = $exam_record_id AND
								`student_id` = $student_id AND `subject_id` = $course_id
							) LIMIT 1;
							";
							//echo $query1;
							$wpdb->query( $query1 );
						}
					}

				}

				$this->delete_cache();

				$url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'updated' => $id ),  admin_url('admin.php?')) );

				RPS_Helper_Function::javascript_redirect($url);

			}

		} else {
			$sql = "SELECT * FROM {$wpdb->rps_exam_record} WHERE id=$id";
			$data = $wpdb->get_row( $sql, ARRAY_A );

			$db_data = $this->result->getStudentIdsByExamRecordId2( $data['id'] );

			if ( !empty($db_data) ) {
				foreach ($db_data as $course_data) {
					$data['student_ids'][] = $course_data['student_id'];
					$data['course_ids']['student_' . $course_data['student_id'] ][] = $course_data['subject_id'];
				}

			} else {
				$data['student_ids'] = array();
				$data['course_ids'] = array();
			}

			$this->formTable( $data );
		}
	}

	private function delete() {
		$exam_record_id = isset($_GET['delete']) ? intval( $_GET['delete'] ) : 0;
		$option = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
        if ( isset($option['user_role']) && $option['user_role'] != ''
            && in_array($option['user_role'], array('manage_options','edit_pages','publish_posts','edit_posts','read')) ) {
            $role = $option['user_role'];
        } else {
            $role = 'manage_options';
        }
		if ( current_user_can($role) && isset($_GET['delete_result']) && wp_verify_nonce($_GET['delete_result'], 'delete_result_' . $exam_record_id)) {
			global $wpdb;
			//first delete all metadata for this exam id
			$query = $wpdb->prepare("DELETE FROM `{$wpdb->rps_exam_record_meta}` WHERE `exam_record_id` = %d", array($exam_record_id));
			$wpdb->query($query);

			//then delete all the marks table data
			$query = $wpdb->prepare("DELETE FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d", array($exam_record_id));
			$wpdb->query($query);

			//then delete result table data
			$query = $wpdb->prepare("DELETE FROM `{$wpdb->rps_exam_record}` WHERE id = %d LIMIT 1", array($exam_record_id));
			$wpdb->query($query);

			//delete transient cache
		    define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
		    RPS_Helper_Function::delete_transient();

			$url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'deleted' => $exam_record_id ),  admin_url('admin.php?')) );

			RPS_Helper_Function::javascript_redirect($url);

		}

	}

	private function viewResults() {
        RPS_Admin_Menu_Result_ViewResults::getInstance('rps_result_results');
	}

	private function formTable($data = array() ) {
		if ( isset($_REQUEST['edit']) && $_REQUEST['edit'] != "" ) {
			$nonce = wp_nonce_field( 'edit_result_nonce_' . $_REQUEST['edit'] , 'edit_result', true, false );
			$readonly = "readonly='readonly'";
			$disabled = "disabled='disabled'";
		}
		else {
			$nonce = wp_nonce_field( 'create_result_nonce' , 'create_result', true, false );
			$readonly = "";
			$disabled = "";
		}
		if ( empty( $data ) ) {
			$data['exam_id'] = '';
			$data['department_id'] = '';
			$data['batch_id'] = '';
			$data['semester_id'] = '';
			//$data['highest_grade'] = '';
			//$data['highest_total_marks'] = '';
			$data['display'] = '1';
			$data['active'] = '1';
			$data['student_ids'] = array();
			$data['course_ids'] = array();
		}

		//get exam id
		$exams = $this->result->getExamID();

		//get departments and batchs
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

		$exam_id = RPS_Helper_Function::is_numeric( $data['exam_id'] ) ? $data['exam_id'] : ( empty( $exams ) ? 0 : $exams[0]['id'] );
		$department_id = RPS_Helper_Function::is_numeric( $data['department_id'] ) ?  $data['department_id'] : ( empty($dept_ids) ? 0 : $dept_ids[0] );
		$semester_id = RPS_Helper_Function::is_numeric($data['semester_id']) ? $data['semester_id'] : ( empty($semesters) ? 0 : key( $semesters[ $department_id ] ) );
		$batch_id = RPS_Helper_Function::is_numeric($data['batch_id']) ? $data['batch_id'] : ( empty($batches) ? 0 : key( $batches[ $department_id ] ) );


		$student_list = new WP_Error( "Empty", __("No Student Found", $this->TD) );
		$course_list = new WP_Error( "Empty", __("No Course Found", $this->TD) );
		$exam_record_data = null;

		//get student list, rps_exam_record table data and rps_marks table data
		if ( $department_id && $batch_id && $semester_id ) {
			$student_list   = $this->student->getStudentDetails( $department_id, $batch_id, $semester_id );
			$course_list    = $this->course->getAllCourses( $department_id, $semester_id );
			if($exam_id) {
				$exam_record_data = $this->result->getExamData( $exam_id, $department_id, $batch_id, $semester_id );
			}
		} elseif ( $department_id && $batch_id ) {
			$student_list = $this->student->getStudentDetails( $department_id, $batch_id );
			$course_list    = $this->course->getAllCourses( $department_id );
			if($exam_id) {
				$exam_record_data = $this->result->getExamData( $exam_id, $department_id, $batch_id );
			}
		}




		?>
		<form method="post" action="">
			<?php echo $nonce; ?>

			<table class="form-table" id="result_table">
				<tbody>

				<!-- Exam ID -->
				<tr class="form-field form-required">
					<th scope="row" valign="top">
						<label for="exam_id"><?php _e('Exam', $this->TD); ?> * </label>
					</th>
					<td>
					<?php
						if ( !empty( $exams ) ) :
							echo '<select name="exam_id" id="exam_id" class="" ' . $disabled  .  '>';
							foreach ( $exams as $exam ) {
								$selected = $exam['id'] == $data['exam_id'] ? 'selected="selected"' : '';
								echo "<option value='{$exam['id']}' $selected>{$exam['name']} - {$exam['exam_year']}</option>";
							}
							echo '</select>';
						else :
							$link = esc_url_raw( add_query_arg(array('page' => 'rps_result_exams', 'add_new' => 1),  admin_url('admin.php?')) );
							echo "<p>" . sprintf(__('Please create a <a href="%s">exam</a> first.', $this->TD), $link  ) . "</p>";
						endif;

					?>
						<p><?php _e('Select Exam From List', $this->TD); ?></p>
						<?php if(isset($this->error['exam_id'])) echo '<p style="color:red;"><strong>' . $this->error['exam_id'] . '</strong></p>';  ?>
					</td>
				</tr>

				<!-- Department Select Box -->
				<tr valign="top">
					<th scope="row">
						<label for="department_id"><?php _e('Department/Class',$this->TD); ?> *</label>
					</th>
					<td>
						<?php if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ): ?>
							<select name="department_id" id="department_id" class="regular-text" <?php echo $disabled ?>>
								<?php
								foreach ($departments as $id => $name):
									echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
								endforeach;
								?>
							</select>

						<?php else:
							echo esc_attr($departments->get_error_message());
						endif; ?>

					</td>
				</tr>

				<!-- Batch Select box -->
				<tr valign="top">
					<th scope="row">
						<label for="batch_id"><?php _e('Batch/Year',$this->TD); ?> * </label>
					</th>
					<td>
						<?php if(!empty($batches)) { ?>
							<select name="batch_id" id="batch_id" class="regular-text" <?php echo $disabled; ?>>
								<?php
								if ( $department_id && RPS_Helper_Function::is_numeric( $department_id ) ) {
									$batch = isset($batches[$department_id]) ? $batches[$department_id] : array();
									if(is_array($batch) && !empty($batch)) {
										foreach ($batch as $key => $name):
											echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
										endforeach;
									}

								}
								?>
							</select>
						<?php } else {
							echo __('No batch/section found.', $this->TD);
						}
						?>
					</td>
				</tr>

				<!-- Semester Select Box -->
				<tr valign="top">
					<th scope="row">
						<label for="semester_id"><?php _e('Semester/Section',$this->TD); ?></label>
					</th>
					<td>
						<?php if ( !empty( $semesters ) ) : ?>
							<select name="semester_id" id="semester_id" class="regular-text" <?php echo $disabled ?>>
							<option><?php _e('Select Semester/Section', $this->TD); ?></option>
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

						<?php else:
							echo __('No semester/section found.', $this->TD);
						endif; ?>
					</td>
				</tr>

				<?php do_action(RPS_Result_Management::PLUGIN_SLUG . '_result_main_form', $data); ?>

				</tbody>
			</table>

			<!-- select student -->
			<div id="student_cours_div" class="rps_result row">
				<?php
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
                                		<td><?php echo '<strong>'; _e('Roll No', $this->TD); echo ':</strong> '. $student_data['roll_no']; ?></td>
									</tr>

								<?php if( ! is_wp_error( $course_list ) && ! empty( $course_list ) ) { ?>
									<tr><td colspan="3"><?php _e('Select Course', $this->TD); ?></td></tr>
									<?php foreach ( $course_list as $course_id => $course_data ) { ?>
										<tr>
											<td>
												<input type="checkbox" name="course_id[student_<?php echo $student_id; ?>][]" value="<?php echo $course_id; ?>" <?php
												if( $disabled === '') { echo 'checked="checked"'; }
												elseif ( isset($data['course_ids']['student_'. $student_id]) && in_array( $course_id, $data['course_ids']['student_'. $student_id] ) ) {
													echo 'checked="checked"';
												}
												?>>
											</td>
											<td><?php echo __( '<strong>Course Code:</strong> ', $this->TD ) . $course_data['course_code']; ?></td>
											<td><?php echo __( '<strong>Course Name:</strong> ', $this->TD)  . $course_data['name'];  ?></td>
										</tr>
									<?php } ?>

								<?php	} else { ?>
									<tr><td colspan="2"><?php _e('No course found.', $this->TD); ?></td></tr>
								<?php } ?>
                                </tbody>
									</table>
							</div>
							<?php
						}
					else: ?>
						<div class="panel panel-default">
						  <div class="panel-body">
							<?php _e('No Students found.', $this->TD); ?>
						  </div>
						</div>
				<?php endif; ?>
			</div>

			<table class="form-table">
				<tbody>
				<tr class="form-field form-required">
					<th scope="row" valign="top">
						<label for="display"><?php _e('Display on frontend Search Result', $this->TD); ?></label>
					</th>
					<td>
						<select name="display" id="display" class="postform">
							<option value='1' <?php selected($data['display'], "1"); ?>><?php _e('Show', $this->TD); ?></option>
							<option value='2' <?php selected($data['display'], "2");  ?>><?php _e('Hide', $this->TD); ?></option>
						</select>
						<p><?php _e('Show/Hide exam on frontend search result.', $this->TD); ?></p>
						<?php if(isset($this->error['enable'])) echo '<p style="color:red;"><strong>' . $this->error['enable'] . '</strong></p>';  ?>
					</td>
				</tr>
				<!--
				<tr class="form-field">
					<th scope="row" valign="top">
						<label for="active"><?php _e('Active', $this->TD); ?></label>
					</th>
					<td>
						<select name="active" id="active" class="postform">
							<option value='1' <?php selected($data['active'], "1"); ?>><?php _e('Active', $this->TD); ?></option>
							<option value='2' <?php selected($data['active'], "2");  ?>><?php _e('Inactive', $this->TD); ?></option>
						</select>
						<p><?php _e('If status is <strong>Active</strong>, you will view this grade to add result page. If status is <strong>Inactive</strong> this grade will be hidden.', $this->TD);  ?></p>
						<?php if(isset($this->error['active'])) echo '<p style="color:red;"><strong>' . $this->error['active'] . '</strong></p>';  ?>
					</td>
				</tr>
				-->
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php if(isset($_REQUEST['edit'])) { echo __("Update Exam Record", $this->TD); } else { echo  __("Add New Exam Record", $this->TD); } ?>"/>
				<?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) . '</a>';  ?>
			</p>

		</form>

		<script type="text/javascript">
				var semesters = <?php echo json_encode($semesters) ?>;
				var batches = <?php echo json_encode($batches) ?>;
				var edit_result = <?php
				if( $disabled != '' ) {
					echo 'true';
				} else {
					echo 'false';
				} ?>;
		</script>
		<?php
	}


	private function showExamRecordList() {
		$table = new RPS_Admin_Menu_Result_MainTable();
		//Fetch, prepare, sort, and filter our data...
		$table->prepare_items();
		?>
		<form id="movies-filter" method="get">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<!-- Now we can render the completed list table -->
			<?php $table->display() ?>
		</form>
		<?php
	}

	private function getHeader() {
		global $wpdb;

		$link = ' <a href="' . esc_url_raw( add_query_arg( array('add_new' => '1', 'page' => $this->page ),  admin_url('admin.php?') ) ) .'" class="add-new-h2">' . __('Add New', $this->TD) . '</a>';

		if ( isset( $_REQUEST['add_new'] ) && $_REQUEST['add_new'] != "" ) {
			$title = __("Add New Exam Record", $this->TD);

		} elseif ( isset( $_REQUEST['edit'] ) && $_REQUEST['edit'] != "") {
			$title = __("Edit Exam Record", $this->TD);

		} elseif ( isset($_REQUEST['marks'], $_REQUEST['student_id']) && RPS_Helper_Function::is_numeric($_REQUEST['marks']) && RPS_Helper_Function::is_numeric($_REQUEST['student_id']) ) {
			$title = __('Add Marks', $this->TD);

		} elseif ( isset($_REQUEST['marks'] ) && RPS_Helper_Function::is_numeric($_REQUEST['marks']) ) {
		    $link2 = ' <a href="' . esc_url_raw( add_query_arg( array('page' => $this->page, 'view_results' => intval($_REQUEST['marks']) ),  admin_url('admin.php?') ) ) .'" class="add-new-h2">' . __('View Results', $this->TD) . '</a>';
			$title = __('Student List', $this->TD) . $link2;

		} elseif ( isset($_REQUEST['view_results'] ) && RPS_Helper_Function::is_numeric($_REQUEST['view_results']) ) {
			$title = __('Results', $this->TD);

		} else {

		$title = __( 'Exam Record List', $this->TD ) . $link ;

		$title = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_title', $title);

		}
		?>
		<div class="wrap container-fluid">
		<div id="icon-edit" class="icon32 icon32-posts-post">&nbsp;</div>
		<h2><?php echo $title; ?></h2>
		<br class="clear">
		<?php
		$str = '';
		if( !empty( $this->messages) ) {

			foreach ($this->messages as $key => $msg):
				$str .= '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
				$str .= "<p>$msg </p>";
				$str .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
			endforeach;

		}
		?>
		<?php echo $str; ?>
		<?php
		if( isset($_REQUEST['updated']) ) {
			echo '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
			echo __('<p>Updated</p>', $this->TD);
			echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
		}
		if( isset($_REQUEST['deleted']) ) {
			echo '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
			echo __('<p>Exam Deleted Successfully.</p>', $this->TD);
			echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
		}

		do_action(RPS_Result_Management::PLUGIN_SLUG . '_result_message');

		?>

		<?php
	}

	private function getFooter() {
		echo "</div>";
	}

	/**
	 * This function will load all required css, js and other function on wp hook
	 */
	public function onLoadPage() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles' ) );
	}

	public function load_scripts() {
		if( isset($_REQUEST['student_id'], $_REQUEST['marks']) || (isset($_REQUEST['extends']) && $_REQUEST['extends'] == '1' ) ) {

		}
		elseif( !isset($_REQUEST['extends']) ) {
		    wp_enqueue_script( 'rps_result' );
	        // Localize Scripts
            wp_localize_script(
                'rps_result', "result",
                array (
                    'nonce' => wp_create_nonce('rps_result_load_data'),
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                )
            );
		}

	}

	public function load_styles() {
		//load bootstrap css
		wp_enqueue_style( 'rps_bootstrap' );
		wp_enqueue_script('rps_bootstrap');
		add_action('admin_footer', array( $this, 'wpFooter' ));
	}

	public function wpFooter() {

        ?>
        <style type="text/css" rel="stylesheet">
            .wp-list-table th#sl {
                width: 50px;
            }
            a.action_button {
            	margin-bottom: 5px !important;
            }
            @media (min-width: 768px) {
            	.modal-dialog {
            		margin: 5% auto !important;
            	}
            }

            .modal-dialog {
            	margin: 5% auto !important;
            }

        </style>

        <script type="application/javascript">
        	jQuery(function($){
        		var exam_id = 0;

				$('.delete_marks').click(function() {
					var th = $(this);
					exam_id = th.data('exam_id');
					var str = 'Are you sure you want to delete <strong>' + th.data('exam_name') +'</strong> ? All result data associated with this result will be deleted.'
					$('#deleteResultModalBody').html(str);
					$('#deleteResultModal').modal('show');

					return false;
				});
				$('#deleteResultModal').on('click', '#confirmDeleteResult', function() {
					var url = $('a.exam_record_id_'+ exam_id).first().attr('href');
					exam_id = null;
					window.location = url;
					//console.log(url);
				});
        	});
		</script>
		<div class="rps_result">
			<div class="modal fade" tabindex="-1" role="dialog" id="deleteResultModal">
			  <div class="modal-dialog" role="document">
				<div class="modal-content">
				  <div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Delete Result</h4>
				  </div>
				  <div class="modal-body">
					<p id="deleteResultModalBody"></p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="confirmDeleteResult">Delete</button>
				  </div>
				</div><!-- /.modal-content -->
			  </div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
		</div>
        <?php
    }

}
