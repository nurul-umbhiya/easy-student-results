<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Init_PostFilters_Student {
    private static $instance;
    private $dbs,$row_data, $TD;
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self;
            self::$instance->dbs = new RPS_Helper_DBS();
            self::$instance->TD = RPS_Result_Management::PLUGIN_SLUG;
            self::$instance->row_data = array();
            self::$instance->actions();
        }
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        
        //load specific js
        add_action('admin_print_scripts-post-new.php', array($this,'loadJs'), 10);
        add_action('admin_print_scripts-post.php', array($this,'loadJs'), 11 );
        
        //load specific css
        add_action('admin_print_styles-post-new.php', array($this,'loadCss'), 10);
        add_action('admin_print_styles-post.php', array($this,'loadCss'), 11 );
        add_action('admin_print_styles-edit.php', array($this,'loadCss'), 12 );
        
        //title filter
        add_filter( 'enter_title_here', array($this,'change_default_title') );
        
        //Creating Filters
        add_action( 'restrict_manage_posts', array($this,'filters') );
        add_filter( 'parse_query', array($this,'parseQuery'),10,1 );
        add_filter( 'months_dropdown_results', '__return_empty_array');
        
        //Manage columns
        add_filter( 'manage_edit-'  . RPS_Result_Management::STUDENT . '_columns',              array($this,'myColumns'), 10, 1 );
        add_action( 'manage_'       . RPS_Result_Management::STUDENT . '_posts_custom_column',  array($this,'manageMyColumns'), 10, 2);

        //remove bulk actions
        add_filter('bulk_actions-edit-' . RPS_Result_Management::STUDENT , '__return_empty_array');
    }
    
    function loadCss() {
        wp_enqueue_style('jquery-ui');

        wp_register_style( RPS_Result_Management::STUDENT,  RPS_Result_Management::URL() . '/assets/css/student.css', array(), RPS_Result_Management::VER );

        wp_enqueue_style( RPS_Result_Management::STUDENT );
    }
    
    function loadJs() {
        wp_enqueue_script('jquery-ui-datepicker');

        wp_register_script( RPS_Result_Management::STUDENT, RPS_Result_Management::URL() . '/assets/js/student.js', array('jquery'), RPS_Result_Management::VER, true );

        wp_localize_script(
            RPS_Result_Management::STUDENT, "data",
            array (
                'title'         => __('Student Name is required.', RPS_Result_Management::TD),
                'reg_no'        => __('Student Registration Number is required.', RPS_Result_Management::TD),
                'roll_no'       => __('Student Roll Number is required.', RPS_Result_Management::TD),
                'department_id' => __('Student Department is required.', RPS_Result_Management::TD),
                'batch_id'      => __('Student Batch is required.', RPS_Result_Management::TD)
            )
        );

        wp_enqueue_script(RPS_Result_Management::STUDENT);
    }
    
    function change_default_title( $title ) {
        $title = __('Enter Student Name Here', $this->TD);
        return $title;
    }
    
    public function filters() {
        $departments = $this->dbs->getDepartmentList();
        if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ) {
            $dept_ids = array_keys($departments);
            //$semesters = array();
            $semesters = array();
            $batches = array();

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
        
            $department_id =  isset($_GET['department_id']) ? stripslashes(trim($_GET['department_id'])) :'';
            //add department filter

            ?>
            <select name="department_id" id="department_id" class="postform">
                <option value=""><?php _e('Select Department'); ?></option>
            <?php
                foreach ($departments as $id => $name):
                    echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                endforeach;
            ?>
            </select>
            <?php

            //add batch filter
            $batch_id = isset($_GET['batch_id']) ? stripslashes(trim($_GET['batch_id'])) :'';
            ?>
            <select name="batch_id" id="batch_id" class="postform">
                <option value=""><?php _e('Select Batch'); ?></option>
            <?php
                if($department_id != '' && RPS_Helper_Function::is_numeric($department_id)) {
                    $batch = isset($batches[$department_id]) ? $batches[$department_id] : '';
                    if(is_array($batch) && !empty($batch)) {
                        foreach ($batch as $key => $name):
                            echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
                        endforeach;
                    }
                } else {
                    $batch = $batches[ $dept_ids[0] ];
                    foreach ($batch as $key => $name):
                        echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
                    endforeach;
                }
            ?>
            </select>

            <?php
            //add semester filter
            $semester_id = isset($_GET['semester_id']) ? stripslashes(trim($_GET['semester_id'])) :'';
            if(!empty($semesters)): ?>
                <select name="semester_id" id="semester_id" class="postform">
                    <option value=""><?php _e('Select Semester'); ?></option>
                    <?php
                    if( $department_id != '' && RPS_Helper_Function::is_numeric($department_id) ) {
                        $semester = isset($semesters[$department_id]) ? $semesters[$department_id] : array();
                        if( is_array($semester) && !empty($semester) ) {
                            foreach ($semester as $id => $name):
                                echo "<option value='{$id}' ".selected($semester_id, $id, false).">{$name}</option>";
                            endforeach;
                        }
                    } else {
                        $semester = $semesters[$dept_ids[0]];
                        foreach ($semester as $id => $name):
                            echo "<option value='{$id}' ".selected($semester_id, $id, false).">{$name}</option>";
                        endforeach;
                    }
                    ?>
                </select>

            <?php endif; ?>


        <script type="text/javascript">        
            jQuery(function($) {
                var semesters = <?php echo json_encode($semesters) ?>;
                var batches = <?php echo json_encode($batches) ?>;

                $('#department_id').on('change',function() {
                    var id = $(this).val();

                    // Popupate semester dropdown box
                    var i = semesters[id];
                    //console.log(i);
                    $('#semester_id option').remove();


                    $('#semester_id')
                        .append($("<option></option>")
                            .attr("value",'')
                            .text('Select Semester'));

                    if(i !== null){
                        $.each(i, function(key, value) {
                            //populate semester dropdown box
                            $('#semester_id')
                                .append($("<option></option>")
                                    .attr("value",key)
                                    .text(value));

                        });
                    }

                    //populate batch dropdown box
                    var j = batches[id];
                    //console.log(j);
                    $('#batch_id option').remove();
                    $( '#batch_id' )
                        .append($("<option></option>")
                            .attr("value",'')
                            .text('Select Batch'));

                    // Add option to the select box from list
                    if ( j !== null ) {
                        $.each( j, function( key, value ) {
                            $( '#batch_id' )
                                .append($("<option></option>")
                                    .attr("value",key)
                                    .text(value));
                        });
                    }


                });
            });
        </script>

        <?php
        }
    }
    
    public function parseQuery($query) {
        global $pagenow;
        $qv = &$query->query_vars;
        
        $department_id  = isset($_GET['department_id']) && $_GET['department_id'] != ''     ? intval(trim($_GET['department_id']))  : '';
        $batch_id       = isset($_GET['batch_id'])      && $_GET['batch_id'] != ''          ? intval(trim($_GET['batch_id']))       : '';
        $semester_id    = isset($_GET['semester_id'])   && $_GET['semester_id'] != ''       ? intval(trim($_GET['semester_id']))    : '';

        if ( $pagenow == 'edit.php' ) {
            if ( RPS_Helper_Function::is_numeric( $department_id ) ) {

                $qv['meta_query'][] = array (
                        'key'     => '_department_id',
                        'value'   => $department_id,
                        'compare' => '=',
                    );

            }

            if ( RPS_Helper_Function::is_numeric( $batch_id ) ) {
                $qv['meta_query'][] = array (
                    'key'       => '_batch_id',
                    'value'     => $batch_id,
                    'compare'   => '='
                );
            }

            if ( RPS_Helper_Function::is_numeric( $semester_id ) ) {
                $qv['meta_query'][] = array (
                    'key'       => '_semester_id',
                    'value'     => $semester_id,
                    'compare'   => '='
                );
            }
        }
    }
    
    //managing columns
    
    public function myColumns($columns) {

        $columns['title'] = 'Student Name';
        $columns['department_id'] = 'Department';
        $columns['batch_id'] = 'Batch';
        $columns['semester_id'] = 'Semester';
        $columns['roll_no'] = "Roll No";
        $columns['reg_no'] = "Reg No";
        
        unset($columns['date']);
        return $columns;
    }
    
    public function manageMyColumns($column_name, $id) {
        global $wpdb;
        if(!array_key_exists($id, $this->row_data)) {
            $this->row_data = array();
            $this->row_data[$id] = get_post_custom($id);
        }
        
        //print_r($data);
        if(!empty($this->row_data[$id])){
            $reg_no = isset($this->row_data[$id]['_reg_no'][0]) ? $this->row_data[$id]['_reg_no'][0] : 'N/A';
            $roll_no = isset($this->row_data[$id]['_roll_no'][0]) ? $this->row_data[$id]['_roll_no'][0] : 'N/A';
            $department_id = isset($this->row_data[$id]['_department_id'][0]) ? $this->row_data[$id]['_department_id'][0] : "N/A";
            $batch_id = isset($this->row_data[$id]['_batch_id'][0]) ? $this->row_data[$id]['_batch_id'][0] : 'N/A';
            $semester_id = isset($this->row_data[$id]['_semester_id'][0]) ? $this->row_data[$id]['_semester_id'][0] : 'N/A';
        }
        else {
            $reg_no = "";
            $roll_no = "";
            $department_id = "";
            $batch_id = "";
        }
        
        switch ($column_name):
            
        case 'department_id':
            $info = $this->dbs->getDepartmentInfo($department_id);
            if( !is_wp_error($info) )
                echo $info['name'];
            else 
                echo __("N/A", $this->TD);
            break;
        
        case 'batch_id':
            $info = $this->dbs->getBatchInfo($department_id,$batch_id);
            if( !is_wp_error($info) )
                echo $info['name'];
            else 
                echo __("N/A", $this->TD);
            break;
        
        case 'semester_id':
            $info = $this->dbs->getSemesterInfo($department_id, $semester_id);
            if( !is_wp_error($info) )
                echo $info['name'];
            else 
                echo __("N/A", $this->TD);
            break;
        
        case 'reg_no':
            echo $reg_no;
            break;
        
        case 'roll_no':
            echo $roll_no;
            break;
        
        endswitch;
    }
}

