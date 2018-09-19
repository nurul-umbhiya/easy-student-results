<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_Settings  {
    private $settings_api, $TD;
    private static $instance;

    public static function getInstance( ) {
        if( self::$instance == null ) {
            self::$instance = new self;
        } else {
            throw new BadFunctionCallException(sprintf('Plugin %s already instantiated', __CLASS__));
        }
        return self::$instance;
    }

    private function __construct() {
        require_once RPS_Result_Management::DIR() . '/libs/settings-api/class.settings-api.php';
        $this->settings_api = new WeDevs_Settings_API;
        $this->TD = RPS_Result_Management::TD;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action('admin_menu', array($this, 'settingsMenu'), 18);
    }


    public function settingsMenu() {
        add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Settings", $this->TD), __("Settings", $this->TD), 'administrator', RPS_Result_Management::PLUGIN_SLUG . '_settings',array($this,'mainDiv'));
    }


    public function mainDiv() {
        echo '<div class="wrap">';
        echo '<h2><i id="icon-edit" class="dashicons dashicons-admin-generic" style="line-height: 1.5em;"></i>&nbsp;';
        echo __('Settings', $this->TD);
        echo '</h2><br class="clear">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => RPS_Result_Management::PLUGIN_SLUG .'_basics',
                'title' => __( 'General Settings', $this->TD )
            ),
            array(
                'id' => RPS_Result_Management::PLUGIN_SLUG . '_students',
                'title' => __( 'Shortcode Student', $this->TD )
            ),
            array(
                'id' => RPS_Result_Management::PLUGIN_SLUG . '_results',
                'title' => __( 'Shortcode Result', $this->TD )
            ),
            array(
                'id' => RPS_Result_Management::PLUGIN_SLUG . '_api',
                'title' => __( 'API', $this->TD )
            ),
            array(
                'id' => RPS_Result_Management::PLUGIN_SLUG . '_license',
                'title' => __( 'License', $this->TD )
            ),
        );
        return apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_settings_section', $sections);
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
    	$image_sizes = get_intermediate_image_sizes();
    	$thumbnail_image_sizes = array();
    	foreach ($image_sizes as $size) {
    		$thumbnail_image_sizes[ $size ] = ucfirst($size);
	    }
        $settings_fields = array(
            RPS_Result_Management::PLUGIN_SLUG .'_basics' => apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_settings_field_basics', array(
                array(
                    'name'  => 'user_role',
                    'label' => __( 'Who Can View <em>Student Results</em> Menu', $this->TD ),
                    'desc'  => __( 'Select User Role here. Default is Administrator', $this->TD ),
                    'type'  => 'select',
                    'options' => array(
                        'manage_options' => 'Administrator',
                        'edit_pages'        => 'Editor',
                        'publish_posts'        => 'Author',
                        'edit_posts'   => 'Contributor',
                        'read'    => 'Subscriber'
                    )
                ),
                array(
                    'name'  => 'bootstrap_css',
                    'label' => __( 'Disable Bootstrap CSS', $this->TD ),
                    'desc'  => __( 'Disable Bootstrap CSS on Frontend', $this->TD ),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'  => 'bootstrap_js',
                    'label' => __( 'Disable Bootstrap JS', $this->TD ),
                    'desc'  => __( 'Disable Bootstrap JS on Frontend', $this->TD ),
                    'type'  => 'checkbox'
                ),
	            array(
		            'name'  => 'marks_js',
		            'label' => __( 'Disable Auto-Calculating Total Marks?', $this->TD ),
		            'desc'  => __( 'If you want to disable auto-calculating Total Marks value in marks window, uncheck this button', $this->TD ),
		            'type'  => 'checkbox',
		            'default' => 'on'
	            ),
	            array(
		            'name'  => 'percentage_js',
		            'label' => __( 'Disable Auto-Calculating Percentage?', $this->TD ),
		            'desc'  => __( 'If you want to disable auto-calculating percentage value in marks window, uncheck this button', $this->TD ),
		            'type'  => 'checkbox',
		            'default' => 'on'
	            ),
                array(
                    'name'  => 'delete_data',
                    'label' => __( 'Delete All Plugins Data', $this->TD ),
                    'desc'  => __( 'If you select this option, when you delete this plugin, all plugins data such as custom db table created by this plugin, all students data, all courses data etc will be deleted. Select this option if you want to completely remove this plugin.', $this->TD ),
                    'type'  => 'checkbox'
                ),
            ) ),

            RPS_Result_Management::PLUGIN_SLUG . '_students' => apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_settings_field_students', array(

                array(
                    'name'    => 'department_text',
                    'label'   => __( 'Select Department Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Department Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Select Department', $this->TD)
                ),
                array (
                    'name'    => 'department_error_text',
                    'label'   => __( 'Department Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Department From List.', $this->TD)
                ),

                array(
                    'name'    => 'batch_text',
                    'label'   => __( 'Select Batch Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Batch Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Select Batch', $this->TD)
                ),
                array(
                    'name'    => 'batch_error_text',
                    'label'   => __( 'Batch Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Batch From List.', $this->TD)
                ),

                array(
                    'name'    => 'semester_text',
                    'label'   => __( 'Select Semester Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Semester Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Select Semester', $this->TD)
                ),
                array(
                    'name'    => 'semester_error_text',
                    'label'   => __( 'Semester Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Semester From List.', $this->TD)
                ),

                array(
                    'name'    => 'student_meta',
                    'label'   => __( 'Student Information', $this->TD ),
                    'desc'    => __( 'Enter which Students Information to display on search results in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Department, batch:Batch, semester:Semester'
                ),
                array(
                    'name'    => 'help_html',
                    'label'   => __( 'Available Fields', $this->TD ),
                    'desc'    => 'department, batch, semester, registration_no, roll_no, library_card, f_name,
                                 m_name, dob, email, stu_phone, blood_group, religion, nationality, present_address,
                                 permanent_address, gaurdian_name, gaurdian_address, gaurdian_contact_no, gaurdian_email',
                    'type'    => 'html',
                ),
            ) ),
            RPS_Result_Management::PLUGIN_SLUG . '_results' => apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_settings_field_results', array (

                array(
                    'name'    => 'exam_text',
                    'label'   => __( 'Select Exam Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Exams Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Select Exam', $this->TD)
                ),
                array (
                    'name'    => 'exam_error_text',
                    'label'   => __( 'Exam Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Exam From List.', $this->TD)
                ),

                array(
                    'name'    => 'department_text',
                    'label'   => __( 'Select Department Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Department Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Department', $this->TD)
                ),
                array (
                    'name'    => 'department_error_text',
                    'label'   => __( 'Department Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Department From List.', $this->TD)
                ),

                array(
                    'name'    => 'batch_text',
                    'label'   => __( 'Select Batch Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Batch Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Batch', $this->TD)
                ),
                array(
                    'name'    => 'batch_error_text',
                    'label'   => __( 'Batch Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Batch From List.', $this->TD)
                ),

                array(
                    'name'    => 'semester_text',
                    'label'   => __( 'Select Semester Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Semester Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Semester', $this->TD)
                ),
                array(
                    'name'    => 'semester_error_text',
                    'label'   => __( 'Semester Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Semester From List.', $this->TD)
                ),

                array(
                    'name'    => 'student_meta',
                    'label'   => __( 'Student Informations', $this->TD ),
                    'desc'    => __( 'Enter which Students Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'name:Name, roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Department, batch:Batch, semester:Semester'
                ),

                array(
                    'name'    => 'help_html_student',
                    'label'   => __( 'Available Student Fields', $this->TD ),
                    'desc'    => '<strong>name,department, batch, semester, registration_no, roll_no, library_card, f_name,
                                 m_name, dob, email, stu_phone, blood_group, religion, nationality, present_address,
                                 permanent_address, gaurdian_name, gaurdian_address, gaurdian_contact_no, gaurdian_email</strong><br>
                                 <strong><em>*** </em> You can also use Result Fields data here. eg: total_marks:Total Marks, cgpa:Cgpa, final_result:Final Result etc</strong>',
                    'type'    => 'html',
                ),

                array(
                    'name'    => 'subject_meta',
                    'label'   => __( 'Subject Fields', $this->TD ),
                    'desc'    => __( 'Enter which Subject Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'course_name:Subject Name, course_code:Subject Code, total_marks:Total Marks, marks_obtained:Marks Obtained, grade:Grade'
                ),
                array(
                    'name'    => 'help_html_subject',
                    'label'   => __( 'Available Subject Fields', $this->TD ),
                    'desc'    => '<strong>name, course_code, total_marks, marks_obtained, grade, grade_point, percentage</strong>',
                    'type'    => 'html',
                ),

                array(
                    'name'    => 'result_meta',
                    'label'   => __( 'Result fields', $this->TD ),
                    'desc'    => __( 'Enter which Result Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'total_marks:Total Marks, cgpa:Cgpa, final_result:Final Result'
                ),
                array(
                    'name'    => 'help_html_result',
                    'label'   => __( 'Available Result Fields', $this->TD ),
                    'desc'    => '<strong>total_marks, cgpa, final_result, final_grade, total_marks_obtained, total_percentage</strong>',
                    'type'    => 'html',
                ),

                array(
                    'name'  => 'print_button',
                    'label' => __( 'Show Print Result Button', $this->TD ),
                    'desc'  => __( 'Show Print result button on search page.', $this->TD ),
                    'type'  => 'checkbox',
                    'default' => 'on'
                ),
                array(
                    'name'    => 'print_button_text',
                    'label'   => __( 'Print Button Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Print Result', $this->TD)
                ),

                array(
                    'name'    => 'print_header',
                    'label'   => __( 'Print Header Section', $this->TD ),
                    'desc'    => __( 'Institution name, address etc for Print version of result page. You can use HTML Tags and CSS styling here. Leave this field blank if you don\'t want to include header section for print version of result.', $this->TD ),
                    'type'    => 'wysiwyg',
                    'default' => ''
                ),

                array(
                    'name'    => 'print_footer',
                    'label'   => __( 'Print Footer Section', $this->TD ),
                    'desc'    => __( 'You can use HTML Tags and CSS styling here. Leave this field blank if you don\'t want to include footer text for print version of result.', $this->TD ),
                    'type'    => 'wysiwyg',
                    'default' => ''
                ),

                array(
                    'name'    => 'custom_css',
                    'label'   => __( 'Custom CSS', $this->TD ),
                    'desc'    => __( 'If you want to change css styling you can do it from here.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => '#rps_result{}'
                ),

                array(
                    'name'  => 'show_header_footer',
                    'label' => __( 'Display Header/footer with Result ?', $this->TD ),
                    'desc'  => __( 'Check this box if you want to display above header footer while displaying result.', $this->TD ),
                    'type'  => 'checkbox',
                ),

                array(
                    'name'  => 'hide_search_fields',
                    'label' => __( 'Hide Search Field', $this->TD ),
                    'desc'  => __( 'If you want to hide search fields while displaying result, you can tick this checkbox', $this->TD ),
                    'type'  => 'checkbox',
                ),
	            array(
		            'name'  => 'show_picture',
		            'label' => __( 'Show Student Image', $this->TD ),
		            'desc'  => __( 'If you want to display Student Image on Result Page, tick this checkbox.', $this->TD ),
		            'type'  => 'checkbox',
		            'default' => 'off'
	            ),
	            array(
		            'name'  => 'image_size',
		            'label' => __( 'Image Size', $this->TD ),
		            'desc'  => __( 'Select image size for student image.', $this->TD ),
		            'type'  => 'select',
		            'options' => $thumbnail_image_sizes
	            ),

            )),

            RPS_Result_Management::PLUGIN_SLUG . '_api' => apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_settings_field_api' ,array (
                array(
                    'name'  => 'enable_api',
                    'label' => __( 'Enable API', $this->TD ),
                    'desc'  => __( 'Enable API for Mobile Apps', $this->TD ),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'    => 'exam_text',
                    'label'   => __( 'Select Exam Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Exams Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Select Exam', $this->TD)
                ),
                array (
                    'name'    => 'exam_error_text',
                    'label'   => __( 'Exam Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Exam From List.', $this->TD)
                ),

                array(
                    'name'    => 'department_text',
                    'label'   => __( 'Select Department Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Department Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Department', $this->TD)
                ),
                array (
                    'name'    => 'department_error_text',
                    'label'   => __( 'Department Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Department From List.', $this->TD)
                ),

                array(
                    'name'    => 'batch_text',
                    'label'   => __( 'Select Batch Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Batch Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Batch', $this->TD)
                ),
                array(
                    'name'    => 'batch_error_text',
                    'label'   => __( 'Batch Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Batch From List.', $this->TD)
                ),

                array(
                    'name'    => 'semester_text',
                    'label'   => __( 'Select Semester Text', $this->TD ),
                    'desc'    => __( 'First option to dispaly on Semester Select box.', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Semester', $this->TD)
                ),
                array(
                    'name'    => 'semester_error_text',
                    'label'   => __( 'Semester Error Text', $this->TD ),
                    'type'    => 'text',
                    'default' => __('Please Select a Semester From List.', $this->TD)
                ),

                array(
                    'name'    => 'student_meta',
                    'label'   => __( 'Student Information', $this->TD ),
                    'desc'    => __( 'Enter which Students Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'name:Name, roll_no:Roll No, registration_no:Registration No, dob:Date of Birth, department:Department, batch:Batch, semester:Semester'
                ),

                array(
                    'name'    => 'help_html_student',
                    'label'   => __( 'Available Student Fields', $this->TD ),
                    'desc'    => '<strong>name,department, batch, semester, registration_no, roll_no, library_card, f_name,
                                 m_name, dob, email, stu_phone, blood_group, religion, nationality, present_address,
                                 permanent_address, gaurdian_name, gaurdian_address, gaurdian_contact_no, gaurdian_email</strong>',
                    'type'    => 'html',
                ),

                array(
                    'name'    => 'subject_meta',
                    'label'   => __( 'Subject Fields', $this->TD ),
                    'desc'    => __( 'Enter which Subject Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'course_name:Subject Name, course_code:Subject Code, total_marks:Total Marks, marks_obtained:Marks Obtained, grade:Grade'
                ),
                array(
                    'name'    => 'help_html_subject',
                    'label'   => __( 'Available Subject Fields', $this->TD ),
                    'desc'    => '<strong>name, course_code, total_marks, marks_obtained, grade, grade_point</strong>',
                    'type'    => 'html',
                ),

                array(
                    'name'    => 'result_meta',
                    'label'   => __( 'Result fields', $this->TD ),
                    'desc'    => __( 'Enter which Result Information to display on result output in a key:value pair, seperated by <strong>,</strong> (comma).
                                    Key will be the available fields and value will be the label (title) text. eg:roll_no:Roll No.', $this->TD ),
                    'type'    => 'textarea',
                    'default' => 'total_marks:Total Marks, cgpa:Cgpa, final_result:Final Result'
                ),
                array(
                    'name'    => 'help_html_result',
                    'label'   => __( 'Available Result Fields', $this->TD ),
                    'desc'    => '<strong>total_marks, cgpa, final_result, final_grade</strong>',
                    'type'    => 'html',
                ),
            )),

            RPS_Result_Management::PLUGIN_SLUG . '_license' => apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_license_fields', array()),

        );

        return $settings_fields;
    }

}