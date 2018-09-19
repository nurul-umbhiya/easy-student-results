<?php
if( ! defined( 'WPINC' ) ) {
	die();
}

class RPS_Helper_Marks {
	protected $pagehook, $TD;
	protected $page, $plugin_slug;
	protected $exam_record_data, $student_data, $result_data, $marks_data, $course, $result_metadata;
	protected $exam_record_id, $student_id;
	protected $marks_table_headings, $results_table_headings;
	protected $total_marks;
	protected $option_general;

	public function __construct( $exam_record_id, $student_id = null, $slug = '', $page = '' ) {

		$this->pagehook = $slug;
		$this->page     = $page;

		$this->TD =  RPS_Result_Management::TD;
		$this->plugin_slug = RPS_Result_Management::PLUGIN_SLUG;

		$this->student = new RPS_Helper_Student();
		$this->result = new RPS_Helper_Result();
		$this->course = new RPS_Helper_Course();
		$this->dbs = new RPS_Helper_DBS();

		$this->option_general = get_option(RPS_Result_Management::PLUGIN_SLUG .'_basics');

		$this->marks_table_headings = $this->results_table_headings = array();
		$this->result_data = $this->marks_data = $this->result_metadata =  array();

		$this->total_marks = 0;

		$this->marks_table_headings();
		$this->results_table_headings();

		if ( !RPS_Helper_Function::is_numeric($exam_record_id) ) {
			throw new Exception(__('Invalid Exam Record ID. Please Check your input values.', $this->TD) );
		} else {
			$this->exam_record_id = $exam_record_id;
			$this->get_exam_record_data();
		}

		if ( $student_id !== null && !RPS_Helper_Function::is_numeric($student_id) ) {
			throw new Exception(__('Invalid Student ID. Please check your input values.', $this->TD));
		} else {
			$this->student_id = $student_id;
			$this->get_student_info();
			$this->get_marks();
			$this->get_metadata();
		}
	}

	public function get_exam_record_data() {
		global $wpdb;
		$query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_exam_record} WHERE id= %d", array($this->exam_record_id));
		$this->exam_record_data = $wpdb->get_row($query,ARRAY_A);
	}

	public function get_marks() {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d", array( $this->exam_record_id, $this->student_id )  );
		$this->marks_data = $wpdb->get_results( $query, ARRAY_A );
	}

	public function get_metadata() {
		$this->result_metadata = $this->result->get_exam_record_meta( $this->exam_record_id, $this->student_id );
	}
	
	public function get_student_info() {
	    $this->student_data = $this->student->getStudentInfo($this->exam_record_data['department_id'], $this->exam_record_data['batch_id'], $this->exam_record_data['semester_id'], $this->student_id, 'all');
    }
    
    public function exam_table_html() {
	    global $wpdb;
	    $query = $wpdb->prepare("SELECT `name`, `exam_year` FROM {$wpdb->rps_exam} WHERE id= %d", array($this->exam_record_data['exam_id']));
	    $exam_name = $wpdb->get_row($query,ARRAY_A);
	    $exam_name = isset($exam_name['name']) ? $exam_name['name'] . ' - ' . $exam_name['exam_year'] : '';

	    $info = $this->dbs->getDepartmentInfo($this->exam_record_data['department_id']);
	    $dept_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];

	    $info = $this->dbs->getBatchInfo( $this->exam_record_data['department_id'], $this->exam_record_data['batch_id'] );
	    $batch_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];

	    if ( RPS_Helper_Function::is_numeric($this->exam_record_data['department_id']) && RPS_Helper_Function::is_numeric($this->exam_record_data['semester_id']) ) {
		    $info           = $this->dbs->getSemesterInfo($this->exam_record_data['department_id'], $this->exam_record_data['semester_id']);
		    $semester_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];
	    } else {
		    $semester_name = __('N/A', $this->TD);
	    }
	    
	?>
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
        
    <?php    
    }
	
	public function marks_table_headings() {
		//Table Headings
		$this->marks_table_headings = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_marks_table_headings', array(
			'sl'                => __('SL', $this->TD),
			'course_name'       => __('Subject Name', $this->TD),
			'course_code'       => __('Subject Code', $this->TD),
			'total_marks'       => __('Total Marks', $this->TD),
			'marks_obtained'    => __('Marks Obtained', $this->TD),
			'percentage'        => __('Percentage', $this->TD),
			'grade'             => __('Grade', $this->TD),
			'grade_point'       => __('Grade Point', $this->TD)
		));
	}

	public function marks_table_header( ) {
	?>
		<table class="table table-striped table-bordered marks_table">
			<thead>
				<tr>
			<?php if (!empty($this->marks_table_headings)) {
				foreach ($this->marks_table_headings as $field_id => $field_value) {
					if ( $field_id == 'sl' ) {
						echo '<th scope="col" class="manage-column column-' . $field_id . '" style="width:50px;">' . $field_value . '</th>';
					}
					else {
						echo '<th scope="col" class="manage-column column-'. $field_id .'">' . $field_value . '</th>';
					}
				}
			} ?>
				</tr>
			</thead>
			<tbody>
	<?php
	}

	public function marks_table_body() {

		if( is_array( $this->marks_data ) && !empty( $this->marks_data ) ) {
			$department_id  = isset($this->exam_record_data['department_id']) ? $this->exam_record_data['department_id'] : 0;
			$batch_id       = isset($this->exam_record_data['batch_id']) ? $this->exam_record_data['batch_id'] : 0;
			$semester_id    = isset($this->exam_record_data['semester_id']) ? $this->exam_record_data['semester_id'] : 0;
			$i = 0;
			foreach ( $this->marks_data as $row ) {

				//fix percentace
				if ( !array_key_exists('percentage', $row) ) {
					$row['percentage'] = 0;
				}

				//get course info
				$course_info = $this->course->getCourseDetails( $department_id, $row['subject_id'], $semester_id );
				if( is_wp_error($course_info) ) {
					$course_info = $this->course->getCourseInfo( $row['subject_id'] );
				}
				$i++;

				if ( isset($this->option_general['marks_js']) && $this->option_general['marks_js'] == 'on' ) {
					if ( isset ( $course_info['total_marks'] ) && $course_info['total_marks'] != '' ) {
						$this->total_marks += floatval( $course_info['total_marks'] );
					}
				}

				$grades = $this->result->getGradeList();
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


				echo "<tr>";
				foreach ($this->marks_table_headings as $field_id => $field_value) {
				    echo '<td>';
					switch ($field_id) {
						case 'sl':
							echo "<strong>{$i}</strong>";
							break;

						case 'course_name':
							$course_name = isset ( $course_info['name'] ) ? $course_info['name'] : '';
							echo "{$course_name}";
							break;

						case 'course_code':
							$course_code = isset ( $course_info['course_code'] ) && $course_info['course_code'] != '' ? $course_info['course_code'] : 'N/A';
							echo "{$course_code}";
							break;

						case 'total_marks':
							$total = isset ( $course_info['total_marks'] ) && $course_info['total_marks'] != '' ? $course_info['total_marks'] : 'N/A';
							echo "{$total}<input type='hidden' class='total_marks' value='{$total}' />";
							break;

						case 'marks_obtained':
							echo "<input type='text' name='results[{$row['id']}][marks_obtained]' style='width:90px;' class='form-control rps_marks' value='{$row['marks_obtained']}'>";
							break;

						case 'percentage':
							echo "<input type='text' name='results[{$row['id']}][percentage]' style='width:90px;' class='form-control percentage' value='{$row['percentage']}'>";
							break;

						case 'grade':
							echo "<select class='grades form-control' name='results[{$row['id']}][grade]' style='min-width: 70px;'>{$grade_options}</select>";
							break;

						case 'grade_point':
							echo "<input type='text' readonly='readonly' class='form-control rps_grade' style='width:90px;'>";
							break;

						default:
							do_action(RPS_Result_Management::PLUGIN_SLUG . '_marks_table_data', $field_id, $row);
							break;
					}
					echo '</td>';
				}
				echo "</tr>";
			}
		}
	}

	public function marks_table_footer( ) {
		echo '</tbody></table>';
	}

	public function results_table_headings() {
		//Table Headings
		$this->results_table_headings = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_results_table_headings', array(
			'total_marks'       => __('Total Marks', $this->TD),
			'total_marks_obtained' => __( 'Marks Obtained', $this->TD ),
			'total_percentage'  => __('Percentage', $this->TD),
			'final_grade'       => __('Final Grade', $this->TD),
			'cgpa'              => __( 'CGPA', $this->TD ),
			'result'            => __( 'Final Result', $this->TD )
		));
	}

	public function results_table_header( ) {
		?>
		<table class="table table-striped table-bordered results_table">
		<thead>
		<tr>
			<?php if (!empty($this->results_table_headings)) {
				foreach ($this->results_table_headings as $field_id => $field_value) {
					if ( $field_id == 'sl' ) {
						echo '<th scope="col" class="manage-column column-' . $field_id . '" style="width:50px;">' . $field_value . '</th>';
					}
					else {
						echo '<th scope="col" class="manage-column column-'. $field_id .'">' . $field_value . '</th>';
					}
				}
			} ?>
		</tr>
		</thead>
		<tbody>
		<?php
	}

	public function results_table_body( ) {
		//Table Headings
		$lbl_total_marks    = __('Total Marks', $this->TD);
		$lbl_total_marks_obtained = __( 'Marks Obtained', $this->TD );


		if ( !empty($this->results_table_headings) ) {
			echo "<tr>";

			foreach ( $this->results_table_headings as $field_id => $field_value ) {
				echo "<td>";
				switch ($field_id) {
					case 'total_marks':
						$total_marks = isset( $this->result_metadata['total_marks'] ) ? floatval($this->result_metadata['total_marks']) : 0.00;
						if ( $total_marks == '' || $total_marks == 0 || floatval($total_marks) < floatval($this->total_marks) ) {
							$total_marks = $this->total_marks;
						}
						echo '<input name="metadata[total_marks]" type="text" placeholder="' . $lbl_total_marks . '" class="form-control total_marks" style="width:70px;" value="' . $total_marks . '"/>';
						break;

					case 'total_marks_obtained':
						$total_marks_obtained = isset( $this->result_metadata['total_marks_obtained'] ) ? floatval( $this->result_metadata['total_marks_obtained'] ) : '';
						echo '<input name="metadata[total_marks_obtained]" type="text" placeholder="' . $lbl_total_marks_obtained . '" class="form-control total_marks_obtained" style="width:90px;" value="' . $total_marks_obtained .'" />';
						break;

					case 'total_percentage':
						$total_percentage = isset( $this->result_metadata['total_percentage'] ) ? esc_attr($this->result_metadata['total_percentage']) : "";
						echo '<input name="metadata[total_percentage]" type="text" placeholder="eg: 80" value="' . $total_percentage .'" class="form-control total_percentage" style="width:90px;" />';
						break;

					case 'final_grade':
						$final_grade = isset( $this->result_metadata['final_grade'] ) ? intval($this->result_metadata['final_grade']) : "";

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
						echo '<select name="metadata[final_grade]" class="final_grade form-control" style="min-width: 50px;">' . $grade_options . '</select>';
						//echo '<input name="metadata[final_grade]" id="final_grade" type="text" value="' . $final_grade .'" class="small-text" />';
						break;

					case 'cgpa':
						$cgpa = isset( $this->result_metadata['cgpa'] ) ? floatval($this->result_metadata['cgpa']) : "";
						echo '<input style="width:90px;" name="metadata[cgpa]" type="text" placeholder="eg: 3.16" value="' . $cgpa .'" class="form-control cgpa" />';
						break;

					case 'result':
						$final_result = isset( $this->result_metadata['final_result'] ) ? esc_attr($this->result_metadata['final_result']) : "";
						echo '<input name="metadata[final_result]" type="text" placeholder="eg: passed" value="' . $final_result .'" class="form-control final_result" style="width:120px;" />';
						break;

					default:
                        do_action(RPS_Result_Management::PLUGIN_SLUG . '_results_table_data', $field_id, $this->result_metadata);
						break;
				}
				echo "</td>";
			}

			echo "</tr>";
		}
		else {

		}
	}

	public function results_table_footer( ) {
		echo '</tbody></table>';
	}

	public function get_submit_button() {
		$back_url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'marks' => $this->exam_record_id ), 'admin.php' ) );
		$lbl_update = __( 'Update', $this->TD );
		$lbl_back = __( 'Back', $this->TD );
		echo <<<EOD
		<table class="table" style="border: 0;">
			<tr>
				<td style="border: 0;"><input type="submit" class="button button-primary" value="{$lbl_update}" />
				<a href="{$back_url}" class="button button-secondary">{$lbl_back}</a></td>
				<td></td>
			</tr>
		</table>

EOD;

	}

	public function get_js() {
		$grade_points = $this->result->getGradePointList();
		if( !is_wp_error($grade_points) ) {
			$grade_points = json_encode( $grade_points );
		} else {
			$grade_points = json_encode( array() );
		}

		echo <<<EOD
		<script type="text/javascript">
		jQuery(function($){
			var grade_points = $grade_points;
			$('.marks_table').on('change', '.grades', function () {
                var grade = $(this).val();
                var grade_point = change_grade_point(grade);
                $(this).parent().parent().find('.rps_grade').val(grade_point);
            });
        
            function change_grade_point(grade) {
                //console.log(grades);
                var ret = "";
                if (grade_points) {
                    $.each( grade_points, function(index, value) {
                        //console.log(index + ' ' + value);
                        if(index == grade) {
                            ret = value;
                            return;
                        }
                    });
                }
                return ret;
            }
        
            function change_all_grade_point() {
                var grade_class = $('.grades');
                $.each(grade_class, function(a,b) {
                    var grade = $(this).val();
                    var grade_point = change_grade_point(grade);
                    //console.log(grade + ' ' + grade_point);
                    $(this).parent().parent().find('.rps_grade').val(grade_point);
                });
            }
        
            change_all_grade_point();
        });    
		</script>
EOD;

		//get total marks js
		if ( isset($this->option_general['marks_js']) && $this->option_general['marks_js'] == 'on' ) {
			echo <<<EOD
		<script type="text/javascript">
		    jQuery(function($){
		        $('.marks_table').on('change', '.rps_marks', function () {
                    var marks = $(this).parent().parent().parent().find('.rps_marks');
                    var total = 0.00;
                    $.each( marks, function(index, value) {
                        total += parseFloat($(this).val());
                    });
            
                    $('table.results_table').find('.total_marks_obtained').first().val(total);
                });
		    });
		</script>
EOD;
		}


        //get percentage js
		if ( isset($this->option_general['percentage_js']) && $this->option_general['percentage_js'] == 'on' ) {
			echo <<<EOD
		<script type="text/javascript">
		jQuery(function($){
		    function calculate_percentage() {
                var table = $('.marks_table');
                var out_of_marks = 0;
                var scored_marks = 0;
                //var th = this;
                var tr = table.find('tr');
        
                $.each(tr, function() {
                    var total_marks = parseFloat( $(this).find('input.total_marks').val() );
                    if ( !isNaN(total_marks) ) {
                        var marks_obtain = parseFloat( $(this).find('.rps_marks').val() );
                        out_of_marks += total_marks;
                        scored_marks += marks_obtain;
    
                        //calculate percentage
                        var percent = ( marks_obtain / total_marks ) * 100;
                        percent = parseFloat( Math.round( percent ) ).toFixed(2);
                        $(this).find('.percentage').first().val(percent);
                    }
                });
    
                //overall percentage
                var num = (scored_marks / out_of_marks) * 100;
                num = parseFloat(Math.round(num * 100) / 100).toFixed(2);
                $('table.results_table').find('.total_percentage').first().val(num);
            }
            calculate_percentage();
        
            $('.marks_table').on('change', '.rps_marks', function () {
        
                var out_of_marks = 0;
                var scored_marks = 0;
        
                var tr = $(this).closest('tbody').find('tr');
                var th = $(this);
        
                $.each(tr, function() {
                    var total_marks = parseFloat( $(this).find('input.total_marks').val() );
                    if ( !isNaN(total_marks) ) {
                        var marks_obtain = parseFloat( $(this).find('.rps_marks').val() );
                        out_of_marks += total_marks;
                        scored_marks += marks_obtain;
        
                        //calculate percentage
                        var percent = ( marks_obtain / total_marks ) * 100;
                        percent = parseFloat( Math.round( percent ) ).toFixed(2);
                        $(this).find('.percentage').first().val(percent);
                    }
                });
        
                var num = (scored_marks / out_of_marks) * 100;
                num = parseFloat(Math.round(num * 100) / 100).toFixed(2);
                //console.log(num);
                //$(this).closest('table').next('table.results_table').first().find('.total_percentage').val(num);
                $('table.results_table').find('.total_percentage').first().val(num);
                
        
            });
            
        });
		</script>
EOD;
		}

	}

	public function get_header( ) {
		echo "<div class='rps_result'><div class='container-fluid table-responsive'>";
	}

	public function get_footer( ) {
		echo "</div></div>";
	}

	public function get_marks_html() {
		//student info headings
		$lbl_student_name    = '<strong>' . __('Student Name', $this->TD) . '</strong>';
		$lbl_roll_number    = '<strong>' . __('Roll Number', $this->TD) . '</strong>';
		$lbl_registration_number = '<strong>' . __('Registration Number', $this->TD) . '</strong>';
		$name = isset($this->student_data['name']) ? $this->student_data['name'] : '';
		$roll = isset($this->student_data['roll_no']) ? $this->student_data['roll_no'] : '';
		$reg_no = isset($this->student_data['registration_no']) ? $this->student_data['registration_no'] : '';
		?>
        <div class="panel panel-default">
            <table class="table">
                <tr>
                    <td><?php echo $lbl_student_name; ?></td>
                    <td><?php echo $name; ?></td>
                    <td><?php echo $lbl_roll_number; ?></td>
                    <td><?php echo $roll; ?></td>
                    <td><?php echo $lbl_registration_number; ?></td>
                    <td><?php echo $reg_no; ?></td>
                </tr>
            </table>
        </div>

        <div class="panel panel-default">
            <div class="table-responsive">
                <?php
                    $this->marks_table_header();
                    $this->marks_table_body();
                    $this->marks_table_footer();
                ?>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="table-responsive">
            <?php
				$this->results_table_header();
				$this->results_table_body();
				$this->results_table_footer();
			?>
            </div>
        </div>
		<?php
		$this->get_submit_button();
	}
}