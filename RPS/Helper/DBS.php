<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Helper_DBS {
    private $TD;
    public function __construct() {
        $this->TD = RPS_Result_Management::TD;
    }
    
    //####    department   ####//
    
    private function getDepartmentData() {
        $transient = RPS_Result_Management::PLUGIN_SLUG . '_department_list';
        //delete_transient( $transient );
        if ( false === ( $ret = get_transient( $transient ) ) ) {
            global $wpdb;
            $query = "SELECT * FROM {$wpdb->rps_department}";
            $results = $wpdb->get_results($query,ARRAY_A);
            $ret = array();
            if ( is_array($results) && !empty($results) ):
                foreach ( $results as $row ) {
                    $id = $row['id'];
                    unset($row['id']);
                    $ret[$id]  = $row;

                }

                set_transient( $transient, $ret, WEEK_IN_SECONDS );

                $transient_keys = get_option( 'rps_result_transient_keys', array() );
                if( !array_key_exists($transient, $transient_keys) ) {
                    $transient_keys[] = $transient;
                    update_option( 'rps_result_transient_keys', $transient_keys );
                }

            endif;    
        }
        //echo "<pre>"; print_r($ret); echo '</pre>';
        return $ret;
    }//end of getDepartmentList()
    
    /**
     * 
     * @param int $department_id
     * @return \WP_Error
     * @return Array Department information
     */
    public function getDepartmentInfo($department_id) {
        $list = $this->getDepartmentData();
        
        if ( is_array( $list ) && RPS_Helper_Function::is_numeric( $department_id ) && array_key_exists( $department_id, $list ) ) {
            return $list[ $department_id ];
        }
        elseif ( $list === '' || $list === NULL  || empty( $list ) ) {
            return new \WP_Error(__('Empty Results', $this->TD),sprintf(__("No records found with department id %s.", $this->TD), $department_id) );
        }
        else {
            return new \WP_Error(__('Invalid Argument', $this->TD) , __("Invalid department id.",  $this->TD));
        }
    }
    
    /**
     * 
     * @param Boolean $active
     * @return \WP_Error
     * @return Array department_id => department_name
     */
    public function getDepartmentList($active=true) {
        $list = $this->getDepartmentData();
        if( $list === '' || $list === NULL  || empty($list)):
            return new \WP_Error(__('Empty Results', $this->TD),__("No department found.", $this->TD));
        else:
            $ret = array();

            foreach ($list as $dept_id => $department):
                if ($active === true) {
                    if($department['active'] === '1') {
                        $ret[$dept_id] = $department['name'];
                    }
                } else {
                    if($department['active'] === '0') {
                        $ret[$dept_id] = $department['name'];
                    }
                }
            endforeach;

            return $ret;
        endif;
    }
    
    //#####   Semester   ####//
    
    function getSemesterData() {
        $transient = RPS_Result_Management::PLUGIN_SLUG . '_semester_list';
        //delete_transient($transient);
        if ( false === ( $ret = get_transient( $transient ) ) ) {
            global $wpdb;
            $query = "SELECT id,slug FROM `{$wpdb->rps_department}`";
            $results = $wpdb->get_results($query,ARRAY_A);
            $ret = array();
            if ( !empty( $results ) ):

                foreach ( $results as $row ):
                    $slug = RPS_Result_Management::PLUGIN_SLUG . '_' . $row['slug'];
                    $args = array(
                            'orderby'       => 'date', 
                            'order'         => 'ASC',
                            'hide_empty'    => false, 
                            'fields'        => 'all', 
                            'hierarchical'  => false, 
                        );
                    $terms = get_terms( $slug, $args );
                    
                    if( !is_wp_error( $terms ) && is_array( $terms ) ):
                        foreach ( $terms as $term ) {
                            $semester = array (
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                            );
                            $ret[ $row['id'] ][ $term->term_id ] = $semester;

                        }
                    endif;
                endforeach;

                set_transient( $transient, $ret, WEEK_IN_SECONDS );

                $transient_keys = get_option( 'rps_result_transient_keys', array() );
                if( !array_key_exists($transient, $transient_keys) ) {
                    $transient_keys[] = $transient;
                    update_option( 'rps_result_transient_keys', $transient_keys );
                }

            endif;
            
        }
        //echo "<pre>"; print_r($ret); echo '</pre>';        
        return $ret;
    }//end of  getSemesterList()
    
    /**
     * This function will return all semester record by department id
     * @param int $department_id
     * @return \WP_Error if error occured
     * @return array semester information array
     */
    public function getSemesterInfoByDept($department_id) {
        $list = $this->getSemesterData();
        if( $list === '' || $list === NULL  || empty($list)) {
            return new \WP_Error(__('Empty Results', $this->TD),sprintf(__("No semester found with department id %s.", $this->TD), $department_id ));
        } elseif( RPS_Helper_Function::is_numeric($department_id) && array_key_exists($department_id, $list) ) {
            return $list[$department_id];
        }
        else {
            return new \WP_Error( __('Invalid Argument', $this->TD), __("Please provide valid department_id",  $this->TD));
        }
    }

    public function getSemesterInfoByID( $semester_id ) {

    }
    
    /**
     * This function will return a specific semester record by department_id and semester_id
     * @param int $department_id
     * @param int $semester_id
     * @return \WP_Error if error occured
     * @return array A single semester record
     */
    
    public function getSemesterInfo($department_id, $semester_id) {
        $list = $this->getSemesterData();
        
        if( $list === '' || $list === NULL  || empty($list)) {
            return new \WP_Error(__('Empty Results', $this->TD), sprintf(__("No semester found with department id %s.", $this->TD),$department_id));
        }
        elseif( RPS_Helper_Function::is_numeric($department_id) && RPS_Helper_Function::is_numeric($semester_id) && array_key_exists($department_id, $list)
                && array_key_exists($semester_id, $list[$department_id]) ) {
            return $list[$department_id][$semester_id];
        }
        else {
            return new \WP_Error(__('Invalid Argument', $this->TD), __("Please provide valid department_id and semester_id",  $this->TD));
        }
    }
    
    /**
     * Get all semester list of a specific department
     * @param int $department_id
     * @return array all semesters with id => name pair
     * @return \WP_Error if error occured or empty data
     */
    public function getSemesterList($department_id) {
        if(RPS_Helper_Function::is_numeric($department_id)) {
            $list = $this->getSemesterInfoByDept($department_id);
            if(is_wp_error($list)) {
                return $list;
            }
            
            $ret = array();
            
            foreach($list as $id => $semester):
                $ret[$id] = $semester['name'];
            endforeach;

            return $ret;
        }
        else {
            return new \WP_Error(__('Invalid Argument', $this->TD), __("Given argument is invalid. Please provide a valid department_id", $this->TD));
        }
    }
    
    
    //####    Batch    ####//
    
    /**
     * Get All Batch List for a department
     * @global object $wpdb
     * @param int $department_id
     * @return null
     */
    private function getBatchData($department_id) {
        $transient = RPS_Result_Management::PLUGIN_SLUG . "_batch_list_{$department_id}";
        //delete_transient($transient);
        if ( false === ( $ret = get_transient( $transient ) ) ) {
            global $wpdb;
            $query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_batch} WHERE department_id=%d",array($department_id));
            $results = $wpdb->get_results($query,ARRAY_A);
            $ret = array();
            if( !empty($results) ):
                foreach ($results as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    $ret[$id] = $row;
                }

                set_transient( $transient, $ret, WEEK_IN_SECONDS );

                $transient_keys = get_option( 'rps_result_transient_keys', array() );
                if( !array_key_exists($transient, $transient_keys) ) {
                    $transient_keys[] = $transient;
                    update_option( 'rps_result_transient_keys', $transient_keys );
                }

            endif;
        }
        
        //echo "<pre>"; print_r($ret); echo '</pre>';
        
        return $ret;  
    }
    
    /**
     * Get batch details of a specific batch
     * @param int $department_id
     * @param int $batch_id
     * @return array (batch details)
     * @return null if no batch information found
     */
    public function getBatchInfo($department_id=null, $batch_id=null) {
        if( RPS_Helper_Function::is_numeric($department_id) && RPS_Helper_Function::is_numeric($batch_id) ) {
            $list = $this->getBatchData($department_id);
        
            if( $list === '' || $list === NULL  || empty($list)) {
                return new \WP_Error(__('Empty Results', $this->TD), sprintf(__("No batch found with department id %s and %s.", $this->TD), $department_id, $batch_id));
            }
            elseif (is_array($list) && array_key_exists($batch_id, $list) ) {
                    return $list[$batch_id];
            } else {
                    return new \WP_Error('Invalid Argument', __("Given argument is invalid. Please provide a valid batch id.", $this->TD));
            }
        } else {
            return new \WP_Error('Invalid Argument', __("Given arguments are invalid. Please provide valid department id and batch id.", $this->TD));
        }
    }
    
    /**
     * Get all batch list of a specific department
     * @param int $department_id
     * @param boolean $active
     * @return \WP_Error in case of error
     * @return Array Associative Array where batch_id is key and batch_name is value..  
     */
    public function getBatchListByDepartment($department_id = null , $active=true){
        if (RPS_Helper_Function::is_numeric($department_id)) {
            $list = $this->getBatchData($department_id);
            if( $list === '' || $list === NULL  || empty($list)) {
                return new \WP_Error(__('Empty Results', $this->TD), sprintf(__("No batch found with department id %s and %s.", $this->TD), $department_id, $batch_id));
            } else {
                $ret = array();
                foreach ($list as $id => $row):
                    if ($active == true) {
                        if ($row['active'] === '1') {
                            $ret[$id] = $row['name'];
                        }
                    } else {
                        if ($row['active'] === '0') {
                            $ret[$id] = $row['name'];
                        }
                    }
                endforeach;
                return $ret;
            }
        } else {
            return new \WP_Error('Invalid Argument', __("Given Department ID is invalid. Please provide valid department ID.", $this->TD));
        }
    }
    
    public function getAllBatchListByDepartment($department_id = null) {
        if(RPS_Helper_Function::is_numeric($department_id)) {
            $list = $this->getBatchData($department_id);
            if( $list === '' || $list === NULL  || empty($list)) {
                return new \WP_Error(__('Empty Results', $this->TD), sprintf(__("No batch found with department id %s.", $this->TD), $department_id));
            } else {
                $ret = array();
                foreach ($list as $id => $row):
                    $ret[$id] = $row['name'];
                endforeach;
                return $ret;
            }
        } else {
            return new \WP_Error('Invalid Argument', __("Given Department ID is invalid. Please provide valid department ID.", $this->TD));
        }
    }
    
    public function getBatchInfoById($batch_id) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_batch} WHERE id=%d",array($batch_id));
        $row = $wpdb->get_row($query,ARRAY_A);
        return $row;
    }
    
    public function getCurrentSemesters($active=true) {
        
        
    }
    
    
    
}

