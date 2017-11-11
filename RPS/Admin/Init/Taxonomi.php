<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Init_Taxonomi {

    private static $instance;
    private $TD; private $taxonomy;
    
    public static function getInstance() {

        if( self::$instance == null ) {
            self::$instance = new self;
            self::$instance->TD = RPS_Result_Management::TD;
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        add_action('init',array($this,'department_taxonomies'),0);
    }
    
    public function department_taxonomies() {
        global $wpdb;
        $query = "SELECT slug FROM `{$wpdb->rps_department}`";
        $results = $wpdb->get_results( $query, ARRAY_A );

        if( $results !==  NULL ) {
            foreach ($results as $row):
                $slug = RPS_Result_Management::PLUGIN_SLUG . '_' . $row['slug'];

                $this->taxonomy[] = $slug;

                $name = __("Semester/Section", $this->TD);
                $label = __("Semester", $this->TD);
                
                register_taxonomy( $slug, array( RPS_Result_Management::PLUGIN_SLUG, 'rps_result_department' ), array(
                        'hierarchical' 			=> false,
                        'update_count_callback' 	=> '_update_post_term_count',
                        'labels' => array(
                                'name' 			    => __( $name, $this->TD ),
                                'singular_name' 	=> __( $label, $this->TD ),
                                'search_items' 		=> __( 'Search', $this->TD ) . ' ' . $label,
                                'all_items' 		=> __( 'All', $this->TD ) . ' ' . $label,
                                'parent_item' 		=> __( 'Parent', $this->TD ) . ' ' . $label,
                                'parent_item_colon' => __( 'Parent', $this->TD ) . ' ' . $label . ':',
                                'edit_item' 		=> __( 'Edit', $this->TD ) . ' ' . $label,
                                'update_item' 		=> __( 'Update', $this->TD ) . ' ' . $label,
                                'add_new_item' 		=> __( 'Add New', $this->TD ) . ' ' . $label,
                                'new_item_name' 	=> __( 'New', $this->TD ) . ' ' . $label
                            ),
                        'show_ui' 			=> true,
                        'query_var' 		=> true,
                        //'capabilities'		=> array(
                        //'manage_terms' 		=> 'manage_product_terms',
                        //'edit_terms' 		    => 'edit_product_terms',
                        //'delete_terms' 		=> 'delete_product_terms',
                        //'assign_terms' 		=> 'assign_product_terms',
                        //),
                        'show_in_nav_menus' => false,
                        'rewrite' 			=> array( 'slug' => $slug, 'with_front' => false, 'hierarchical' => false ),
                    )
                );

                //delete cache
                add_action( 'edited_' . $slug, array($this,'save_form_fields'),10,2);
                add_action( 'created_' . $slug, array($this,'save_form_fields'),10,2);

                //remove trash link
                //add_filter( $slug . '_row_actions',     array( $this, 'remove_trash_link' ), 10, 2  );
                
                
            endforeach;

            //prevent deleting semester categories
            add_action( 'delete_term_taxonomy', array( $this, 'prevent_delete_taxonomy') );
        }
    }

    function remove_trash_link( $actions, $tag ) {

        foreach( array('delete', 'view') as $action ) {
            unset($actions[ $action ]);
        }

        return $actions;
    }

    function prevent_delete_taxonomy( $tt_id ) {
        //$tt_id = term_taxonomy_id
        global $wpdb;
        $taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id) );

        if ( in_array($taxonomy, $this->taxonomy) ) {
            $error = false;
	        //check this semester exist on student,
	        if ( !$error ) {
		        $query_args['meta_query'][] = array(
			        'key'     => '_semester_id',
			        'value'   => $tt_id,
			        'compare' => '=',
		        );
		        $meta_query = new \WP_Meta_Query();
		        $meta_query->parse_query_vars( $query_args );

		        $mq_sql    = $meta_query->get_sql(
			        'post',
			        $wpdb->posts,
			        'ID',
			        null
		        );
		        $post_type = RPS_Result_Management::STUDENT;
		        $query     = "SELECT ID FROM {$wpdb->posts} {$mq_sql['join']} WHERE post_type='{$post_type}' and (post_status='publish' or post_status='promoted' ){$mq_sql['where']} LIMIT 1";

		        $result    = $wpdb->get_row( $query, ARRAY_A );

		        if ( is_array( $result ) && ! empty( $result ) ) {
			        $error = true;
			        //$this->errors[] = __( 'You can\'t delete this department. Department is assigned to Students. Please delete assigned Students or reassign Students to another department first to delete this department', $this->TD );
		        }
	        }

            //check this semester exist on course
	        if ( !$error ) {
		        $query_args['meta_query'][] = array(
			        'key'     => '_semester_id',
			        'value'   => $tt_id,
			        'compare' => '=',
		        );
		        $meta_query                 = new \WP_Meta_Query();
		        $meta_query->parse_query_vars( $query_args );

		        $mq_sql = $meta_query->get_sql(
			        'post',
			        $wpdb->posts,
			        'ID',
			        null
		        );

		        $post_type = RPS_Result_Management::COURSE;

		        $query  = "SELECT ID FROM {$wpdb->posts} {$mq_sql['join']} WHERE post_type='{$post_type}' and post_status='publish' {$mq_sql['where']} LIMIT 1";
		        $result = $wpdb->get_row( $query, ARRAY_A );
		        if ( is_array( $result ) && ! empty( $result ) ) {
			        $error          = true;
			        //$this->errors[] = __( 'You can\'t delete this department. Department is assigned to Courses. Please delete assigned Courses or reassign Courses to another department first to delete this department', $this->TD );
		        }
	        }

            //check this semester exist on result table
	        if ( !$error ) {
		        $query  = $wpdb->prepare( "SELECT id FROM {$wpdb->rps_exam_record} WHERE semester_id = %d LIMIT 1", array( $tt_id ) );
		        $result = $wpdb->get_row( $query, ARRAY_A );
		        if ( is_array( $result ) && ! empty( $result ) ) {
			        $error = true;
			        //$this->errors[] = __( 'You can\'t delete this department. Department is assigned to Exams. Please delete assigned Exams or reassign Exans to another department first to delete this department', $this->TD );
		        }
	        }

	        if ( $error ) {
	            die();
            }
        }
    }

    public function save_form_fields( $term_id, $tt_id ) {
        //$tt_id = term_taxonomy_id

        //delete transient cache
        if( !defined( RPS_Result_Management::PLUGIN_SLUG . '_delete_transient' ) ) {
            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
        }

        RPS_Helper_Function::delete_transient();
    }
    
}

?>
