<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Init_PostType {
    private static $instance;
    
    public static function getInstance() {
        if(self::$instance==null) {
            self::$instance = new self;
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        add_action( 'init', array( $this, 'students' ), 8 );
        add_action( 'init', array( $this, 'courses' ), 9 );

        //prevent deleting posts
        add_action('wp_trash_post',         array( $this, 'restrict_post_deletion' ), 10, 1);
        add_action('before_delete_post',    array( $this, 'restrict_post_deletion' ), 10, 1);

        //remove trash link
        add_filter( 'post_row_actions',     array( $this, 'remove_trash_link' ), 10, 2  );

        //remove Move to Trash link from post edit page
        //add_action( 'post_submitbox_start', array( $this, 'remove_delete_link' ) );

        //change Featured Image title
        add_action('do_meta_boxes', array( $this, 'change_image_box' ) );
    }


    public function change_image_box() {
        remove_meta_box( 'postimagediv', RPS_Result_Management::STUDENT, 'side' );
        add_meta_box( 'postimagediv', __('Student Photo', RPS_Result_Management::PLUGIN_SLUG), 'post_thumbnail_meta_box', RPS_Result_Management::STUDENT, 'side', 'high');
    }

    public function restrict_post_deletion( $post_ID ) {

        $post_arr = array( RPS_Result_Management::COURSE, RPS_Result_Management::STUDENT );

        if ( in_array( get_post_type( $post_ID ), $post_arr ) ) {
            //check entry exist or not
            global $wpdb;
            if ( get_post_type( $post_ID ) === RPS_Result_Management::COURSE  ) {
                $query = $wpdb->prepare("SELECT id FROM `{$wpdb->rps_marks}` WHERE subject_id = %d LIMIT 1", array( $post_ID ));
            } else {
                $query = $wpdb->prepare("SELECT id FROM `{$wpdb->rps_marks}` WHERE student_id = %d LIMIT 1", array( $post_ID ));
            }

            if( null ===  $wpdb->get_var($query)) {
                return;
            } else {
                wp_die(__("You are not authorized to delete this post. Entry exist on result table.", RPS_Result_Management::TD ) );
            }
        }
    }

    public function remove_trash_link( $actions, $post ) {

        $post_arr = array( RPS_Result_Management::COURSE, RPS_Result_Management::STUDENT );

        if ( in_array( get_post_type( $post->ID ), $post_arr ) ) {
            unset( $actions['trash'] );
            unset( $actions['view'] );
        }

        return $actions;

    }

    public function remove_delete_link() {

        global $typenow;

        // when editing pages, $typenow isn't set until later!
        if (empty($typenow)) {
            // try to pick it up from the query string
            if (!empty($_GET['post'])) {
                $post = get_post($_GET['post']);
                $typenow = $post->post_type;
            }
            // try to pick it up from the quick edit AJAX post
            elseif (!empty($_POST['post_ID'])) {
                $post = get_post($_POST['post_ID']);
                $typenow = $post->post_type;
            }
        }

        $post_arr = array( RPS_Result_Management::COURSE, RPS_Result_Management::STUDENT );

        if ( in_array( $typenow, $post_arr ) ) {
            ?>
            <style>
                #delete-action {
                    display: none;
                }
            </style>
            <?php
        }

    }
    
    public function courses() {
        register_post_type(RPS_Result_Management::COURSE,
            array(
                'label'                     => 'Courses',
                'labels' => array (
                    'name'                  => __( 'Courses', RPS_Result_Management::TD ),
                    'singular_name'         => __( 'Course', RPS_Result_Management::TD ),
                    'menu_name'             => _x( 'Courses', 'Admin menu name', RPS_Result_Management::TD ),
                    'add_new'               => __( 'Add Course', RPS_Result_Management::TD ),
                    'add_new_item'          => __( 'Add New course', RPS_Result_Management::TD ),
                    'edit'                  => __( 'Edit', RPS_Result_Management::TD ),
                    'edit_item'             => __( 'Edit Course', RPS_Result_Management::TD ),
                    'new_item'              => __( 'New Course', RPS_Result_Management::TD ),
                    'view'                  => __( 'View Course', RPS_Result_Management::TD ),
                    'view_item'             => __( 'View Course', RPS_Result_Management::TD ),
                    'search_items'          => __( 'Search Courses', RPS_Result_Management::TD ),
                    'not_found'             => __( 'No Courses found', RPS_Result_Management::TD ),
                    'not_found_in_trash'    => __( 'No Courses found in trash', RPS_Result_Management::TD ),
                    'parent'                => __( 'Parent Course', RPS_Result_Management::TD )
                ),

                'description'               => __( 'This is where you can add new courses.', RPS_Result_Management::TD ),
                'show_in_menu'              => RPS_Result_Management::PLUGIN_SLUG,
                //'capability_type'         => 'ustc_course',
                //'map_meta_cap'            => true,
                //'publicly_queryable'      => false,
                'public'                    => true,
                'show_ui'                   => true,
                'exclude_from_search'       => true,
                'hierarchical'              => false, // Hierarchical causes memory issues - WP loads all records!
                //'rewrite'                 => array('slug'=>'rps_result_course'),
                'supports'                  => array('title'),
                'has_archive'               => false,
                'show_in_nav_menus'         => false,
                'menu_icon'                 => '<div id="icon-edit" class="icon32 icon32-posts-post"></div>'
            )
        );
    }
    
    public function students() {
        register_post_type(RPS_Result_Management::STUDENT,
            array(
                'label'                 => 'Students',
                'labels' => array(
                        'name'                  => __( 'Students', RPS_Result_Management::TD ),
                        'singular_name'         => __( 'Student', RPS_Result_Management::TD ),
                        'menu_name'             => _x( 'Students', 'Admin menu name', RPS_Result_Management::TD ),
                        'add_new'               => __( 'Add Student', RPS_Result_Management::TD ),
                        'add_new_item'          => __( 'Add New Student', RPS_Result_Management::TD ),
                        'edit'                  => __( 'Edit', RPS_Result_Management::TD ),
                        'edit_item'             => __( 'Edit Student', RPS_Result_Management::TD ),
                        'new_item'              => __( 'New Student', RPS_Result_Management::TD ),
                        'view'                  => __( 'View Student', RPS_Result_Management::TD ),
                        'view_item'             => __( 'View Student', RPS_Result_Management::TD ),
                        'search_items'          => __( 'Search Students', RPS_Result_Management::TD ),
                        'not_found'             => __( 'No Students found', RPS_Result_Management::TD ),
                        'not_found_in_trash'    => __( 'No Students found in trash', RPS_Result_Management::TD ),
                        'parent'                => __( 'Parent Student', RPS_Result_Management::TD )
                ),

                'description' 		=> __( 'This is where you can add new Students.', RPS_Result_Management::TD ),
                'public' 		=> true,
                'show_ui' 		=> true,
                'show_in_menu'          => RPS_Result_Management::PLUGIN_SLUG,
                //'capability_type' 	=> 'ustc_student',
                //'map_meta_cap'	=> true,
                //'publicly_queryable' 	=> false,
                'exclude_from_search' 	=> true,
                'hierarchical' 		=> false, // Hierarchical causes memory issues - WP loads all records!
                //'rewrite' 		=> array('slug'=>'rps_result_student'),
                'supports' 		=> array('title','thumbnail'),
                'has_archive' 		=> false,
                'show_in_nav_menus' 	=> false,
                'menu_icon'             => 'dashicons dashicons-groups'
            )
        );
        //echo $hook;
    }
    
}

