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
                
                $count = $wpdb->get_var($query);

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
        $option = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
        if ( isset($option['user_role']) && $option['user_role'] != ''
            && in_array($option['user_role'], array('manage_options','edit_pages','publish_posts','edit_posts','read')) ) {
            $role = $option['user_role'];
        } else {
            $role = 'manage_options';
        }

        $batch_id = isset($_GET['delete']) ? intval( $_GET['delete'] ) : 0;
        global $wpdb;

		if ( current_user_can($role) && isset($_GET['delete_batch']) && wp_verify_nonce($_GET['delete_batch'], 'delete_batch_' . $batch_id)) {
		    $error = false;

			//check batch id is used with any student
            if ( !$error ) {
	            $query_args['meta_query'][] = array(
		            'key'     => '_batch_id',
		            'value'   => $batch_id,
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
		            $this->errors[] = __( 'You can\'t delete this Batch. Batch is assigned to Students. Please delete assigned Students or reassign Students to another batch first to delete this batch.', $this->TD );
	            }
            }

	        //check department id is used with any result
            if ( !$error ) {
	            $query  = $wpdb->prepare( "SELECT id FROM {$wpdb->rps_exam_record} WHERE batch_id = %d LIMIT 1", array( $batch_id ) );
	            $result = $wpdb->get_row( $query, ARRAY_A );
	            if ( is_array( $result ) && ! empty( $result ) ) {
		            $error          = true;
		            $this->errors[] = __( 'You can\'t delete this batch. Batch is assigned to Exams. Please delete assigned Exams or reassign Exams to another batch first to delete this batch', $this->TD );
	            }
            }


            //finally delete the batch
            if ( !$error ) {

	            //delete department record
		        $wpdb->delete( $wpdb->rps_batch, array( 'id' => $batch_id ), array( '%d' ) );

		        //delete transient cache
		        define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
		        RPS_Helper_Function::delete_transient();

                $this->messages[] = __('Batch deleted successfully.', $this->TD);
            }

            $this->listTable();

		}
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
                            <option value='2' <?php selected($data['active'], "2");  ?>><?php _e('In-active', $this->TD) ?></option>
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
    
    private function listTable() {
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
        if(isset($_REQUEST['add_new']) && $_REQUEST['add_new'] != "") {
            $title = __("Add New Batch", $this->TD);
            
        }
        elseif(isset ($_REQUEST['edit']) && $_REQUEST['edit'] != "") {
            $title = __("Edit Batch", $this->TD);
        }
        else {
            $link = ' <a href="' . esc_url_raw( add_query_arg(array('add_new'=>'1', 'page'=> $this->page),  admin_url('admin.php?')) ) .'" class="add-new-h2">' . __('Add New', $this->TD) . '</a>';
            $title = __("Batch List", $this->TD) . apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_batch_h2', $link) ;
            
        }
    ?>
        <div class="wrap">
                <div id="icon-edit" class="icon32 icon32-posts-post">&nbsp;</div>
                <h2><?php echo $title; ?></h2>
                <br class="clear">
    <?php
        
        $str = '';
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

	    if ( $str != '' ) {
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
    
    private function get_footer() {
        return '</div>';

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
        		var batch_id = 0;

				$('.delete_batch').click(function() {
					var th = $(this);
					batch_id = th.data('batch_id');
					var str = 'Are you sure you want to delete batch: <strong>' + th.data('batch_name') +'</strong> ?'
					$('#deleteResultModalBody').html(str);
					$('#deleteResultModal').modal('show');

					return false;
				});
				$('#deleteResultModal').on('click', '#confirmDeleteResult', function() {
					var url = $('a.batch_id_'+ batch_id).first().attr('href');
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
					<h4 class="modal-title">Delete Batch</h4>
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
}

