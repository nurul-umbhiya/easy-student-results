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
        $this->loadCss();
        $this->loadJs();
    }
    
    private function loadCss() {
        
    }
    
    private function loadJs() {
        
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
            $reserved_terms = array (
                    'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and',
                    'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage', 'day',
                    'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name',
                    'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm',
                    'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type',
                    'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence',
                    'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id',
                    'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year',
            );

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
                $this->error['full_name'] = __("Please specify department full name.", $this->TD);
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
        if ( ! empty( $_POST ) && check_admin_referer( 'edit_department_nonce', 'edit_department' ) ) {
            // Grab the submitted data
            $name   = ( isset( $_POST['name'] ) )   ? (string) stripslashes( $_POST['name'] ) : '';
            $slug    = ( isset( $_POST['slug'] ) )    ? $this->helper->sanitize_taxonomy_name( stripslashes( (string) $_POST['slug'] ) ) : '';
            $description    = ( isset( $_POST['description'] ) )    ? (string) stripslashes( $_POST['description'] ) : '';
            $full_name = ( isset( $_POST['full_name'] ) ) ? (string) stripslashes( $_POST['full_name'] ) : '';
            $active = ( isset( $_POST['active'] ) ) ? (int) $_POST['active'] : 1;
            $credit = ( isset( $_POST['credit'] ) ) ? floatval( $_POST['credit'] ) : 0.00;
            

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
        if ( isset($_GET['delete_department']) && wp_verify_nonce($_GET['delete_department'], 'delete_department_' . $department_id)) {
            global $wpdb;
            //check department id is used in batch
            $query = $wpdb->prepare("SELECT count(*) as cnt FROM `{$wpdb->rps_batch}` WHERE department_id=%d", array($department_id));
            $row = $wpdb->get_row($query, ARRAY_A);
            if ( is_array($row) && !empty($row) && intval( $row['cnt'] ) != 0) {
                wp_die(__('You can\'t delete this department. Department is assigned to batch. Please delete batch first to delete this department'));
            }
            //check department id is used with any student

            //check department id is used with any course

            //check department id is used with any result

            //delete all the semester associated with this department

            //finally delete the department

        }
    }
    
    private function left($data = array()) {
        //print_r($data);
        if(isset($_REQUEST['edit']) && $_REQUEST['edit'] != "") {
            $nonce = wp_nonce_field( 'edit_department_nonce' , 'edit_department', true, false ); 
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
                            <label for="name"><?php _e( 'Department/Class Name', $this->TD) ?></label>
                            <input name="name" id="name" type="text" size="40" aria-required="true" value="<?php echo $data['name'];   ?>" />
                            <p><?php _e( 'The short name of department or class, use Uppercase letters with no space. eg CSE', $this->TD ) ?></p>
                            <?php if(isset($this->error['name'])) echo '<p style="color:red;"><strong>' . $this->error['name'] . '</strong></p>';  ?>
                        </div>
                
                        <div class="form-field">
                            <label for="slug"><?php _e('Department/Class Slug', $this->TD ) ?></label>
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
                                <option value='0' <?php selected($data['active'], "2");  ?>><?php _e('In-active', $this->TD) ?></option>
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
                                    $del_url = esc_url_raw( add_query_arg(array( 'page' => $this->page, 'delete' => $department['id']),  admin_url('admin.php?')) );
                                    $del_url = wp_nonce_url ( $del_url, 'delete_department_' . $department['id'], 'delete_department' );
                        ?>
                             <tr>
                                 <td>
                                     <?php echo "<a href='$dept_url'>" . $department['name'] . '</a>'; ?>
                                     <div class="row-actions">
                                         <span class="edit"><a href="<?php echo $dept_url; ?>"><?php _e('Edit', $this->TD) ?></a></span>
                                         <span class="delete"><a class="delete" href="<?php echo $del_url; ?>"><?php _e('Delete', $this->TD) ?></a></span>
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
        $str = '<div class="wrap"><h2><i class="dashicons dashicons-admin-page" style="line-height: 1.5em;"></i> ';
        $str .= __('Department/Class List', $this->TD);
        $str .= '</h2><br class="clear">';
        $str .= '<div id="col-container">';
        
        if(!empty($this->messages)){
            $str .= "<div id='message' class='updated fade'>";
            foreach ($this->messages as $key => $msg):
                $str .= "<p>$msg</p>";
            endforeach;
            $str .= "</div>";
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

