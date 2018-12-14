<?php
if( ! defined( 'WPINC' ) ) {
	die();
}

class RPS_Helper_Result {

	public function __construct() {
		//$ret = $this->_getStudentData(1, 1);
		//echo "<pre>"; print_r($ret); echo "</pre>";

	}

	public function getExamID( $get = 1, $enable = null ) {
		global $wpdb;

		if ( $enable != null ) {
			$query = $wpdb->prepare( "SELECT `id`,`name`, `exam_year` FROM `{$wpdb->rps_exam}` WHERE `active` = %d AND `display` = %d", array($get, $enable) );
		} else {
			$query = $wpdb->prepare( "SELECT `id`,`name`, `exam_year` FROM `{$wpdb->rps_exam}` WHERE `active` = %d", array($get) );
		}

		$result = $wpdb->get_results( $query, ARRAY_A );

		return $wpdb->get_results( $query, ARRAY_A );

	}

	//this is for result shortcode
	public function getExamListData() {
		$transient = "rps_exam_list_data";
		//delete_transient($transient);
		global $wpdb;

		if ( false === ( $ret = get_transient( $transient ) ) ) {

			$exam_ids = $this->getExamID();
			$ret = array();
			$dbs = new RPS_Helper_DBS();

			if ( !empty( $exam_ids ) ) {

				foreach ($exam_ids as $row) {
					$exam_id = $row['id'];
					$ret[ $exam_id ]['department_ids'] = array();

					//get all department id for this exam id
					$query = $wpdb->prepare("SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `exam_id` = %d AND `active` = 1 AND `display` = 1 ", array($exam_id));
					$results = $wpdb->get_results($query, ARRAY_A);

					if ( !empty( $results ) ) {

						foreach ( $results as $exam_record ) {
							$department_id = $exam_record['department_id'];
							$batch_id = $exam_record['batch_id'];
							$semester_id = $exam_record['semester_id'];

							//check department already processed
							if ( !array_key_exists( $department_id, $ret[$exam_id]['department_ids'] ) ) {
								$dept_info = $dbs->getDepartmentInfo($department_id);
								$ret[$exam_id]['department_ids'][$department_id] = $dept_info['name'];

								$ret[$exam_id][$department_id]['batch_ids'] = array();
								$ret[$exam_id][$department_id]['semester_ids'] = array();
							}

							if ( !array_key_exists( $batch_id, $ret[$exam_id][$department_id]['batch_ids'] ) ) {
								$batch_info = $dbs->getBatchInfo($department_id, $batch_id);
								$ret[$exam_id][$department_id]['batch_ids'][$batch_id] = $batch_info['name'];
							}

							if ( !array_key_exists($semester_id, $ret[$exam_id][$department_id]['semester_ids'] ) && $semester_id ) {
								$semester_info = $dbs->getSemesterInfo($department_id, $semester_id);
								if ( !is_wp_error($semester_info) ) {
									$ret[$exam_id][$department_id]['semester_ids'][$semester_id] = $semester_info['name'];
								} else {
									$ret[$exam_id][$department_id]['semester_ids'][$semester_id] = '';
								}

							}

						}

					}
				}
			} else {
				$ret = new WP_Error('Empty', __("No exams found on database.", RPS_Result_Management::TD));
			}


			set_transient( $transient, $ret, 12 * DAY_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}

		}

		return $ret;

	}

	//get unique exam name
	//this is for result shortcode 2
	public function getExamName($get = 1, $enable = null) {
		$transient = "rps_exam_unique_names";
		//delete_transient($transient);
		global $wpdb;

		if ( false === ( $ret = get_transient( $transient ) ) ) {

			$exam_ids = $this->getExamID($get, $enable);
			$ret = array();
			foreach ( $exam_ids as $exam_data ) {
				$exam_name = trim( $exam_data['name'] );
				if ( !in_array($exam_name, $ret) ) {
					$ret[] = $exam_name;
				}
			}

			set_transient( $transient, $ret, 12 * DAY_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}
		}
		return $ret;
	}

	//get unique exam year
	//this is for result shortcode 2
	public function getExamYear($get = 1, $enable = null) {
		$transient = "rps_exam_unique_years";
		//delete_transient($transient);
		global $wpdb;

		if ( false === ( $ret = get_transient( $transient ) ) ) {

			$exam_ids = $this->getExamID($get, $enable);
			$ret = array();
			foreach ( $exam_ids as $exam_data ) {
				$exam_year = trim( $exam_data['exam_year'] );
				if ( !in_array($exam_year, $ret) ) {
					$ret[] = $exam_year;
				}
			}

			set_transient( $transient, $ret, 12 * DAY_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}
		}
		return $ret;
	}

	public function getExamData( $exam_id, $department_id, $batch_id, $semester_id = null ) {
		//echo "exam ID: $exam_id, Department ID: $department_id, Batch ID: $batch_id";
		if ( ! RPS_Helper_Function::is_numeric($exam_id) && !RPS_Helper_Function::is_numeric( $department_id ) && !RPS_Helper_Function::is_numeric( $batch_id ) )
			return null;

		global $wpdb;
		$query = "";
		if ( $exam_id && $department_id && $batch_id && $semester_id ) {
			$query = $wpdb->prepare(
				"SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `exam_id`= %d AND `department_id` = %d AND `batch_id` = %d AND `semester_id` = %d",
				array ( $exam_id, $department_id, $batch_id, $semester_id )
			);
		} elseif ( $exam_id && $department_id && $batch_id ) {
			$query = $wpdb->prepare(
				"SELECT * FROM `{$wpdb->rps_exam_record}` WHERE `exam_id`=%d AND `department_id` = %d AND `batch_id` = %d ",
				array ( $exam_id, $department_id, $batch_id )
			);
		}

		if ( $query != "" ) {
			return $wpdb->get_row( $query, ARRAY_A );
		}

		return null;
	}

	public function getStudentIDsBySearchForm( $exam_id, $department_id, $batch_id, $semester_id = null ) {
		global $wpdb;
		$student = new RPS_Helper_Student();

		//get exam_record_id
		if( RPS_Helper_Function::is_numeric( $exam_id ) && RPS_Helper_Function::is_numeric( $department_id )
			&& RPS_Helper_Function::is_numeric( $batch_id ) && RPS_Helper_Function::is_numeric( $semester_id ) ) {

			$query = $wpdb->prepare("SELECT id from `{$wpdb->rps_exam_record}` WHERE exam_id=%d AND
			department_id=%d AND batch_id=%d AND semester_id=%d LIMIT 1", array( $exam_id, $department_id, $batch_id, $semester_id ) );

		}
		elseif( RPS_Helper_Function::is_numeric( $exam_id ) && RPS_Helper_Function::is_numeric( $department_id )
			&& RPS_Helper_Function::is_numeric( $batch_id ) ) {

			$query = $wpdb->prepare("SELECT id from `{$wpdb->rps_exam_record}` WHERE exam_id=%d AND
			department_id=%d AND batch_id=%d LIMIT 1", array( $exam_id, $department_id, $batch_id ) );

		}
		else {
			return new WP_Error( 'Invalid Input', __( "Invalid Argument Supplied.", RPS_Result_Management::TD ) );
		}

		$exam_record_id = $wpdb->get_var($query);

		if( !RPS_Helper_Function::is_numeric($exam_record_id) ) {
			return new WP_Error( 'Invalid Exam Record ID', __( "No Exam Record Found.", RPS_Result_Management::TD ) );
		}

		$student_list = $this->getStudentIdsByExamRecordId($exam_record_id);

		if ( empty( $student_list ) ) {
			return new WP_Error( 'Empty', __( "No results found on database.", RPS_Result_Management::TD ) );
		}

		$ret = array();
		foreach ( $student_list as $student_id ) {
			$ret[ $student_id ] = $student->getStudentInfo2( $student_id );
			$ret[ $student_id ]['image'] = ($ret[ $student_id ]['image'] != "" ? $ret[ $student_id ]['image'] : ( $ret[ $student_id ]['gender'] === "Male" ?  RPS_Result_Management::URL() . '/assets/img/user_male.png' : RPS_Result_Management::URL() . '/assets/img/user_female.png' ) );
		}

		return array( 'exam_record_id' => $exam_record_id, 'data' => $ret );

	}

	public function getStudentIdsByExamRecordId( $exam_record_id ) {
		if ( RPS_Helper_Function::is_numeric( $exam_record_id ) ) {
			global $wpdb;
			$query = $wpdb->prepare("SELECT DISTINCT `student_id` FROM {$wpdb->rps_marks} WHERE `exam_record_id` = %d", array( $exam_record_id ) );

			$col = $wpdb->get_col($query);
			return $col;
		}

		return array();
	}

	public function getStudentIdsByExamRecordId2( $exam_record_id ) {
		if ( RPS_Helper_Function::is_numeric( $exam_record_id ) ) {
			global $wpdb;
			$query = $wpdb->prepare("SELECT `subject_id`, `student_id` FROM {$wpdb->rps_marks} WHERE `exam_record_id` = %d", array( $exam_record_id ) );

			$col = $wpdb->get_results($query, ARRAY_A);
			return $col;
		}

		return array();
	}

	public function getCourseIdsByExamRecordId( $exam_record_id, $student_id = null ) {
		if ( RPS_Helper_Function::is_numeric( $exam_record_id ) ) {
			global $wpdb;
			if ( $student_id !== null && RPS_Helper_Function::is_numeric($student_id) ) {
				$query = $wpdb->prepare("SELECT DISTINCT `subject_id` FROM {$wpdb->rps_marks} WHERE `exam_record_id` = %d AND `student_id` = %d", array( $exam_record_id, $student_id ) );
			} else {
				$query = $wpdb->prepare("SELECT DISTINCT `subject_id` FROM {$wpdb->rps_marks} WHERE `exam_record_id` = %d", array( $exam_record_id ) );
			}

			$col = $wpdb->get_col($query);
			return $col;
		}

		return array();
	}



	public function getExamRecordDataByExamRecordID( $exam_record_id ) {
		if ( RPS_Helper_Function::is_numeric( $exam_record_id ) ) {
			global $wpdb;
			$query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_exam_record} WHERE `id` = %d", array( $exam_record_id ) );

			return $wpdb->get_row($query, ARRAY_A);
		}

		return array();
	}



	public function update_exam_record_meta( $exam_record_id, $student_id, $meta_key='', $meta_value= '' ) {
		if ( !$meta_key || !RPS_Helper_Function::is_numeric( $exam_record_id ) || !RPS_Helper_Function::is_numeric( $student_id ) ) {
			return false;
		}

		global $wpdb;

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize($meta_value);

		//check metavalue is same as existing
		$old_value = $this->check_exam_record_meta($exam_record_id, $student_id, $meta_key);

		if ( $old_value !== null && $old_value == $meta_value) {
			//value already exist ie: old value is equal to new value
			return false;

		} elseif ( $old_value === null ) {

			//this is new, so insert new record
			$result = $wpdb->insert($wpdb->rps_exam_record_meta,
				array(
					'exam_record_id' 	=> $exam_record_id,
					'student_id'		=> $student_id,
					'meta_key' 			=> $meta_key,
					'meta_value' 		=> $meta_value
				),
				array(
					'%d', '%d', '%s', '%s'
				)
			);

			if (!$result) {
				return false;
			}


			$id = (int) $wpdb->insert_id;

			$transient = "rps_exam_record_meta_{$exam_record_id}_{$student_id}";
			delete_transient($transient);

			return $id;

		} elseif( $old_value !== null ) {
			//update metadata

			$result = $wpdb->update( $wpdb->rps_exam_record_meta,
				array('meta_value' 		=> $meta_value),
				array(
					'exam_record_id' 	=> $exam_record_id,
					'student_id'		=> $student_id,
					'meta_key' 			=> $meta_key,
				),
				array( "%s" ),
				array( "%d", "%d", "%s" )
			);



			if (!$result) {
				return false;
			}

			$transient = "rps_exam_record_meta_{$exam_record_id}_{$student_id}";
			delete_transient($transient);

			return true;

		}

		return false;
	}


	function check_exam_record_meta( $exam_record_id, $student_id, $meta_key = '' ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT `meta_value` FROM `{$wpdb->rps_exam_record_meta}` WHERE `exam_record_id` = %d AND `student_id` = %d AND `meta_key` = %s LIMIT 1",
			array($exam_record_id, $student_id, $meta_key)
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		if ( is_array($result) && !empty($result) ) {
			return $result['meta_value'];
		}

		return null;
	}


	function get_exam_record_meta( $exam_record_id, $student_id, $meta_key = '' ) {

		if ( !RPS_Helper_Function::is_numeric( $exam_record_id ) || !RPS_Helper_Function::is_numeric( $student_id ) ) {

			return false;
		}

		$transient = "rps_exam_record_meta_{$exam_record_id}_{$student_id}";


		//delete_transient($transient);

		if ( false === ( $meta_cache = get_transient( $transient ) ) ) {
			//now get data from database
			global $wpdb;


			$query = $wpdb->prepare(
				"SELECT `meta_key`, `meta_value` FROM `{$wpdb->rps_exam_record_meta}` WHERE `exam_record_id` = %d AND `student_id` = %d",
				array($exam_record_id, $student_id)
			);


			$result = $wpdb->get_results( $query, ARRAY_A );


			$tmp = array();
			if ( !empty( $result ) ) {
				foreach ( $result as $row ) {
					$tmp[ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
				}
			}
			else {
				$temp = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_results_table_headings',array(
					'total_marks'           => '',
					'total_marks_obtained'  => '',
					'total_percentage'      => '',
					'final_grade'           => '',
					'cgpa'                  => '',
					'result'                => '',
					'final_result'          => ''
				));


				foreach ( $temp as $key => $value ) {
					$tmp[ $key ] = '';
				}

			}
			$meta_cache = $tmp;

			set_transient( $transient, $meta_cache, 12 * DAY_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}

		}

		if( empty( $meta_cache ) ) {
			return null;
		}

		if ( !$meta_key ) {
			return $meta_cache;
		}

		if (isset($meta_cache[$meta_key])) {
			//metakey exist
			return $meta_cache[$meta_key];

		} else {
			//metakey doesent exist
			return null;
		}
	}

	private function getGradeData() {

		$transient = 'rps_grade_data';

		if ( false === ( $grade_data = get_transient( $transient ) ) ) {
			// It wasn't there, so regenerate the data and save the transient
			global $wpdb;

			$query = "SELECT * FROM `{$wpdb->rps_grade}` WHERE `active`=1";

			$grade_data = $wpdb->get_results( $query, ARRAY_A );

			set_transient( $transient, $grade_data, 1 * WEEK_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}

		}

		return $grade_data;
	}

	public function getGradeList() {
		$data = $this->getGradeData();
		if( empty($data) ) {
			return new WP_Error( 'No Data', 'No grade found on database.' );
		}

		$ret = array();

		foreach ( $data as $row ) {
			$ret[ $row['id'] ] = $row['grade'];
		}

		return $ret;
	}

	public function getGradePointList() {
		$data = $this->getGradeData();
		if( empty($data) ) {
			return new WP_Error( 'No Data', 'No grade found on database.' );
		}

		$ret = array();

		foreach ( $data as $row ) {
			$ret[ $row['id'] ] = $row['grade_point'];
		}

		return $ret;
	}
}