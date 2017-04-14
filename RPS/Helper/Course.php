<?php
/**
 * Use function getCourseInfo($course_id) to get all course data for a specefic course.
 * Use function getCourseDetails($department_id,$semester_id,$course_id) or getCourseDetails($course_id) to get Course Details
 * Use function getCourseList($department_id,$semester_id) to get all course lists with $course_id => $course_code pair
 * Use function getAllCourses($department_id) to get course details for a specific department.
 */
if ( ! defined( 'WPINC' ) ) {
	die();
}

class RPS_Helper_Course {
	public function __construct() {
		//$this->getCourseData();
	}

	/**
	 * This function will return all courses with details
	 * @internal this function is internal
	 * @global object $wpdb
	 * @return array course details for all courses
	 */
	private function getCourseData( $department_id, $semester_id = null ) {
		if( null === $semester_id ) {
			$transient = RPS_Result_Management::PLUGIN_SLUG . "_course_list_$department_id";
		} else {
			$transient = RPS_Result_Management::PLUGIN_SLUG . "_course_list_{$department_id}_{$semester_id}";
		}

		//delete_transient( $transient );

		if ( false === ( $ret = get_transient( $transient ) ) ) {


			global $wpdb;

			//get program by $department_id ie. day or evening
			$query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_department} WHERE id = %d", array( $department_id ));
			$row   = $wpdb->get_row( $query, ARRAY_A );
			if ( $row === null ) {
				return new \WP_Error( 'Invalid Department ID', __( "Given 'Department ID' is invalid. No data found associated with this ID. Please check your input.", RPS_Result_Management::TD ) );
			}


			$dept_key = '_department_id';
			$sem_key  = '_semester_id';

			$query_args['meta_query'][] = array(
				'key'     => $dept_key,
				'value'   => $department_id,
				'compare' => '=',
			);

			if( RPS_Helper_Function::is_numeric( $semester_id ) ) {
				$query_args['meta_query'][] = array(
					'key'     => $sem_key,
					'value'   => $semester_id,
					'compare' => '=',
				);
			}


			$meta_query = new \WP_Meta_Query();
			$meta_query->parse_query_vars( $query_args );

			$mq_sql = $meta_query->get_sql(
				'post',
				$wpdb->posts,
				'ID',
				null
			);

			$post_type = RPS_Result_Management::COURSE;

			$query   = "SELECT ID, post_title FROM {$wpdb->posts} {$mq_sql['join']} WHERE post_type='{$post_type}' and post_status='publish' {$mq_sql['where']}";
			$results = $wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $results ) ):
				foreach ( $results as $row ) {
					$data = get_post_custom( $row['ID'] );
					//print_r($data);
					$course_code  = $data['_course_code'][0];
					$sem_id  = $data['_semester_id'][0];
					$details      = maybe_unserialize( $data['_course_details'][0] );
					//print_r($details);
					$info = array(
						'course_code'     => $course_code,
						'name'            => $row['post_title'],
						'total_marks'     => $details['total_marks'],
						'credit'          => $details['credit'],
						'course_type'     => RPS_Helper_Function::getCourseType($details['course_type']),
						'pre_requisist'   => $details['pre_requisist'],
						'related_subject' => $details['related_subject'],
					);

					if ( RPS_Helper_Function::is_numeric( $sem_id ) ) {
						$ret[ $sem_id ][ $row['ID'] ] = $info;
					} else {
						$ret[0][ $row['ID'] ] = $info;
					}
				}
			else:
				return new \WP_Error( 'No Record Found', __( "No record is associated with given Department_ID", "ustc" ) );
			endif;


			set_transient( $transient, $ret, YEAR_IN_SECONDS );

			$transient_keys = get_option( 'rps_result_transient_keys', array() );
			if( !array_key_exists($transient, $transient_keys) ) {
				$transient_keys[] = $transient;
				update_option( 'rps_result_transient_keys', $transient_keys );
			}
		}

		return $ret;
	}


	/**
	 * This function will return all courses by department_id.
	 * if no arguments are set WP_Error will returned
	 *
	 * @param type $department_id
	 *
	 * @return \WP_Error
	 * @return array $ret
	 */
	public function getAllCourses( $department_id, $semester_id = null ) {
		$data = array();
		if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $semester_id )) {
			$data = $this->getCourseData( $department_id, $semester_id );
			if ( is_wp_error($data) )
				return $data;
			else
				return $data[ $semester_id ];

		} elseif ( RPS_Helper_Function::is_numeric( $department_id ) ) {
			$data = $this->getCourseData( $department_id );
			if ( is_wp_error($data) )
				return $data;
			else
				return $data[0];
		} else {
			return new \WP_Error( 'Invalid Arguments', __( "Given arguments are invalid.", RPS_Result_Management::TD ) );
		}
	}

	public function getAllCourseList( $department_id, $semester_id = null  ) {
		$data = array();
		$ret = array();
		if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $semester_id )) {
			$data = $this->getCourseData( $department_id, $semester_id );
		} elseif ( RPS_Helper_Function::is_numeric( $department_id ) ) {
			$data = $this->getCourseData( $department_id );
		} else {
			return new \WP_Error( 'Invalid Arguments', __( "Given arguments are invalid.", RPS_Result_Management::TD ) );
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		} else {
			if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $semester_id )) {

				foreach ( $data as $sem_id => $course_data ):

					foreach ( $course_data as $course_id => $course_details ) {
						$ret[ $sem_id ][ $course_id ] = $course_details['course_code'];
					}

				endforeach;

			} elseif ( RPS_Helper_Function::is_numeric( $department_id ) ) {

				foreach ( $data[0] as $course_id => $course_details ):

					$ret[ $course_id ] = $course_details['course_code'];

				endforeach;

			}

		}
		return $ret;
	}

	/**
	 * Get Course Details for a course, eg, course_code, department_id etc
	 *
	 * @param int $course_id
	 */
	public function getCourseInfo( $course_id ) {
		$ret = array();
		if ( RPS_Helper_Function::is_numeric( $course_id ) ):
			$course = get_post( $course_id );
			$data   = get_post_custom( $course_id );

			$course_code     = $data['_course_code'][0];
			$department_id   = $data['_department_id'][0];
			$semester_id     = $data['_semester_id'][0];
			$details         = maybe_unserialize( $data['_course_details'][0] );

			$ret = array(
				'name'            => $course->post_title,
				'course_code'     => $course_code,
				'department_id'   => $department_id,

				'semester_id'     => $semester_id,
				'total_marks'     => $details['total_marks'],
				'credit'          => $details['credit'],
				'course_type'     => $details['course_type'],
				'pre_requisist'   => $details['pre_requisist'],
				'related_subject' => $details['related_subject'],
			);
		endif;

		return $ret;
	}


	/**
	 *
	 * @param type $department_id
	 * @param type $semester_id
	 * @param type $course_id
	 *
	 * @return \WP_Error
	 * @return Array $ret
	 */
	public function getCourseDetails( $department_id = null, $course_id = null, $semester_id = null ) {
		$data = array();
		$ret = array();

		if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $semester_id ) && RPS_Helper_Function::is_numeric( $course_id ) ) {

			$data = $this->getCourseData( $department_id, $semester_id );

		} elseif ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $course_id ) ) {

			$data = $this->getCourseData( $department_id );

		} else {

			return new \WP_Error( 'Invalid Arguments', __( "Given arguments are invalid.", RPS_Result_Management::TD ) );

		}

		if ( is_wp_error( $data ) ) {

			return $data;

		} else {
			if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $semester_id ) ) {

				if (  array_key_exists( $semester_id, $data ) && array_key_exists($course_id, $data[ $semester_id ] ) ) {
					$ret = $data[ $semester_id ][ $course_id ];
				} else {
					return new \WP_Error( 'Invalid Arguments', __( "Given arguments are invalid.", RPS_Result_Management::TD ) );
				}

			} elseif ( RPS_Helper_Function::is_numeric( $department_id ) ) {

				if ( is_array($data) && array_key_exists(0,$data) && array_key_exists( $course_id, $data[0] ) ) {
					$ret = $data[0][ $course_id ];
				} else {
					return new \WP_Error( 'Invalid Arguments', __( "Given arguments are invalid.", RPS_Result_Management::TD ) );
				}

			}
		}

		return $ret;
	}

}

