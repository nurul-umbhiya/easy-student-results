<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_Department extends RPS_Admin_Menu_MenuAbstract {

    public static function getInstance($page) {
        if(self::$instance==null) {
            self::$instance = new self;
            //self::$instance->pagehook = $slug;
            self::$instance->page = $page;
        }
        
        return self::$instance;
    }
    
    /**
     * This function will load all html related functions
     */
    public function mainDiv() { 
        if( isset($_REQUEST['page']) && $_REQUEST['page'] === $this->page ) {
            ob_start();
            if( isset( $_REQUEST['add_new'] ) ) {
                $this->add_new();
            }
            elseif ( isset( $_REQUEST['edit'] ) ) {
                $this->edit();
            }
            elseif ( isset( $_REQUEST['delete'] ) ) {
                $this->delete();
            }
            else {
                $this->right();
                $this->left();
            }
            $content = ob_get_clean();
            global $wpdb;
            //$wpdb->show_errors();
            //$wpdb->print_error();
            echo $this->get_header();
            echo $content;
            echo $this->get_footer(); 
        }
    }
    
    /**
     * This function will load all required css, js and other function on wp hook
     */
    public function onLoadPage() {
        //die('called');
	    wp_register_style( 'rps_bootstrap',     RPS_Result_Management::URL() . '/assets/bootstrap-3.3.5/css/bootstrap.css', array(), '3.3.5' );
	    wp_register_script( 'rps_bootstrap',    RPS_Result_Management::URL() . '/assets/bootstrap-3.3.5/js/bootstrap.min.js', array( 'jquery' ), '3.3.5', true);
	    $this->loadCss();
	    $this->loadJs();
    }
    
    private function loadCss() {
	    wp_enqueue_style( 'rps_bootstrap' );
	    add_action('admin_footer', array( $this, 'wpFooter' ));
    }
    
    private function loadJs() {
	    wp_enqueue_script('rps_bootstrap');
    }

	public function wpFooter() {

		?>
        <style type="text/css" rel="stylesheet">
            .wp-list-table th#sl {
                width: 50px;
            }
            a.action_button {
                margin-bottom: 5px !important;
            }
            @media (min-width: 768px) {
                .modal-dialog {
                    margin: 5% auto !important;
                }
            }

            .modal-dialog {
                margin: 5% auto !important;
            }

        </style>
        <script type="application/javascript">
            jQuery(function($){
                var department_id = 0;

                $('.delete_department').click(function() {
                    var th = $(this);
                    department_id = th.data('department_id');
                    var str = 'Are you sure you want to delete department: <strong>' + th.data('department_name') +'</strong> ?'
                    $('#deleteResultModalBody').html(str);
                    $('#deleteResultModal').modal('show');

                    return false;
                });
                $('#deleteResultModal').on('click', '#confirmDeleteResult', function() {
                    var url = $('a.department_id_'+ department_id).first().attr('href');
                    batch_id = null;
                    window.location = url;
                    //console.log(url);
                });
            });
        </script>
        <div class="rps_result">
            <div class="modal fade" tabindex="-1" role="dialog" id="deleteResultModal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Delete Department</h4>
                        </div>
                        <div class="modal-body">
                            <p id="deleteResultModalBody"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="confirmDeleteResult">Delete</button>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
        </div>
		<?php
	}
    
    private function add_new() {
        $data = array();
        if ( ! empty( $_POST ) && check_admin_referer( 'create_department_nonce', 'create_department' ) ) {
            
            // Grab the submitted data
            $name   = ( isset( $_POST['name'] ) )   ? (string) stripslashes( $_POST['name'] ) : '';
            $slug    = ( isset( $_POST['slug'] ) )    ? $this->helper->sanitize_taxonomy_name( stripslashes( (string) $_POST['slug'] ) ) : '';
            $description    = ( isset( $_POST['description'] ) )    ? (string) stripslashes( $_POST['description'] ) : '';
            $full_name = ( isset( $_POST['full_name'] ) ) ? (string) stripslashes( $_POST['full_name'] ) : '';
            $active = ( isset( $_POST['active'] ) ) ? (int) $_POST['active']  : '';
            $credit = ( isset( $_POST['credit'] ) ) ? floatval( $_POST['credit'] ) : 0.00;
            
            // Auto-generate the label or slug if only one of both was provided
            if ( ! $slug ) {
                $slug = $this->helper->sanitize_taxonomy_name( stripslashes( $name ) );
            }
            if( $slug ) {
                $slug = str_replace('-', '_', $slug);
            }

            // Forbidden attribute names
            // http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms
            $reserved_terms = RPS_Helper_Function::reserved_terms();

            // Error checking
            if( !$name ) {
                $this->error['name'] = __("Please specify a department name.", $this->TD);
            } elseif($this->getDepartmentByName($name) != NULL) {
                //check department name exist in database
                $this->error['name'] = __("Department name already exists in database.", $this->TD);
            }
            if( !$slug ) {
                $this->error['slug'] = __("Please specify a department slug.", $this->TD);
            }
            else {
                if ( strlen( $slug ) >= 28 ) {
                    $this->messages[] = sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', $this->TD ), sanitize_title( $slug ) );
                } elseif ( in_array( $slug, $reserved_terms ) ) {
                    $this->messages[] = sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', $this->TD ), sanitize_title( $slug ) );
                } else {
                    $taxonomy_exists = taxonomy_exists( RPS_Result_Management::PLUGIN_SLUG . '_'  . $slug );

                    if ( $taxonomy_exists ) {
                        $this->messages[] = sprintf( __( 'Slug "%s" is already in use. Change it, please.', $this->TD ), sanitize_title( $slug ) );
                    }
                }
            }
            if( !$full_name ) {
                //$this->error['full_name'] = __("Please specify department full name.", $this->TD);
            }
            
            $data = array (
                'name'          => $name,
                'slug'          => $slug,
                'full_name'     => $full_name,
                'description'   => $description,
                'credit'        => $credit,
                'active'        => $active,
            );

            // Show the error message if any
            if (empty($this->error ) && empty($this->messages)) {        
                $format = array("%s","%s","%s","%s","%f","%d");
                global $wpdb;

                if($wpdb->insert( $wpdb->rps_department, $data , $format)) {
                    
                    $this->messages[] = __("Department Added Successfully...", $this->TD);
                    do_action( RPS_Result_Management::PLUGIN_SLUG . '_department_added', $wpdb->insert_id, $data );

                    //delete transient cache
                    define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
                    RPS_Helper_Function::delete_transient();

                    $data = array();
                }
                else {
                    $this->messages[] = __("Something went wrong.", $this->TD);
                }
            } else {
                $data['add_new'] = "1";
            }
        }
        
        $this->right();
        $this->left($data);
        
    }
    
    private function edit() {
        global $wpdb;
	    $department_id = isset($_REQUEST['edit']) ? intval($_REQUEST['edit']) : 0;

        if ( ! empty( $_POST ) && check_admin_referer( 'edit_department_nonce_' . $department_id, 'edit_department' ) ) {
            // Grab the submitted data
            $name   = ( isset( $_POST['name'] ) )   ? (string) stripslashes( $_POST['name'] ) : '';
	        $slug    = ( isset( $_POST['slug'] ) )    ? $this->helper->sanitize_taxonomy_name( stripslashes( (string) $_POST['slug'] ) ) : '';
            $description    = ( isset( $_POST['description'] ) )    ? (string) stripslashes( $_POST['description'] ) : '';
            $full_name = ( isset( $_POST['full_name'] ) ) ? (string) stripslashes( $_POST['full_name'] ) : '';
            $active = ( isset( $_POST['active'] ) ) ? (int) $_POST['active'] : 1;
            $credit = ( isset( $_POST['credit'] ) ) ? floatval( $_POST['credit'] ) : 0.00;

	        // Error checking
	        if ( $name == '' ) {
		        $this->error['name'] = __("Please specify a department name.", $this->TD);
	        } else {
		        $query = $wpdb->prepare("SELECT count(*) FROM `{$wpdb->rps_department}` WHERE name=%s AND id != %d",
			        array($name, $department_id)
		        );
		        $count = $wpdb->get_var($query);

		        if( 0 !== intval($count) ) {
			        $this->error['name'] = __("Department name already exists in database.", $this->TD);
		        }
	        }

            if(empty($this->error)):
            
                $data = array(
                    'name' => $name,
                    'full_name'    => $full_name,
                    'description' => $description,
                    'credit' => $credit,
                    'active' => $active,
                );

                $where = array("id" => $_REQUEST['edit']);
                $format = array('%s',"%s","%s","%f","%d");
                $format_where = array("%d");

                if($wpdb->update($wpdb->rps_department,$data,$where,$format,$format_where)) {
                    $this->messages[] = __("Department Updated Successfully.",$this->TD);
                    $data = array();

                    $id = (int) $_REQUEST['edit'];
                    do_action( RPS_Result_Management::PLUGIN_SLUG . '_department_edited',$id );

                    //delete transient cache
                    define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
                    RPS_Helper_Function::delete_transient();

                    $location = esc_url_raw( add_query_arg(array('page'=> $this->page,'updated'=>'1'),  admin_url('admin.php?')) );
                    $this->helper->javascript_redirect($location);
                } else {
                    //$this->messages[] = "Data Updated Successfully";
                    //$data = array();
                    $location = esc_url_raw( add_query_arg(array('page'=>$this->page,'updated'=>'1'),  admin_url('admin.php?')) );
                    $this->helper->javascript_redirect($location);
                }
                
            else:
                $data = array(
                    'name'          => $name,
                    'slug'          => $slug,
                    'full_name'     => $full_name,
                    'description'   => $description,
                    'credit'        => $credit,
                    'active'        => $active,
                );
                $this->right();
                $this->left($data);
            endif;
            
        } else {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->rps_department}` WHERE id=%d", array($_REQUEST['edit'])),ARRAY_A);
            //print_r($data);
            if(empty($data) ) {
                $this->messages[] = sprintf( __("No records found with id %d", $this->TD), $_REQUEST['edit']);
            }
            $this->right();
            $this->left($data);
        }
    }
    
    private function delete () {
        $department_id = isset($_GET['delete']) ? intval( $_GET['delete'] ) : 0;
	    $option = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
	    if ( isset($option['user_role']) && $option['user_role'] != ''
	         && in_array($option['user_role'], array('manage_options','edit_pages','publish_posts','edit_posts','read')) ) {
		    $role = $option['user_role'];
	    } else {
		    $role = 'manage_options';
	    }

        if ( current_user_can($role) && isset($_GET['delete_department']) && wp_verify_nonce($_GET['delete_department'], 'delete_department_' . $department_id)) {
            global $wpdb;
            $error = false;
            //check department id is used in batch
            $query = $wpdb->prepare("SELECT count(*) as cnt FROM `{$wpdb->rps_batch}` WHERE department_id=%d", array($department_id));
            $row = $wpdb->get_row($query, ARRAY_A);
            if ( is_array($row) && !empty($row) && intval( $row['cnt'] ) != 0) {
                $error = true;
                $this->errors[] = __('You can\'t delete this department. Department is assigned to batch. Please delete batch first to delete this department', $this->TD);
            }

            //check department id is used with any student
            if ( !$error ) {
	            $query_args['meta_query'][] = array(
		            'key'     => '_department_id',
		            'value'   => $department_id,
		            'compare' => '=',
	            );
	            $meta_query                 = new \WP_Meta_Query();
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
		            $error          = true;
		            $this->errors[] = __( 'You can\'t delete this department. Department is assigned to Students. Please delete assigned Students or reassign Students to another department first to delete this department', $this->TD );
	            }
            }

            //check department id is used with any course
            if ( !$error ) {
	            $query_args['meta_query'][] = array(
		            'key'     => '_department_id',
		            'value'   => $department_id,
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
		            $this->errors[] = __( 'You can\'t delete this department. Department is assigned to Courses. Please delete assigned Courses or reassign Courses to another department first to delete this department', $this->TD );
	            }
            }

	        //check department id is used with any result
            if ( !$error ) {
	            $query  = $wpdb->prepare( "SELECT id FROM {$wpdb->rps_exam_record} WHERE department_id = %d LIMIT 1", array( $department_id ) );
	            $result = $wpdb->get_row( $query, ARRAY_A );
	            if ( is_array( $result ) && ! empty( $result ) ) {
		            $error          = true;
		            $this->errors[] = __( 'You can\'t delete this department. Department is assigned to Exams. Please delete assigned Exams or reassign Exams to another department first to delete this department', $this->TD );
	            }
            }


            //finally delete the department
            if ( !$error ) {
	            $row = $wpdb->get_row($wpdb->prepare("SELECT slug FROM `{$wpdb->rps_department}` WHERE id=%d", array($department_id)),ARRAY_A);
	            if ( is_array($row) && !empty($row) ) {
		            //drop taxonomy table, ie: delete all the semester associated with this department
		            $taxonomy = RPS_Result_Management::PLUGIN_SLUG . '_' . $row['slug'];
		            RPS_Helper_Function::delete_taxonomy( $taxonomy );

		            //delete department record
		            $wpdb->delete( $wpdb->rps_department, array( 'id' => $department_id ), array( '%d' ) );

		            //delete transient cache
		            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
		            RPS_Helper_Function::delete_transient();

		            $this->messages[] = __('Department/Class deleted successfully.', $this->TD);

	            }
            }

	        $this->right();
	        $this->left();
        }
    }
    
    private function left($data = array()) {
        //print_r($data);
        if(isset($_REQUEST['edit']) && $_REQUEST['edit'] != "") {
            $nonce = wp_nonce_field( 'edit_department_nonce_' . intval($_REQUEST['edit']) , 'edit_department', true, false );
            $readonly = "readonly='readonly'";
            $hidden_field = "";
        }
        else {
            $nonce = wp_nonce_field( 'create_department_nonce' , 'create_department', true, false ); 
            $readonly = "";
            $hidden_field = "<input type='hidden' name='add_new' value='1'>";
        }
        
        if(empty($data)){
            $data = array(
                'name'          => "",
                'slug'          => "",
                'full_name'     => "",
                'description'   => "",
                'credit'        => "",
                'active'        => ""
            );
        }
        
    ?>

    
        <div id="col-left">
            <div class='col-wrap'>
                <div class='form-wrap'>
                    <h3><?php _e( 'Add New Department/Class', $this->TD ) ?></h3>
                    <form method="post" action="">
                        <?php echo $nonce; echo $hidden_field; ?>

                        <div class="form-field form-required">
                            <label for="name"><?php _e( 'Department/Class Name', $this->TD) ?> (*)</label>
                            <input name="name" id="name" type="text" size="40" aria-required="true" value="<?php echo $data['name'];   ?>" />
                            <p><?php _e( 'The short name of department or class, use Uppercase letters with no space. eg CSE', $this->TD ) ?></p>
                            <?php if(isset($this->error['name'])) echo '<p style="color:red;"><strong>' . $this->error['name'] . '</strong></p>';  ?>
                        </div>
                
                        <div class="form-field">
                            <label for="slug"><?php _e('Department/Class Slug', $this->TD ) ?> (*)</label>
                            <input name="slug" id="slug" type="text" size="40" aria-required="false" value="<?php echo $data['slug'];   ?>" <?php echo $readonly; ?> />
                            <p><?php _e('The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and underscore. <strong>Leave blank for lowercase version of your department name.</strong> eg. cse', $this->TD ) ?></p>
                            <?php if(isset($this->error['slug'])) echo '<p style="color:red;"><strong>' . $this->error['slug'] . '</strong></p>';  ?>
                        </div>
                
                        <div class="form-field form-required">
                            <label for="full_name"><?php _e('Full Name', $this->TD) ?></label>
                            <input name="full_name" id="full_name" type="text" size="40" aria-required="true" value="<?php echo $data['full_name'];   ?>"/>
                            <p><?php _e('eg. Computer Science & Engineering', $this->TD) ?></p>
                            <?php if(isset($this->error['full_name'])) echo '<p style="color:red;"><strong>' . $this->error['full_name'] . '</strong></p>';  ?>
                        </div>
                        
                        <div class="form-field">
                            <label for="description"><?php _e('Description', $this->TD) ?></label>
                            <textarea name="description" id="description" rows="5" cols="40"><?php echo $data['description'];  ?></textarea>
                            <p><?php _e('Write a short description about department.', $this->TD) ?></p>
                        </div>
                        
                        <div class="form-field form-required">
                            <label for="credit"><?php _e('Total Credit', $this->TD) ?></label>
                            <input name="credit" id="credit" type="text" size="40" aria-required="true" value="<?php echo $data['credit'];   ?>"/>
                            <p><?php _e('Total credit to complete.', $this->TD) ?></p>
                            <?php if(isset($this->error['credit'])) echo '<p style="color:red;"><strong>' . $this->error['credit'] . '</strong></p>';  ?>
                        </div>
                        
                        <div class="form-field form-required">
                            <label for="active">Active</label>
                            <select name="active" id="program" class="postform">
                                <option value='1' <?php selected($data['active'], "1"); ?>><?php _e('Active', $this->TD) ?></option>
                                <option value='2' <?php selected($data['active'], "2");  ?>><?php _e('In-active', $this->TD) ?></option>
                            </select>
                            <p><?php _e('Enable this department?', $this->TD) ?></p>
                            <?php if(isset($this->error['active'])) echo '<p style="color:red;"><strong>' . $this->error['active'] . '</strong></p>';  ?>
                        </div>
                
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php if(isset($_REQUEST['edit'])) { echo __("Edit Department", $this->TD); } else { echo  __("Add New Department", $this->TD);} ?>"/>
                            <?php if(isset($_REQUEST['edit'])) { echo '<a href="' . esc_url_raw( add_query_arg(array('page'=>$this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Cancel', $this->TD). '</a>'; } ?>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    <?php
    }
    
    private function right() {
        global $wpdb;
        //$wpdb->show_errors();
        //$wpdb->print_error();
    ?>    
        <div id="col-right">
            <div class="col-wrap">
                <table class="wp-list-table widefat fixed striped tags" style="width: 100%;">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Name', $this->TD) ?></th>
                            <th scope="col"><?php _e('Semesters/Section', $this->TD) ?></th>
                            <th scope="col"><?php _e('Active', $this->TD) ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>
                    
                    <tfoot>
                        <tr>
                            <th scope="col"><?php _e('Name', $this->TD) ?></th>
                            <th scope="col"><?php _e('Semesters/Section', $this->TD) ?></th>
                            <th scope="col"><?php _e('Active', $this->TD) ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </tfoot>
                    
                    <tbody>
                        <?php 
                            $departments = $this->getAllDepartment();
                            //print_r($departments);
                            if(  empty( $departments ) || $departments === NULL ) {
                        ?>
                        <tr><td colspan="4"><?php _e('No Department/Class Found.', $this->TD) ?></td></tr>
                        <?php
                            }
                            else {
                                
                                foreach($departments as $department):
                                    $slug = RPS_Result_Management::PLUGIN_SLUG . '_' . $department['slug'];
                                    $dept_url = esc_url_raw( add_query_arg(array( 'page' => $this->page, 'edit' => $department['id']),  admin_url('admin.php?')) );
                                    $del_url = esc_url_raw( add_query_arg(array( 'page' => $this->page, 'delete' => $department['id'], 'action'=>'delete'),  admin_url('admin.php?')) );
                                    $del_url = wp_nonce_url ( $del_url, 'delete_department_' . $department['id'], 'delete_department' );

                                    $action_links = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_department_action_buttons', array(
                                        'edit' => '<span class="edit"><a href="' . $dept_url . '">' . __('Edit', $this->TD) . '</a></span>',
                                        'delete' => '<span class="delete"><a href="' . $del_url .'" data-department_id="' . $department['id'] . '" data-department_name="' . $department['name'] . '" class="delete delete_department department_id_' . $department['id'] .'">' . __('Delete', $this->TD) . '</a></span>'
                                    ), $department['id']);
                        ?>
                             <tr>
                                 <td>
                                     <?php echo "<a href='$dept_url'>" . $department['name'] . '</a>'; ?>
                                     <div class="row-actions">
                                         <?php if ( !empty($action_links) ) {
                                             echo implode(' | ', $action_links);
                                         } ?>
                                     </div>
                                 </td>
                                 
                                 <td>
                                     <?php
                                        if ( taxonomy_exists( $slug ) ) :
                                         $terms_array = array();
                                         $terms = get_terms($slug, 'orderby=name&hide_empty=0');
                                         if ($terms) :
                                             foreach ($terms as $term) :
                                                 $terms_array[] = $term->name;
                                             endforeach;
                                             echo implode(', ', $terms_array);
                                         else :
                                             echo '<span class="na">&ndash;</span>';
                                         endif;
                                        else :
                                            echo '<span class="na">&ndash;</span>';
                                        endif;
                                     ?>
                                 </td>
                                 <td><?php if($department['active']=="1"){ echo "Active"; } else { echo "Inactive"; } ?></td>
                                 <td><a href="edit-tags.php?taxonomy=<?php echo $slug; ?>" class="button button-secondary"><?php _e( 'Configure Semester', $this->TD ); ?></a></td>
                             </tr>
                                 
                                 
                        <?php        
                                    
                                    
                                    
                                endforeach;
                                
                            }
                        ?>
                        
                    </tbody>
                </table>
            </div>
        </div>
    <?php    
    }
    
    private function getAllDepartment(){
        global $wpdb;
        $query = "SELECT id,name,slug,active FROM `{$wpdb->rps_department}`";
        $results = $wpdb->get_results($query,ARRAY_A);
        return $results;
    }
    
    private function getDepartmentByName($name = "") {
        if($name !== "") {
            global $wpdb;
            $query = $wpdb->prepare("SELECT * FROM `{$wpdb->rps_department}` WHERE name=%s LIMIT 1", array($name));
            return $wpdb->get_row($query, ARRAY_A);
        }
        return NULL;
    }

    private function get_header() {
        $department_header_h2 = '<i class="dashicons dashicons-admin-page" style="line-height: 1.5em;"></i>';
        $department_header_h2 .= __('Department/Class List', $this->TD);
        $department_header_h2 = apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_department_h2', $department_header_h2);

        $str = '<div class="wrap"><h2>' . $department_header_h2 . '</h2>';
        $str .= '<br class="clear">';
        $str .= '<div id="col-container">';
        
        if(!empty($this->messages)) {
            foreach ($this->messages as $key => $msg):
	            $str .= "<div id='message' class='updated notice is-dismissible fade'>";
                $str .= "<p>$msg</p>";
                $str .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
	            $str .= "</div>";
            endforeach;

        }

	    if(!empty($this->errors)) {
		    foreach ($this->errors as $key => $msg):
			    $str .= "<div id='message' class='error notice is-dismissible fade'>";
			    $str .= "<p>$msg</p>";
			    $str .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
			    $str .= "</div>";
		    endforeach;

	    }

        if(isset($_REQUEST['updated'])){
            echo '<div id="message" class="updated fade"><p>' . __('Updated', $this->TD) . '</p></div>';
        }
        return $str;
    }
    
    private function get_footer() {
        return "</div></div>";
    }
    
}

