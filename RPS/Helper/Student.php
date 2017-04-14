<?php
if( ! defined( 'WPINC' ) ) {
    die();
}
/**
 * Use function getStudentList($department_id=null,$batch_id=null) to get student lists for a given department and batch as student_id => student_roll 
 * Use function getStudentInfo($student_id) to get student details by id. Information will be provided by get_post_meta() function.
 * Use function getSutdentDetails($department_id,$batch_id, $student_id) to get student details. Information will be provided form cache.
 */
class RPS_Helper_Student {

    public function __construct() {
        //$ret = $this->getStudentData(1, 1);
        //echo "<pre>"; print_r($ret); echo "</pre>";

    }
    
    /**
     * This function will return
     * @internal this function is internal
     * @global object $wpdb
     * @param int $department_id
     * @param int $batch_id
     * @return array
     */
    private function getStudentData( $department_id, $batch_id, $semester_id = null ) {
        if ( $semester_id === null || !RPS_Helper_Function::is_numeric($semester_id) )
            $transient = RPS_Result_Management::PLUGIN_SLUG . "_student_list_{$department_id}_{$batch_id}" ;
        elseif ( RPS_Helper_Function::is_numeric($semester_id)  )
            $transient = RPS_Result_Management::PLUGIN_SLUG . "_student_list_{$department_id}_{$batch_id}_{$semester_id}" ;

        //delete_transient($transient);

        $ret = array();
        
        if (false === ($ret = get_transient($transient) ) ) {
            global $wpdb;

            /*
            $query_args = array( 'meta_query' => array(
                'relation' => 'AND',
                array(
                        'key' => '_department_id',
                        'value' => $department_id,
                        'compare' => '=',
                ),
                array(
                        'key' => '_batch_id',
                        'value' => $batch_id,
                        'compare' => '=',
                ),
            ) );
            */

            $query_args['meta_query']['relation'] = 'AND';

            $query_args['meta_query'][] = array(
                'key' => '_department_id',
                'value' => $department_id,
                'compare' => '=',
            );

            $query_args['meta_query'][] = array(
                'key' => '_batch_id',
                'value' => $batch_id,
                'compare' => '=',
            );

            if ( RPS_Helper_Function::is_numeric($semester_id) && $semester_id ) {
                $query_args['meta_query'][] = array(
                    'key' => '_semester_id',
                    'value' => $semester_id,
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
            $post_type = RPS_Result_Management::STUDENT;
            $query = "SELECT ID, post_title FROM {$wpdb->posts} {$mq_sql['join']} WHERE post_type='{$post_type}' and post_status='publish' {$mq_sql['where']}" ;
            $results = $wpdb->get_results($query,ARRAY_A);
            
            if( ! empty( $results ) ) :
                foreach ( $results as $row ) :
                    $data           = get_post_custom($row['ID']);
                    $reg_no         = isset($data['_reg_no'][0]) ? $data['_reg_no'][0] : '';
                    $roll_no        = isset($data['_roll_no'][0]) ? $data['_roll_no'][0] : '';
                    $lib_card       = isset($data['_lib_card'][0]) ? $data['_lib_card'][0] : '';
                    $department_id  = isset($data['_department_id'][0]) ? $data['_department_id'][0] : '';
                    $batch_id       = isset($data['_batch_id'][0]) ? $data['_batch_id'][0] : '';
                    $semester_id    = isset($data['_semester_id'][0]) ? $data['_semester_id'][0] : '';
                    
                    $personal_info  = maybe_unserialize(maybe_unserialize($data['_student_personal_info'][0]));
                    $contact_info   = maybe_unserialize(maybe_unserialize($data['_student_contact_info'][0]));
                    //$education_info = maybe_unserialize(maybe_unserialize($data['_student_education_info'][0]));
                    
                    $faculty_info = array (
                        'id'            => $row['ID'],
                        'name'          => $row['post_title'],
                        'department_id' => $department_id,
                        'batch_id'      => $batch_id,
                        'semester_id'   => $semester_id,
                        'registration_no' => $reg_no,
                        'roll_no'       => $roll_no,
                        'library_card'  => $lib_card
                    );
                    
                    $tmp = array();
                    if(!empty($faculty_info)) $tmp = array_merge ($faculty_info);
                    if(!empty($personal_info)) $tmp = array_merge ($tmp,$personal_info);
                    if(!empty($contact_info)) $tmp = array_merge ($tmp,$contact_info);
                    //if(!empty($education_info)) $tmp = array_merge ($tmp,$education_info);

                    //get featured image
                    if (has_post_thumbnail( $row['ID'] ) ):
                        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $row['ID'] ) );
                        $tmp['image'] = $image[0];
                    else:
                        $tmp['image'] = '';
                    endif;

                    $ret[$row['ID']] = $tmp;
                    
                endforeach;
            else:
                return new \WP_Error("Empty Row",__("No Student found.",RPS_Result_Management::TD));
            endif;    
            
            set_transient($transient, $ret,'WEEK_IN_SECONDS');

            $transient_keys = get_option( 'rps_result_transient_keys', array() );
            if( !array_key_exists($transient, $transient_keys) ) {
                $transient_keys[] = $transient;
                update_option( 'rps_result_transient_keys', $transient_keys );
            }
            
        }
        //echo "<pre>"; print_r($ret); echo "</pre>";
        return $ret;
    }
    
    /**
     * This function will return student list for a given department and batch
     * @param int $department_id
     * @param int $batch_id
     * @param int $semester_id
     * @return array List of students as id => roll_no pair
     */
    public function getStudentList( $department_id, $batch_id, $semester_id = null ) {
        $ret = array();
        $data = array();
        if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) && RPS_Helper_Function::is_numeric( $semester_id ) ) {

            $data = $this->getStudentData( $department_id, $batch_id, $semester_id );

        } elseif ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) ) {

            $data = $this->getStudentData( $department_id, $batch_id );

        } else {
            return new \WP_Error('Invalid Argument',__("Given arguments are invalid. Please provide valid argument.", RPS_Result_Management::TD ));
        }
            
        if( !is_wp_error( $data ) ) {
            foreach ($data as $id => $details):
                $ret[$id] = $details['roll_no'];
            endforeach;

            return $ret;
        }
        else {
            return $data;
        }
    }


    public function getStudentDetails( $department_id, $batch_id, $semester_id = null ) {
        $data = array();

        if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) && RPS_Helper_Function::is_numeric( $semester_id ) ) {

            $data = $this->getStudentData( $department_id, $batch_id, $semester_id );

        } elseif ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) ) {

            $data = $this->getStudentData( $department_id, $batch_id );

        } else {
            return new \WP_Error('Invalid Argument',__("Given arguments are invalid. Please provide valid argument.", RPS_Result_Management::TD ));
        }

        return $data;
    }

    
    /**
     * This function will return a particular student information from cache 
     * @param int $department_id
     * @param int $batch_id
     * @param int $student_id
     * @param int $semester_id
     * @return array
     */
    public function getSutdentInfo( $department_id, $batch_id, $student_id, $semester_id = null ) {

        if ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) && RPS_Helper_Function::is_numeric( $semester_id ) ) {
            $data = $this->getStudentData( $department_id, $batch_id );

        } elseif ( RPS_Helper_Function::is_numeric( $department_id ) && RPS_Helper_Function::is_numeric( $batch_id ) ) {
            $data = $this->getStudentData( $department_id, $batch_id );

        } else {
            return new \WP_Error('Invalid Argument',__("Given arguments are invalid. Please provide valid arguments.",RPS_Result_Management::TD));

        }

        if ( !is_wp_error( $data ) && RPS_Helper_Function::is_numeric( $student_id ) &&  array_key_exists( $student_id, $data ) ) {
            return $data[$student_id];

        } elseif ( is_wp_error( $data ) ) {
            return $data;

        } elseif ( !RPS_Helper_Function::is_numeric( $student_id ) )  {
            return new \WP_Error('Invalid Student ID',__("Student ID is invalid. Please provide a valid Student ID.",RPS_Result_Management::TD));

        }
    }
    
    /**
     * This function will return a particular student information
     * @param int $student_id
     * @return array
     */
    public function getStudentInfo2( $student_id ) {

        if( RPS_Helper_Function::is_numeric( $student_id ) ) {
            $tmp = array();
            $post = get_post($student_id);
            if ( null === $post )
                return new \WP_Error('Invalid Student ID',__("Given post ID is invalid. Please provide a valid Student ID.", RPS_Result_Management::TD));

            $data = get_post_custom($student_id);


            $reg_no         = isset($data['_reg_no'][0]) ? $data['_reg_no'][0] : '';
            $roll_no        = isset($data['_roll_no'][0]) ? $data['_roll_no'][0] : '';
            $lib_card       = isset($data['_lib_card'][0]) ? $data['_lib_card'][0] : '';
            $department_id  = isset($data['_department_id'][0]) ? $data['_department_id'][0] : '';
            $batch_id       = isset($data['_batch_id'][0]) ? $data['_batch_id'][0] : '';
            $semester_id    = isset($data['_semester_id'][0]) ? $data['_semester_id'][0] : '';

            $personal_info  = maybe_unserialize(maybe_unserialize($data['_student_personal_info'][0]));
            $contact_info   = maybe_unserialize(maybe_unserialize($data['_student_contact_info'][0]));
            //$education_info = maybe_unserialize(maybe_unserialize($data['_student_education_info'][0]));

            $faculty_info = array (
                'id'            => $post->ID,
                'name'          => $post->post_title,
                'department_id' => $department_id,
                'batch_id'      => $batch_id,
                'semester_id'   => $semester_id,
                'registration_no' => $reg_no,
                'roll_no'       => $roll_no,
                'library_card'  => $lib_card
            );

            $tmp = array();
            if(!empty($faculty_info)) $tmp = array_merge ($faculty_info);
            if(!empty($personal_info)) $tmp = array_merge ($tmp,$personal_info);
            if(!empty($contact_info)) $tmp = array_merge ($tmp,$contact_info);

            $tmp['ID'] = $student_id;
            return $tmp;
            
        }
        else {
            return new \WP_Error('Invalid Argument',__("Given argument is invalid. Please provide a valid Student ID.", RPS_Result_Management::TD));
        }
    }

    public function getStudentByRoll($department_id, $batch_id, $semester_id, $student_roll) {

        $data = $this->getStudentDetails($department_id, $batch_id, $semester_id);

        if ( !is_wp_error($data) && is_array($data) && !empty($data) ) {

            foreach ( $data as $stu) {
                if ( array_key_exists('roll_no', $stu) && $stu['roll_no'] == $student_roll ) {
                    return $stu;
                    break;
                }
            }

        }

        return new \WP_Error('No Student',__("No student found.", RPS_Result_Management::TD));
    }

    public function getStudentByRegNo($department_id, $batch_id, $semester_id, $registration_no) {

        $data = $this->getStudentDetails($department_id, $batch_id, $semester_id);

        if ( !is_wp_error($data) && is_array($data) && !empty($data) ) {

            foreach ( $data as $stu) {
                if ( array_key_exists('registration_no', $stu) && $stu['registration_no'] == $registration_no ) {
                    return $stu;
                    break;
                }
            }

        }

        return new \WP_Error('No Student',__("No student found.", RPS_Result_Management::TD));
    }
}

/*
//example response data
Array
(
    [46] => Array
        (
            [name] => Test
            [department_id] => 1
            [batch_id] => 1
            [semester_id] => 7
            [registration_no] => 12
            [roll_no] => 121
            [library_card] =>
            [f_name] =>
            [m_name] =>
            [dob] =>
            [email] =>
            [stu_phone] =>
            [blood_group] =>
            [religion] =>
            [nationality] =>
            [present_address] =>
            [permanent_address] =>
            [gaurdian_name] =>
            [gaurdian_address] =>
            [gaurdian_contact_no] =>
            [gaurdian_email] =>
        )

    [81] => Array
        (
            [name] => Test 2
            [department_id] => 1
            [batch_id] => 1
            [semester_id] => 5
            [registration_no] => 122
            [roll_no] => 13
            [library_card] =>
            [f_name] =>
            [m_name] =>
            [dob] =>
            [email] =>
            [stu_phone] =>
            [blood_group] =>
            [religion] =>
            [nationality] =>
            [present_address] =>
            [permanent_address] =>
            [gaurdian_name] =>
            [gaurdian_address] =>
            [gaurdian_contact_no] =>
            [gaurdian_email] =>
        )

)
 */

