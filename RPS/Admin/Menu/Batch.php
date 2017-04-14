<?php

if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_Batch extends RPS_Admin_Menu_MenuAbstract {

    public static function getInstance( $page ) {
        if( self::$instance == null ) {
            self::$instance = new self;
            //self::$instance->pagehook = $slug;
            self::$instance->page = $page;
        }
        
        return self::$instance;
    }
    
    /**
     * This function will hold all html related functions
     */
    public function mainDiv() {
        if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this->page ) {
            ob_start();
            if( isset( $_REQUEST['add_new'] ) ){
                $this->add_new();
            }
            elseif ( isset( $_REQUEST['edit'] ) ) {
                $this->edit();
            }
            elseif ( isset( $_REQUEST['delete'] ) ) {
                $this->delete();
            }
            else {
                $this->listTable();

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
    
    private function add_new() {
        $data = array();
        global $wpdb;
        if ( ! empty( $_POST ) && check_admin_referer( 'create_batch_nonce', 'create_batch' ) ) {
            
            // Grab the submitted data
            $data['name']           = ( isset( $_POST['name'] ) )               ? (string) stripslashes( $_POST['name'] ) : '';
            $data['department_id']  = ( isset( $_POST['department_id'] ) )      ? (int) $_POST['department_id'] : '';
            $data['session_month']  = ( isset( $_POST['session_month'] ) )      ? stripslashes($_POST['session_month'])  : '';
            $data['session_year']   = ( isset( $_POST['session_year'] ) )       ? stripslashes($_POST['session_year'])  : '';
            $data['active']         = ( isset( $_POST['active'] ) )             ? (int) $_POST['active'] : 1;
            
            //check for validation 
            if( !$data['name'] ) {
                $this->error['name'] = __('Please specify a batch name.', $this->TD);
            } else {
                //check for unique name
            }
            
            if( !$data['department_id'] ) {
                $this->error['department_id'] = __('Please select a department from above list.', $this->TD);
            }

            /*
            if( !$data['current_sem'] ) {
                $this->error['current_sem'] = __('Please select a semester from avobe list.', $this->TD);
            }
            */
            
            if( !$data['session_month'] ) {
                $this->error['session_month'] = __('Please select a month for session from avobe list.', $this->TD);
            }
            
            if( !$data['session_year'] ) {
                $this->error['session_year'] = __('Please select a year for session from avobe list.', $this->TD);
            }
            
            //check unique batch name with department id
            if( $data['name'] != '' && RPS_Helper_Function::is_numeric($data['department_id']) ) {
                $query = $wpdb->prepare("SELECT count(*) FROM `{$wpdb->rps_batch}` WHERE name=%s AND department_id=%d",
                    array($data['name'], $data['department_id'])
                );
                
                $count = $wpdb->get_var($query,ARRAY_A);

                if( 0 !== intval($count) ) {
                    $this->error['name'] = __('Batch name already exists in database. Please enter another name.', $this->TD);
                }
            }
            
            if( empty($this->error) && empty($this->messages) ) {
                $data['added'] = time();
                $format = array('%s', '%d', '%s', '%s', '%d', '%d');
                
                if( $wpdb->insert( $wpdb->rps_batch, $data, $format) ) {
                    $id = $wpdb->insert_id;
                    do_action( RPS_Result_Management::PLUGIN_SLUG . '_batch_added', $id, $data);

                    //delete transient cache
                    define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
                    RPS_Helper_Function::delete_transient();

                    $location = esc_url_raw( add_query_arg(array('page'=>  $this->page,'updated'=>'1'),  admin_url('admin.php?')) );
                    $this->helper->javascript_redirect($location);
                    
                } else {
                    $this->messages[] = __('Somethis went wrong!', $this->TD);
                    $wpdb->show_errors();
                    $wpdb->print_error();
                }
            }
        }
        
        $this->addEdit($data);
    }
    
    private function edit() {
        global $wpdb;
        if ( ! empty( $_POST ) && check_admin_referer( 'edit_batch_nonce', 'edit_batch' ) ) {
            // Grab the submitted data
            $data['name']           = ( isset( $_POST['name'] ) )               ? (string) stripslashes( $_POST['name'] ) : '';
            $department_id          = ( isset( $_POST['department_id'] ) )      ? (int) $_POST['department_id'] : '';
            $data['session_month']  = ( isset( $_POST['session_month'] ) )   ? stripslashes($_POST['session_month'])  : '';
            $data['session_year']   = ( isset( $_POST['session_year'] ) )    ? stripslashes($_POST['session_year'])  : '';
            $data['active']         = ( isset( $_POST['active'] ) )             ? (int) $_POST['active'] : 1;

            //check for validation
            if( !$data['name'] ) {
                $this->error['name'] = __('Please specify a batch name.', $this->TD);
            } else {
                //check for unique name
            }

            if( !$data['session_month'] ) {
                $this->error['session_month'] = __('Please select a month for session from avobe list.', $this->TD);
            }

            if( !$data['session_year'] ) {
                $this->error['session_year'] = __('Please select a year for session from avobe list.', $this->TD);
            }
            
            //check unique batch name with department id
            if( $data['name'] != '' && RPS_Helper_Function::is_numeric( $department_id ) ) {

                $query = $wpdb->prepare("SELECT count(*) FROM `{$wpdb->rps_batch}` WHERE name=%s AND department_id = %d AND `id` != %d ",
                    array($data['name'], $department_id, $_REQUEST['edit'])
                );
                $count = $wpdb->get_var($query);

                if( 0 !== intval($count) ) {
                    $this->error['name'] = __('Batch name already exists in database. Please enter another name.', $this->TD);
                }
            }
            
            if( empty( $this->error ) && empty( $this->messages ) ) {
                $format = array('%s', '%s', '%s', '%d');
                $where  = array('id' => (int)$_REQUEST['edit']);
                $format_where = array('%d');

                $wpdb->update( $wpdb->rps_batch, $data, $where, $format, $format_where);

                do_action( RPS_Result_Management::PLUGIN_SLUG . '_batch_edited', (int) $_REQUEST['edit'], $data);

                //delete transient cache
                define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
                RPS_Helper_Function::delete_transient();

                $location = esc_url_raw( add_query_arg(array('page'=>  $this->page,'updated'=>'2'),  admin_url('admin.php?')) );
                RPS_Helper_Function::javascript_redirect($location);

            } else {
                $this->addEdit($data, $department_id);
            }
        }
        else {
            $data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->rps_batch}` WHERE id=%d", array( $_REQUEST['edit'] ) ),ARRAY_A);
            //print_r($data);
            if(empty($data) ) {
                $this->messages[] = sprintf( __('No records found with id %s', $this->TD), $_REQUEST['edit']);
            }
            $this->addEdit($data, $data['department_id']);
            
        }
    }
    
    private function delete() {
        
    }
    
    private function addEdit($data = array(), $department_id = null) {
        //print_r($data);
        if(isset($_REQUEST['edit']) && $_REQUEST['edit'] != "") {
            $nonce = wp_nonce_field( 'edit_batch_nonce' , 'edit_batch', true, false ); 
            $readonly = "readonly='readonly'";
            $disabled = "disabled='disabled'";
            $hidden_field = "<input type='hidden' name='department_id' value='$department_id'>";
        }
        else {
            $nonce = wp_nonce_field( 'create_batch_nonce' , 'create_batch', true, false ); 
            $readonly = "";
            $disabled = "";
            $hidden_field = "<input type='hidden' name='add_new' value='1'>";
        }
        
        if(empty($data)) {
            $data = array(
                'name' => '',
                'slug' => '',
                'department_id' => '',
                //'current_sem' => '',
                'session_month' => '',
                'session_year' => '',
                'active' => ''
            );
        }
        
        
        $departments = $this->dbs->getDepartmentList();
        if( !is_wp_error($departments) ) {
            $dept_ids = array_keys($departments);

            /*
            $semesters = array();
            if(!empty($dept_ids)):
                foreach ($dept_ids as $id):
                    $sem = $this->dbs->getSemesterList($id);
                    $semesters[$id] = is_wp_error($sem) ? array() : $sem;
                endforeach;
            endif;
            */
        }
    ?>

    
        <form method="post" action="">
            <?php echo $nonce; echo $hidden_field; ?>
            <table class="form-table">
                <tbody>
                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="name"><?php _e('Batch/Year', $this->TD) ?></label>
                    </th>
                    <td>
                        <input name="name" id="name" type="text" size="40" aria-required="true" value="<?php echo $data['name'];   ?>" <?php //echo $readonly; ?> />
                        <p><?php _e('The short name of batch/section, eg. 1st, Section A etc.', $this->TD) ?></p>
                        <?php if(isset($this->error['name'])) echo '<p style="color:red;"><strong>' . $this->error['name'] . '</strong></p>';  ?>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="department_id"><?php _e('Department/Class', $this->TD) ?></label>
                    </th>
                    <td>
                        <?php if( !is_wp_error($departments) && !empty($dept_ids) ): ?>
                        <select name="department_id" id="department_id" <?php echo $disabled;  ?>>
                            <?php
                            foreach ($departments as $id => $name):
                                //echo $id;
                                echo "<option value='{$id}' ".selected($data['department_id'], $id, false)." >{$name}</option>";
                            endforeach;
                            ?>
                        </select>
                        
                        <?php else: 
                            $error_string = $departments->get_error_message();
                            echo esc_attr($error_string);
                        endif; ?>
                        
                        <p><?php _e('Select Department/Class From List.', $this->TD) ?></p>
                        <?php if(isset($this->error['department_id'])) echo '<p style="color:red;"><strong>' . $this->error['department_id'] . '</strong></p>';  ?>
                    </td>
                </tr>


                <tr class="form-field">
                    <th scope="row" valign="top">
                        <label for="session"><?php _e('Session', $this->TD) ?></label>
                    </th>
                    <td>
                        <select name="session_month" id="session_month">
                            <?php
                                $months = array(
                                    '01' => 'January',
                                    '02' => 'February',
                                    '03' => 'March',
                                    '04' => 'April',
                                    '05' => 'May',
                                    '06' => 'June',
                                    '07' => 'July',
                                    '08' => 'August',
                                    '09' => 'September',
                                    '10' => 'October',
                                    '11' => 'November',
                                    '12' => 'December'
                                );

                            foreach ($months as $key => $month ) {
                                $selected = $data['session_month'] == $key ? 'selected="selected"': '';
                                printf('<option value="%s" %s>%s</option>', $key, $selected, __($month, $this->TD));
                            }
                            ?>
                        </select>

                        <select name="session_year" id="session_year">
                            <?php 
                                $current = date('Y');
                                $past = $current - 20;
                                if($data['session_year'] == '') {
                                    $year_selected = $current;
                                } else {
                                    $year_selected = $data['session_year'];
                                }
                                for (; $past<=$current; $past++) {
                                    echo "<option value='{$past}' ".selected($year_selected, $past, false).">{$past}</option>";
                                }
                            ?>
                        </select>
                        <p><?php _e('Enter Session. eg. Month - Year', $this->TD) ?></p>
                        <?php if(isset($this->error['session_year'])) echo '<p style="color:red;"><strong>' . $this->error['session_year'] . '</strong></p>';  ?>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="active"><?php _e('Active', $this->TD) ?></label>
                    </th>
                    <td>
                        <select name="active" id="program" class="postform">
                            <option value='1' <?php selected($data['active'], "1"); ?>><?php _e('Active', $this->TD) ?></option>
                            <option value='0' <?php selected($data['active'], "2");  ?>><?php _e('In-active', $this->TD) ?></option>
                        </select>
                        <p><?php _e('Enable this Batch ?', $this->TD) ?></p>
                        <?php if(isset($this->error['active'])) echo '<p style="color:red;"><strong>' . $this->error['active'] . '</strong></p>';  ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php if(isset($_REQUEST['edit'])) { echo "Edit Batch"; } else { echo  "Add New Batch";} ?>"/>
                <?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) .'</a>';  ?>
            </p>
                
        </form>
        <!--
        <script type="text/javascript">        
            jQuery(function($){
                var semesters = <?php echo json_encode($semesters) ?>;
                $('#department_id').on('change',function(){
                    var id = $(this).val();
                    var i = semesters[id];
                    console.log(i);
                    $('#current_sem option').remove();

                    // Add option to the select box from list
                    if(i !== null){
                        $.each(i, function(key, value) {   
                             $('#current_sem')
                                 .append($("<option></option>")
                                 .attr("value",key)
                                 .text(value)); 
                        });
                    }
                });
            });
        </script>
        -->
                
    <?php

    }
    
    private function listTable(){
        $batch_table = new RPS_Admin_Menu_BatchTable();
        //Fetch, prepare, sort, and filter our data...
        $batch_table->prepare_items();
        ?>    
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $batch_table->display() ?>
        </form>
        <?php    
    }
    
    
    
    private function get_header() {
        if(isset($_REQUEST['add_new']) && $_REQUEST['add_new'] != ""){
            $title = __("Add New Batch", $this->TD);
            
        }
        elseif(isset ($_REQUEST['edit']) && $_REQUEST['edit'] != ""){
            $title = __("Edit Batch", $this->TD);
        }
        else {
            $link = ' <a href="' . esc_url_raw( add_query_arg(array('add_new'=>'1', 'page'=> $this->page),  admin_url('admin.php?')) ) .'" class="add-new-h2">' . __('Add New', $this->TD) . '</a>';
            $title = __("Batch List", $this->TD) . $link ;
            
        }
    ?>
        <div class="wrap">
                <div id="icon-edit" class="icon32 icon32-posts-post">&nbsp;</div>
                <h2><?php echo $title; ?></h2>
                <br class="clear">
    <?php
        
        $str = '';
        if(!empty($this->messages)) {
            $str .= '<div id="message" class="updated fade">';
            foreach ($this->messages as $key => $msg):
                $str .= "$msg <br>";
            endforeach;
            $str .= "</div>";
            echo $str;
        }
        if(isset($_REQUEST['updated'])) {
            if($_REQUEST['updated']==1) {
                echo '<div id="message" class="updated fade">' . __('Batch Added Successfully.', $this->TD) . '</div>';
            }
            elseif($_REQUEST['updated']==2) {
                echo '<div id="message" class="updated fade"><p>' . __('Batch Edited Successfully.', $this->TD) . '</p></div>';
            }
        }
    }
    
    private function get_footer(){
        return '</div>';

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
        add_action('admin_footer', array( $this, 'wpFooter' ));
    }
    
    private function loadJs() {
        
    }

    public function wpFooter() {

        ?>
        <style type="text/css">
            .wp-list-table th#sl {
                width: 50px;
            }
        </style>
        <?php
    }
}

