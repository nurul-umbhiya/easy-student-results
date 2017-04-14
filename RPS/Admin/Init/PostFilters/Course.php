<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Init_PostFilters_Course {
    private static $instance;
    private $TD, $row_data, $dbs;
    public static function getInstance() {
        if(self::$instance==null){
            self::$instance = new self;
            self::$instance->dbs = new RPS_Helper_DBS();
            self::$instance->TD = \RPS_Result_Management::PLUGIN_SLUG;
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
        add_filter( 'manage_edit-'  . RPS_Result_Management::COURSE . '_columns',              array($this,'myColumns'), 10, 1 );
        add_action( 'manage_'       . RPS_Result_Management::COURSE . '_posts_custom_column',  array($this,'manageMyColumns'), 10, 2);

        //remove bulk actions
        add_filter('bulk_actions-edit-' . RPS_Result_Management::COURSE , '__return_empty_array');
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
                    var semesters = <?php echo json_encode($semesters) ?>

                    $('#student_faculty_meta').on('#department_id', 'change', function() {

                        //$('#department_id').on('change',function() {
                        var id = $(this).val();

                        // Popupate semester dropdown box
                        var i = semesters[id];
                        console.log(i);
                        $('#semester_id option').remove();


                        if(i !== null){
                            $.each(i, function(key, value) {
                                //populate semester dropdown box
                                $('#semester_id')
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
        $semester_id    = isset($_GET['semester_id'])   && $_GET['semester_id'] != ''       ? intval(trim($_GET['semester_id']))    : '';

        if ( $pagenow == 'edit.php' ) {
            if ( RPS_Helper_Function::is_numeric( $department_id ) ) {

                $qv['meta_query'][] = array (
                    'key'     => '_department_id',
                    'value'   => $department_id,
                    'compare' => '=',
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

    public function myColumns( $columns ) {
        $columns['title']       = __('Course Title', RPS_Result_Management::TD);
        $columns['course_code'] = __('Course Code', RPS_Result_Management::TD);
        $columns['course_type'] = __("Course Type", RPS_Result_Management::TD);
        $columns['department_id'] = __('Department', RPS_Result_Management::TD);
        $columns['semester_id'] = __('Semester', RPS_Result_Management::TD);

        unset($columns['date']);
        return $columns;
    }

    public function manageMyColumns($column_name, $id) {
        global $wpdb;

        if( !is_array($this->row_data) || !array_key_exists($id, $this->row_data)) {
            $this->row_data = array();
            $this->row_data[$id] = get_post_custom($id);
        }

        //print_r($data);
        if(!empty($this->row_data[$id])){
            $details      = maybe_unserialize( $this->row_data[$id]['_course_details'][0] );

            $course_code = isset($this->row_data[$id]['_course_code'][0]) ? $this->row_data[$id]['_course_code'][0] : 'N/A';
            $course_type = isset($details['course_type']) ? RPS_Helper_Function::getCourseType($details['course_type']) : 'N/A';
            $department_id = isset($this->row_data[$id]['_department_id'][0]) ? $this->row_data[$id]['_department_id'][0] : "N/A";
            $semester_id = isset($this->row_data[$id]['_semester_id'][0]) ? $this->row_data[$id]['_semester_id'][0] : 'N/A';
        }
        else {
            $course_code = "";
            $course_type = "";
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

            case 'semester_id':
                $info = $this->dbs->getSemesterInfo($department_id, $semester_id);
                if( !is_wp_error($info) )
                    echo $info['name'];
                else
                    echo __("N/A", $this->TD);
                break;

            case 'course_code':
                echo $course_code;
                break;

            case 'course_type':
                echo $course_type;
                break;

        endswitch;
    }


    
    function loadCss() {
        wp_enqueue_style('jquery-ui');
        wp_register_style( RPS_Result_Management::COURSE,  RPS_Result_Management::URL() . '/assets/css/courses.css', array(), RPS_Result_Management::VER );

        wp_enqueue_style( RPS_Result_Management::COURSE );
    }
    
    function loadJs() {
        wp_enqueue_script('jquery-ui-datepicker');

        wp_register_script( RPS_Result_Management::COURSE, RPS_Result_Management::URL() . '/assets/js/course.js', array('jquery'), RPS_Result_Management::VER, true );

        wp_localize_script(
            RPS_Result_Management::COURSE, "data",
            array (
                'title'         => __('Course Name is required.', RPS_Result_Management::TD),
                'course_code'   => __('Course Code is required.', RPS_Result_Management::TD),
                'department_id' => __('Department ID is required.', RPS_Result_Management::TD)
            )
        );

        wp_enqueue_script( RPS_Result_Management::COURSE );
    }
    
    function change_default_title( $title ) {
        $title = __("* Enter Course Name Here", $this->TD);
        return $title;
    }
    
}

