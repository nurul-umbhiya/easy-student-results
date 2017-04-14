<?php

class RPS_API extends WP_REST_Controller {

    private $department_id, $batch_id, $semester_id, $student_id,  $exam_id, $exam_record_id;
    protected $error, $messages;
    protected $helper, $page, $dbs, $plugin_slug;
    protected $DIR, $URL, $VER, $TD, $options;
    protected $student, $result, $course;

    public function __construct() {

        $this->helper = new RPS_Helper_Function();
        $this->dbs = new RPS_Helper_DBS();
        $this->student = new RPS_Helper_Student();
        $this->result = new RPS_Helper_Result();

        $this->plugin_slug = RPS_Result_Management::PLUGIN_SLUG;

        $this->DIR = RPS_Result_Management::DIR();
        $this->URL = RPS_Result_Management::URL();
        $this->VER = RPS_Result_Management::VER;

        $this->TD =  RPS_Result_Management::TD;

        $this->options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_api', array() );

        $this->error = array();
        $this->messages = array();


    }



    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {

        $namespace = 'rps_result/v1';
        $base = 'route';

        //search student
        register_rest_route($namespace, '/' . $base . '/student_fields' , array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'student_fields'),
                'args' => array(),
            ),
        ));

        register_rest_route($namespace, '/' . $base . '/search_student' , array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'search_student'),
                'args' => $this->get_student_search_params(),
            )
        ));

        //search result
        register_rest_route($namespace, '/' . $base . '/result_fields' , array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'result_fields'),
                'args' => array(),
            ),
        ));

        register_rest_route($namespace, '/' . $base . '/list_results' , array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'list_results'),
                'args' => $this->get_result_search_params(),
            )
        ));

        register_rest_route($namespace, '/' . $base . '/show_result' , array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'show_result'),
                'args' => $this->get_show_result_params(),
            )
        ));


        register_rest_route($namespace, '/' . $base . '/schema', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_public_item_schema'),
        ));
    }

    public function show_result( $request ) {
        $params = $request->get_params();
        $this->exam_record_id = isset($params['exam_record_id']) ? $params['exam_record_id'] : null;
        $this->student_id = isset($params['student_id']) ? $params['student_id'] : null;

        if ( null === $this->exam_record_id || null === $this->student_id ) {
            //invalid request
            return new WP_Error('invalid-request', __('Invalid Request.', $this->TD), array('status' => 400));
        }

        $exam_record_data = $this->result->getExamRecordDataByExamRecordID( $this->exam_record_id );

        if ( null === $exam_record_data ) {
            return new WP_Error('invalid-request', __('Invalid Exam Record ID.', $this->TD), array('status' => 400));
        }

        $this->department_id = isset( $exam_record_data['department_id'] ) ? $exam_record_data['department_id'] : null;
        $this->batch_id = isset( $exam_record_data['batch_id'] ) ? $exam_record_data['batch_id'] : null;
        $this->semester_id = isset( $exam_record_data['semester_id'] ) ? $exam_record_data['semester_id'] : null;

        global $wpdb;

        $query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `exam_record_id` = %d AND `student_id` = %d", array( $this->exam_record_id, $this->student_id )  );
        $data = $wpdb->get_results( $query, ARRAY_A );

        if ( empty( $data ) ) {
            return new WP_Error('invalid-request', __('No data found.', $this->TD), array('status' => 400));
        }

        //getmetadata
        $metadata = $this->result->get_exam_record_meta( $this->exam_record_id, $this->student_id );
        $department_info = $this->dbs->getDepartmentInfo( $this->department_id );
        $batch_info = $this->dbs->getBatchInfo($this->department_id, $this->batch_id);
        $semester_info = $this->dbs->getSemesterInfo( $this->department_id, $this->semester_id );
        $student_info = $this->student->getSutdentInfo( $this->department_id, $this->batch_id, $this->student_id, $this->semester_id );

        $ret_meta = array();

        $student_meta = $this->options['student_meta'];
        $student_meta = trim( $student_meta );
        $student_meta = trim( $student_meta, ',' );
        $meta_array = explode(',', $student_meta);
        foreach ( $meta_array as $meta) {
            $meta = trim($meta);
            $meta = explode(':', $meta);
            $meta_key = isset($meta[0]) ? trim($meta[0]) : '';
            $meta_value = isset($meta[1]) ? trim($meta[1]) : '';

            if( $meta_key == 'department' ) {
                $ret_meta[ $meta_value ] = isset( $department_info['full_name']) ? strip_tags( $department_info['full_name'] ) : '';

            } elseif ( $meta_key == 'batch' ) {
                $ret_meta[ $meta_value ] =  isset( $batch_info['name'] ) ? strip_tags( $batch_info['name'] ) : '';

            } elseif( $meta_key == 'semester' ) {
                $ret_meta[ $meta_value ] =  isset( $semester_info['name'] ) ? strip_tags( $semester_info['name'] ) : '';

            } else {
                $ret_meta[ $meta_value ] =  isset( $student_info[ $meta_key ] ) ? strip_tags( $student_info[ $meta_key ] ) : '';

            }
        }

        $subject_meta = $this->options['subject_meta'];
        $subject_meta = trim($subject_meta);
        $subject_meta = trim($subject_meta, ',');
        $meta_array = explode(',', $subject_meta);

        $subject_keys = array();
        $subject_values = array();
        $i = 0;
        foreach ( $meta_array as $meta) {
            $meta       = trim( $meta );
            $meta       = explode( ':', $meta );
            $meta_key   = isset($meta[0]) ? trim($meta[0]) : '';
            $meta_value = isset($meta[1]) ? trim($meta[1]) : '';
            $subject_keys[ $i ] = strip_tags( $meta_key );
            $subject_values[ $i ] = strip_tags( $meta_value );
            $i++;
        }

        if ( empty( $subject_values ) ) {

        } else {
            $ret_subject_keys[] = __('SL', $this->TD);

            foreach ( $subject_values as $key => $value ) {
                $ret_subject_keys[] =  $value;
            }
        }

        if ( !empty( $subject_keys ) ) {

            if ( is_array( $data ) && ! empty( $data ) ) {
                $i      = 0;
                $grades = $this->result->getGradeList();
                $grade_points = $this->result->getGradePointList();

                $this->course = new RPS_Helper_Course();

                foreach ( $data as $row ) {

                    $course_info = $this->course->getCourseDetails( $this->department_id, $row['subject_id'], $this->semester_id );

                    if ( is_wp_error( $course_info ) ) {
                        $course_info = $this->course->getCourseInfo( $row['subject_id'] );
                    }

                    $i++;

                    $course_name    = isset ( $course_info['name'] ) ? $course_info['name'] : '';
                    $course_code    = isset ( $course_info['course_code'] ) && $course_info['course_code'] != '' ? $course_info['course_code'] : 'N/A';
                    $total_marks    = isset ( $course_info['total_marks'] ) && $course_info['total_marks'] != '' ? $course_info['total_marks'] : 'N/A';
                    $marks_obtained = $row['marks_obtained'];
                    $grade          = $grades[ $row['grade_id'] ];
                    $grade_point    = $grade_points[ $row['grade_id'] ];

                    $tmp[] =  $i;

                    foreach ( $subject_keys as $key => $value ) {

                        switch ($value) {
                            case 'course_name':
                                $tmp[] = strip_tags( $course_name );
                                break;
                            case 'course_code':
                                $tmp[] = strip_tags( $course_code );
                                break;
                            case 'total_marks':
                                $tmp[] = strip_tags( $total_marks );
                                break;
                            case 'marks_obtained':
                                $tmp[] = strip_tags( $marks_obtained );
                                break;
                            case 'grade':
                                $tmp[] = strip_tags( $grade );
                                break;
                            case 'grade_point':
                                $tmp[] = strip_tags( $grade_point );
                                break;
                            default:
                                $tmp[] = '&nbsp;';
                        }

                    }

                    $ret_subject_values[] = $tmp;
                    $tmp = array();
                }

            }
        } else {

        }

        $result_meta = $this->options['result_meta'];
        $result_meta = trim($result_meta);
        $result_meta = trim($result_meta, ',');
        $meta_array = explode(',', $result_meta);
        $result_keys = array();
        $result_values = array();
        $i = 0;

        foreach ( $meta_array as $meta) {
            $meta       = trim( $meta );
            $meta       = explode( ':', $meta );
            $meta_key   = isset($meta[0]) ? trim($meta[0]) : '';
            $meta_value = isset($meta[1]) ? trim($meta[1]) : '';
            $result_keys[ $i ] = $meta_key;
            $result_values[ $i ] = $meta_value;
            $i++;
        }

        $ret_result_meta = array();

        for( $i = 0; $i < count($result_keys); $i++ ) {
            $ret_result_meta[ $result_values[ $i ] ] = $metadata[ $result_keys[ $i ] ];
        }

        $ret = array(
            'student_meta' => $ret_meta,
            'subject_keys' => $ret_subject_keys,
            'subject_values' => $ret_subject_values,
            'result_meta' => $ret_result_meta
        );

        return new WP_REST_Response($ret, 200);

    }

    public function list_results( $request ) {
        $params = $request->get_params();
        $this->exam_id = isset($params['exam_id']) ? $params['exam_id'] : null;
        $this->department_id = isset($params['department_id']) ? $params['department_id'] : null;
        $this->batch_id = isset( $params['batch_id'] ) ? $params['batch_id'] : null;
        $this->semester_id = isset( $params['semester_id'] ) ? $params['semester_id'] : null ;

        if( RPS_Helper_Function::is_numeric( $this->exam_id ) && RPS_Helper_Function::is_numeric( $this->department_id )
            && RPS_Helper_Function::is_numeric( $this->batch_id ) )
        {
            $student_list = $this->result->getStudentIDsBySearchForm( $this->exam_id, $this->department_id, $this->batch_id, $this->semester_id );

        } else {
            //invalid request
            return new WP_Error('invalid-request', __('Invalid Request.', $this->TD), array('status' => 400));
        }

        if ( !is_wp_error($student_list) ) {
            return new WP_REST_Response($student_list, 200);

        } else {
            //print error message and return
            return new WP_Error('error-getting-data', $student_list->get_error_message(), array('status' => 400));
        }


    }

    public function search_student( $request ) {
        //get parameters from request
        $params = $request->get_params();
        $this->department_id = isset($params['department_id']) ? $params['department_id'] : null;
        $this->batch_id = isset( $params['batch_id'] ) ? $params['batch_id'] : null;
        $this->semester_id = isset( $params['semester_id'] ) ? $params['semester_id'] : null ;


        $student_list = array();
        $department_info = array();
        $batch_info = array();
        $semester_info = array();

        if( RPS_Helper_Function::is_numeric($this->department_id) && RPS_Helper_Function::is_numeric($this->batch_id) &&
            RPS_Helper_Function::is_numeric($this->semester_id) ) {

            $student_list = $this->student->getStudentDetails( $this->department_id, $this->batch_id, $this->semester_id );

            //get department, batch and semester
            $department_info = $this->dbs->getDepartmentInfo( $this->department_id );
            $batch_info = $this->dbs->getBatchInfo( $this->department_id, $this->batch_id );
            $semester_info = $this->dbs->getSemesterInfo( $this->department_id, $this->semester_id );

        } elseif ( RPS_Helper_Function::is_numeric($this->department_id) && RPS_Helper_Function::is_numeric($this->batch_id) ) {

            $student_list = $this->student->getStudentDetails( $this->department_id, $this->batch_id, $this->semester_id );
            $department_info = $this->dbs->getDepartmentInfo( $this->department_id );
            $batch_info = $this->dbs->getBatchInfo( $this->department_id, $this->batch_id );

        } else {
            //invalid request
            return new WP_Error('invalid-request', __('Invalid Request.', $this->TD), array('status' => 400));
        }

        if ( is_wp_error($student_list) ) {
            //print error message and return
            return new WP_Error('error-getting-data', $student_list->get_error_message(), array('status' => 400));
        }

        $data = array();

        foreach ( $student_list as $student ):

            $student['image'] = ($student['image'] != "" ? $student['image'] : ( $student['gender'] === "Male" ?  $this->URL . '/assets/img/user_male.png' : $this->URL . '/assets/img/user_female.png' ) );


            $data[ $student['id'] ]['meta_data'] = $student;


            $student_meta = $this->options['student_meta'];
            $student_meta = trim( $student_meta );
            $student_meta = trim( $student_meta, ',' );
            $meta_array = explode(',', $student_meta);

            foreach ( $meta_array as $meta) {
                $meta = trim($meta);
                $meta = explode(':', $meta);
                $meta_key = isset($meta[0]) ? trim($meta[0]) : '';
                $meta_value = isset($meta[1]) ? trim($meta[1]) : '';


                if( $meta_key == 'department' ) {
                    $meta_data =  isset( $department_info['full_name']) ? strip_tags( $department_info['full_name'] ) : '';

                } elseif ( $meta_key == 'batch' ) {
                    $meta_data =  isset( $batch_info['name'] ) ? strip_tags( $batch_info['name'] ) : '';

                } elseif( $meta_key == 'semester' ) {
                    $meta_data =  isset( $semester_info['name'] ) ? strip_tags( $semester_info['name'] ) : '';

                } else {
                    $meta_data = isset( $student[ $meta_key ] ) ? strip_tags( $student[ $meta_key ] ) : '';

                }

                $data[ $student['id'] ]['meta'][ $meta_value ] = $meta_data;
            }

        endforeach;

        return new WP_REST_Response($data, 200);

    }



    public function student_fields( $request ) {

        //get parameters from request
        $params = $request->get_params();

        $departments = $this->dbs->getDepartmentList();

        $semesters = array();
        $batches = array();

        if( !is_wp_error( $departments ) ) {
            $dept_ids = array_keys( $departments );

            if ( !empty( $dept_ids ) ) :
                foreach ( $dept_ids as $id ):
                    //get batch
                    $batch_data = $this->dbs->getAllBatchListByDepartment( $id );
                    $batches[$id] = is_wp_error($batch_data) ? array() : $batch_data;
                    //get semester
                    $sem = $this->dbs->getSemesterList($id);
                    $semesters[$id] = is_wp_error($sem) ? array() : $sem;
                endforeach;
            endif;

            $select_department      = isset($this->options['department_text'])  ? $this->options['department_text']     : __('Select Department', $this->TD);
            $select_batch           = isset($this->options['batch_text'])       ? $this->options['batch_text']          : __('Select Batch', $this->TD);
            $select_semester        = isset($this->options['semester_text'])    ? $this->options['semester_text']       : __('Select Semester', $this->TD);
            $department_error_text  = isset($this->options['department_error_text'])    ? esc_sql( $this->options['department_error_text'] )    : __('Please Select a Department From List.', $this->TD);
            $batch_error_text       = isset($this->options['batch_error_text'])         ? esc_sql( $this->options['batch_error_text'] )         : __('Please Select a Batch From List.', $this->TD);
            $semester_error_text    = isset($this->options['semester_error_text'])      ? esc_sql( $this->options['semester_error_text'] )      : __('Please Select a Semester From List.', $this->TD);

            $data = array(
                'departments' => $departments,
                'batches' => $batches,
                'semesters' => $semesters,
                'select_department' => $select_department,
                'select_batch'  => $select_batch,
                'select_semester' => $select_semester,
                'department_error_text' => $department_error_text,
                'batch_error_text' => $batch_error_text,
                'semester_error_text' => $semester_error_text
            );

            return new WP_REST_Response( $data, 200 );

        } else {
            return new WP_Error('no-data', __('No data found.', $this->TD), array('status' => 400));
        }

    }

    public function send_response( $data ) {
        $response = rest_ensure_response( $data );

        $response->header("Access-Control-Allow-Origin", get_http_origin());
        $response->header("Access-Control-Allow-Methods", "POST, GET, OPTIONS, PUT, DELETE");
        $response->header("Access-Control-Allow-Credentials", true);

        $response->set_status( 200 );

        return $response;
    }

    public function get_student_search_params() {
        return array(
            'department_id' => array(
                'description' => 'Department ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'batch_id' => array(
                'description' => 'Batch ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'semester_id' => array(
                'description' => 'Semester ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),
            'page' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),

        );
    }

    public function get_show_result_params() {
        return array(
            'exam_record_id' => array(
                'description' => 'Exam Record ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'student_id' => array(
                'description' => 'Student ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'per_page' => array(
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),
            'page' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),

        );
    }

    public function get_result_search_params() {
        return array(
            'exam_id' => array(
                'description' => 'Exam ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'department_id' => array(
                'description' => 'Department ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'batch_id' => array(
                'description' => 'Batch ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
                'required' => true,
            ),
            'semester_id' => array(
                'description' => 'Semester ID.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),
            'page' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => null,
                'sanitize_callback' => 'absint',
            ),

        );
    }

    public function result_fields( $request ) {

        //get parameters from request
        $params = $request->get_params();

        $exam_ids = $this->result->getExamID(1,1);

        if ( !empty($exam_ids) ) {

            $exam_list_data = $this->result->getExamListData();

            $select_exam         = isset($this->options['exam_text']) ? esc_attr( $this->options['exam_text'] ) : __('Select Exam', $this->TD);
            $select_department = isset($this->options['department_text']) ? esc_sql($this->options['department_text']) : __('Select Department', $this->TD);
            $select_batch = isset($this->options['batch_text']) ? esc_sql($this->options['batch_text']) : __('Select Batch', $this->TD);
            $select_semester = isset($this->options['semester_text']) ? esc_sql($this->options['semester_text']) : __('Select Semester', $this->TD);

            $exam_error_text = isset($this->options['exam_error_text']) ? esc_sql($this->options['exam_error_text']) : __('Please Select a Exam From List.', $this->TD);
            $department_error_text = isset($this->options['department_error_text']) ? esc_sql($this->options['department_error_text']) : __('Please Select a Department From List.', $this->TD);
            $batch_error_text = isset($this->options['batch_error_text']) ? esc_sql($this->options['batch_error_text']) : __('Please Select a Batch From List.', $this->TD);
            $semester_error_text = isset($this->options['semester_error_text']) ? esc_sql($this->options['semester_error_text']) : __('Please Select a Semester From List.', $this->TD);

            $data = array(
                'exam_ids' => $exam_ids,
                'exam_list_data' => $exam_list_data,
                'select_exam' => $select_exam,
                'select_department' => $select_department,
                'select_batch' => $select_batch,
                'select_semester' => $select_semester,
                'exam_error_text' => $exam_error_text,
                'department_error_text' => $department_error_text,
                'batch_error_text' => $batch_error_text,
                'semester_error_text' => $semester_error_text
            );

            return new WP_REST_Response($data, 200);

        } else {
            return new WP_Error('no-data', __('No data found.', $this->TD), array('status' => 400));
        }

    }




    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params()
    {
        return array(
            'test' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'page' => array(
                'description' => 'Current page of the collection.',
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description' => 'Maximum number of items to be returned in result set.',
                'type' => 'integer',
                'default' => 10,
                'sanitize_callback' => 'absint',
            ),
            'search' => array(
                'description' => 'Limit results to those matching a string.',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    public function get_public_item_schema() {
        return array();
    }
}