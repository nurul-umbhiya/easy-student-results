<?php
if(!defined('WPINC')) {
	die();
}

class RPS_Admin_Menu_Result_New extends RPS_Admin_Menu_MenuAbstract {

	public static function getInstance($slug) {
		if( self::$instance == null ) {

			self::$instance = new self;

			self::$instance->pagehook = $slug;
			self::$instance->page = 'rps_result_results_new';

			self::$instance->student = new RPS_Helper_Student();
			self::$instance->result = new RPS_Helper_Result();
			self::$instance->course = new RPS_Helper_Course();
		}

		return self::$instance;
	}

	/**
	 * This function will hold all html related functions
	 */
	public function mainDiv() {
		if( isset($_REQUEST['page']) && $_REQUEST['page'] === $this->page ) {
			ob_start();
			$this->formTable();
			$content = ob_get_clean();
			global $wpdb;
			//$wpdb->show_errors();
			//$wpdb->print_error();
			echo $this->getHeader();
			echo $content;
			echo $this->getFooter();
		}
	}

	private function formTable($data = array() ) {
		$nonce = wp_nonce_field( 'create_result_nonce' , 'create_result', true, false );
		$readonly = "";
		$disabled = "";

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
							echo '<select name="exam_id" id="exam_id" class="" ' . $disabled  .  '><option value="">Select Exam</option>';
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
					</td>
				</tr>

				<!-- Department Select Box -->
				<tr valign="top">
					<th scope="row">
						<label for="department_id"><?php _e('Class',$this->TD); ?> *</label>
					</th>
					<td>
						<?php if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ): ?>
							<select name="department_id" id="department_id" class="regular-text" <?php echo $disabled ?>>
							    <option value="">Select Class</option>
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
							    <option value="">Select Batch/Year</option>
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
						<label for="semester_id"><?php _e('Term',$this->TD); ?></label>
					</th>
					<td>
						<?php if ( !empty( $semesters ) ) : ?>
							<select name="semester_id" id="semester_id" class="regular-text" <?php echo $disabled ?>>
							<option><?php _e('Select Term', $this->TD); ?></option>
								<?php
								if( $department_id != '' && RPS_Helper_Function::is_numeric($department_id) ) {
									$semester = isset($semesters[$department_id]) ? $semesters[$department_id] : array();
									if( is_array($semester) && !empty($semester) ) {
										foreach ($semester as $id => $name):
											echo "<option value='{$id}'>{$name}</option>";
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

				<tr valign="top">
					<th scope="row">
						<label><?php _e('Subjects',$this->TD); ?></label>
					</th>
					<td>
					    <div id="subject_div"><?php echo __('No subject is selected.', $this->TD); ?></div>
					</td>
				</tr>

				</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit_btn" class="button button-primary" value="<?php if(isset($_REQUEST['edit'])) { echo __("Update Exam Record", $this->TD); } else { echo  __("Add New Exam Record", $this->TD); } ?>" disabled="disabled"/>
				<?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) . '</a>';  ?>
			</p>

		</form>

		<script type="text/javascript">
				var semesters = <?php echo json_encode($semesters) ?>;
				var batches = <?php echo json_encode($batches) ?>;
		</script>
		<?php
	}

	private function getHeader() {

		$title = __("Add New Exam", $this->TD);
		?>
		<div class="wrap container-fluid">
		<div id="icon-edit" class="icon32 icon32-posts-post">&nbsp</div>
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
		echo $str;
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
		wp_enqueue_script('rps_bootstrap');
	}

	public function load_styles() {
		//load bootstrap css
		wp_enqueue_style( 'rps_bootstrap' );
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

        		$('#result_table').on('change', '#department_id', function() {
                    change_batch_semester();
                    change_student_course();
                });

        		$('#result_table').on('change', '#semester_id, #batch_id', function() {
                    change_student_course();
                });

                function change_batch_semester() {

                    var id = $('#department_id').val();

                    // Popupate semester dropdown box
                    var i = typeof semesters != 'undefined' ? semesters[id] : null;
                    //console.log(i);
                    $('#semester_id option').remove();

                    $('#semester_id')
                        .append($("<option></option>")
                        .attr("value",'')
                        .text('Select Term'));


                    if (i !== null) {
                        $.each(i, function (key, value) {
                            //populate semester dropdown box
                            $('#semester_id')
                                .append($("<option></option>")
                                    .attr("value", key)
                                    .text(value));

                        });
                    }

                    //populate batch dropdown box
                    var j = typeof batches != 'undefined' ? batches[id] : null;
                    //console.log(j);
                    $('#batch_id option').remove();

                    // Add option to the select box from list
                    if (j !== null) {
                        $.each(j, function (key, value) {
                            $('#batch_id')
                                .append($("<option></option>")
                                    .attr("value", key)
                                    .text(value));
                        });
                    }
                }
                change_batch_semester();

                function change_student_course() {
                    var department_id = $('#department_id').val();
                    var batch_id = $('#batch_id').val();
                    var semester_id = $('#semester_id').val();

                    //call ajax
                    jQuery.ajax({
                        type : "post",
                        dataType : "json",
                        url : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data : {action: "rps_result_load_courses", department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: '<?php echo wp_create_nonce('rps_result_load_courses');  ?>'},
                        success: function(response) {
                            if(response.type == "success") {

                                if ( response.data && response.data != '' ) {
                                    $('#submit_btn').prop('disabled', false);
                                    $('#subject_div').html( response.data );
                                }
                                else {
                                    $('#submit_btn').prop('disabled', true);
                                    $('#subject_div').html( 'No subject is selected.' );
                                }
                            }
                            else {
                                alert("Invalid Request.")
                            }
                        }
                    })

                }
                change_student_course();

				$('body').on('click', '#submit_btn', function(e) {
				    e.preventDefault();
					var th = $(this);


					var exam_id = $('#exam_id').val();
					var department_id = $('#department_id').val();
                    var batch_id = $('#batch_id').val();
                    var semester_id = $('#semester_id').val();

                    var subjects = [];
                    $.each($('input[name="course_id[]"]:checked'), function(){
                        subjects.push($(this).val());
                    });

                    $('#addNewResultModal').modal('show');
                    $('#addNewResultModalBody').html('<p>Checking for existing result. Please wait...</p>');

                    if ( subjects.length == 0 ) {
                        $('#addNewResultModalBody').html('<p>Please select some subjects to continue.</p>');
                        return false;
                    }

					//call ajax
                    jQuery.ajax({
                        type : "post",
                        dataType : "json",
                        url : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data : {action: "rps_result_check_existing_results", exam_id: exam_id, department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: '<?php echo wp_create_nonce('rps_result_check_existing_results');  ?>'},
                        success: function(response) {
                            if(response.type == "success") {
                                if ( response.students.length > 0 && response.exam_record_id > 0 ) {
                                    var str = '<div class="alert alert-info" role="alert">Adding Result... Please don\'t close this window until the task is completed..</div>';
                                    $('#addNewResultModalBody').html( str + $('#progressbar_html').html());
                                    add_student_results(0, response.students, response.exam_record_id, subjects);
                                }
                                else {
                                    $('#addNewResultModalBody').html('<p>No Student Found or Database Error. Please check your input</p>');
                                }
                            }
                            else if( response.errors ) {
                                var str = '';
                                $.each(response.errors, function(index, error) {
                                    str += '<p>' + error + '</p>';
                                });
                                $('#addNewResultModalBody').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                            }
                            else {
                                alert("Invalid Request.")
                            }
                        }
                    });
					return false;
				});

				function add_student_results(index, students, exam_record_id, subjects) {
                            if ( typeof exam_record_id !== 'undefined' && typeof index !== 'undefined') {
                                $.ajax({
                                    dataType : "json",
                                    type : 'POST',
                                    url : '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                                    data: {action: 'rps_result_add_student_results', nonce: '<?php echo wp_create_nonce('rps_result_add_student_results') ?>', exam_record_id: exam_record_id, student_id: students[index], subjects: subjects},
                                    success: function(response) {
                                        //console.log(response);
                                        if ( response.errors ) {
                                            var str = '';
                                            $.each(response.errors, function(index, error) {
                                                str += '<p>' + error + '</p>';
                                            });
                                            $('#addNewResultModalBody').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                                        }
                                        else {
                                            index++;
                                            var total = (index/students.length)*100;
                                            $("#total_percent").html(Math.floor(total));
                                            $("#progress_success").css("width", total + "%");
                                            if (index != students.length) {
                                                add_student_results(index, students, exam_record_id, subjects);
                                            } else {
                                                if ( index == students.length ) {
                                                    //show success message
                                                    var success_msg = '<div class="alert alert-success alert-dismissible" role="alert">' +
                                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                                        'Result Added successfully.</div>';
                                                    $('#addNewResultModalBody').html(success_msg);
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
        	});
		</script>
		<div class="rps_result">
			<div class="modal fade" tabindex="-1" role="dialog" id="addNewResultModal">
			  <div class="modal-dialog" role="document">
				<div class="modal-content">
				  <div class="modal-header">
					<h4 class="modal-title">Add New Result</h4>
				  </div>
				  <div class="modal-body" id="addNewResultModalBody"></div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				  </div>
				</div><!-- /.modal-content -->
			  </div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
		</div>

        <script type="text/template" id="progressbar_html">
            <div><span class="total_percent" id="total_percent">0</span>% Complete</div>
            <div class="progress">
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success progress_success" id="progress_success"></div>
                </div>
            </div>
        </script>
        <?php
    }

}
