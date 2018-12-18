<?php

if ( !defined( 'WPINC' ) ) {
	die();
}

class RPS_DefaultOptions {
	private static $instance;
	private $TD;

	public static function getInstance() {
		if(self::$instance==null) {
			self::$instance = new self;
			self::$instance->TD = RPS_Result_Management::TD;
			//self::$instance->createOptions();
		}

		return self::$instance;
	}

	private function __construct() {
		;
	}

	public function createOptions() {

		$rps_result_basics = array(
			'user_role'			=> 'manage_options',
			'bootstrap_css'     => '',
			'bootstrap_js'      => '',
			'marks_js'          => 'on',
			'percentage_js'     => 'on',
			'delete_data'		=> '',
			'result_header'     => '<h4>CLEVER ZONE ACADEMY</h4><h5>MAKURDI</h5>',
			'result_footer'     => '<h4>Keys to Affective & Psychomotor Domain:</h4><p>5=excellent, 4=very good, 3=good, 2=fair, 1=poor</p>',
			'school_logo'       => RPS_Result_Management::URL() . '/assets/img/logo-128.png'
		);

		if( get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', '') == '' ) {
			add_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', $rps_result_basics );
		}

		$rps_result_students = array(
			'department_text'   => __('Select Class', $this->TD),
			'batch_text'        => __('Select Session', $this->TD),
			'semester_text'     => __('Select Term', $this->TD),
			'department_error_text' => __('Please Select a Class From List.', $this->TD),
			'batch_error_text'      => __('Please Select a Session From List.', $this->TD),
			'semester_error_text'   => __('Please Select a Term From List.', $this->TD),
			'student_meta'      => 'roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Class, batch:Session, semester:Term',
		);

		if( get_option( RPS_Result_Management::PLUGIN_SLUG . '_students', '') == '' ) {
			add_option( RPS_Result_Management::PLUGIN_SLUG . '_students', $rps_result_students );
		}

		$rps_result_results = array (
			'exam_text'         => __('Select Exam', $this->TD),
			'department_text'   => __('Class', $this->TD),
			'batch_text'        => __('Session', $this->TD),
			'semester_text'     => __('Term', $this->TD),
			'exam_error_text'   => __('Please Select a Exam From List.', $this->TD),
			'department_error_text' => __('Please Select a Class From List.', $this->TD),
			'batch_error_text'      => __('Please Select a Session From List.', $this->TD),
			'semester_error_text'   => __('Please Select a Term From List.', $this->TD),
			'student_meta'      => 'name:Name, roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Class, batch:Session, semester:Term',
			'subject_meta'      => 'course_name:Subject Name, course_code:Subject Code, total_marks:Total Marks, marks_obtained:Marks Obtained, grade:Grade',
			'result_meta'       => 'total_marks:Total Marks, total_marks_obtained:Marks Obtained, cgpa:Cgpa, final_result:Final Result, (highest_marks, class_position --> if you use Result Fields AddOns)',
			'print_button'		=> 'on',
			'print_button_text' => __('Print Result', $this->TD),
			'print_header' => '',
			'print_footer' => '',
			'custom_css' => '#rps_result{}',
			'show_header_footer' => '',
			'hide_search_fields' => '',
			'show_picture'		=> 'off',
			'image_size'        => 'thumbnail'
		);

		if( get_option( RPS_Result_Management::PLUGIN_SLUG . '_results', '') == '') {
			add_option( RPS_Result_Management::PLUGIN_SLUG . '_results', $rps_result_results );
		}

		//api section

		$rps_result_api = array (
				'enable_api'     	=> '',
				'exam_text'         => __('Select Exam', $this->TD),
				'department_text'   => __('Class', $this->TD),
				'batch_text'        => __('Session', $this->TD),
				'semester_text'     => __('Term', $this->TD),
				'exam_error_text'   => __('Please Select a Exam From List.', $this->TD),
				'department_error_text' => __('Please Select a Class From List.', $this->TD),
				'batch_error_text'      => __('Please Select a Session From List.', $this->TD),
				'semester_error_text'   => __('Please Select a Term From List.', $this->TD),
				'student_meta'      => 'name:Name, roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Class, batch:Session, semester:Term',
				'subject_meta'      => 'course_name:Subject Name, course_code:Subject Code, total_marks:Total Marks, marks_obtained:Marks Obtained, grade:Grade',
				'result_meta'       => 'total_marks:Total Marks, total_marks_obtained:Marks Obtained, cgpa:Cgpa, final_result:Final Result',
		);

		if( get_option( RPS_Result_Management::PLUGIN_SLUG . '_api', '') == '') {
			add_option( RPS_Result_Management::PLUGIN_SLUG . '_api', $rps_result_api );
		}
	}

}