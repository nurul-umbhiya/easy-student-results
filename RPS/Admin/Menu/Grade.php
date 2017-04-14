<?php

if( !defined( 'WPINC' ) ) {
    die();
}

class RPS_Admin_Menu_Grade extends  RPS_Admin_Menu_MenuAbstract {

    public static function getInstance($page) {
        if( self::$instance == null ) {
            self::$instance = new self;
            //self::$instance->pagehook = $slug;
            self::$instance->page = $page;
        }

        return self::$instance;
    }

    private function deleteCache() {
        $transient = 'rps_grade_data';
        delete_transient($transient);
    }
    
    /**
     * This function will hold all html related functions
     */
    public function mainDiv() {
        if( isset($_REQUEST['page']) && $_REQUEST['page'] === $this->page ) {
            ob_start();
            if(isset($_REQUEST['add_new']) ){
                $this->addNew();
            }
            elseif (isset ($_REQUEST['edit'])) {
                $this->edit();
            }
            elseif (isset($_REQUEST['delete'])){
                $this->delete();
            }
            else {
                $this->showGradeList();

            }
            $content = ob_get_clean();
            global $wpdb;
            //$wpdb->show_errors();
            //$wpdb->print_error();
            echo $this->getHeader();
            echo $content;
            echo $this->getFooter();
        }
    }

    private function addNew() {
        if ( ! empty( $_POST ) && check_admin_referer( 'create_grade_nonce_' . get_current_user_id() , 'create_grade' ) ) {
            global $wpdb;
            $data['marks']          = ( isset( $_POST['marks'] ) )          ? (string) stripslashes( $_POST['marks'] ) : '';
            $data['grade']          = ( isset( $_POST['grade'] ) )          ? (string) stripslashes( $_POST['grade'] ) : '';
            $data['grade_point']    = ( isset( $_POST['grade_point'] ) )    ? floatval( $_POST['grade_point'] )  : '';
            $data['grade_classification']     = ( isset( $_POST['grade_classification'] ) )     ? (string) stripslashes( $_POST['grade_classification'] ) : '';
            $data['active']         = ( isset( $_POST['active'] ) )         ? (int) $_POST['active']  : 1;


            //check for empty grade
            if( $data['grade'] === "" ) {
                $this->error['grade'] = __("Please Enter Grade.", $this->TD);
            } else {
                //check for duplicate grade
                $sql = $wpdb->prepare("SELECT * FROM {$wpdb->rps_grade} WHERE grade=%s", array($data['grade']));
                $row = $wpdb->get_row($sql,ARRAY_A);

                if( $row !== NULL ) {
                    $this->error['grade'] = __("Grade already exist. Please enter another grade.", $this->TD);
                }
            }

            //check for duplicate grade point
            if ( $data['grade_point'] != "" ) {
                $sql = $wpdb->prepare("SELECT * FROM {$wpdb->rps_grade} WHERE grade_point=%f", array($data['grade_point']));
                $row = $wpdb->get_row($sql,ARRAY_A);
                if( $row !== NULL ) {
                    $this->error['grade_point'] = __("Grade Point already exist. Please enter another Grade Point.", $this->TD);
                }
                $wpdb->show_errors();
                $wpdb->print_error();
                //check for numeric grade point
                if ( !is_numeric( $data['grade_point'] ) && $data['grade_point'] < 0 ) {
                    $this->error['grade_point'] = __( 'Grade Point  should be a numeric value and should be greate than or equal to zero', $this->TD );
                }
            }

            if ( empty( $this->messages ) && empty( $this->error ) ) {
                $data['added'] = time();
                $format = array('%s', '%s', '%f', '%s', '%d', '%d');
                if ( $wpdb->insert( $wpdb->rps_grade, $data, $format ) ) {
                    //delete cache
                    $this->deleteCache();

                    $this->messages[] = __("Grade Inserted Successfully.", $this->TD);
                    $this->showGradeList();
                }
                else {
                    $wpdb->show_errors();
                    $wpdb->print_error();
                    $this->formTable($data);
                }
            }
            else {
                $this->formTable($data);
            }
        }
        else {
            $this->formTable();
        }
    }

    private function edit() {
        global $wpdb;
        $id = isset($_GET['edit']) ? (int) stripslashes($_GET['edit']) : '';

        if ( ! empty( $_POST ) && check_admin_referer( 'edit_grade_nonce_' . $id, 'edit_grade' ) ) {

            $data['marks']          = ( isset( $_POST['marks'] ) )          ? (string) stripslashes( $_POST['marks'] ) : '';
            $data['grade']          = ( isset( $_POST['grade'] ) )          ? (string) stripslashes( $_POST['grade'] ) : '';
            $data['grade_point']    = ( isset( $_POST['grade_point'] ) )    ? stripslashes( $_POST['grade_point'] )  : '';
            $data['grade_classification']     = ( isset( $_POST['grade_classification'] ) )     ? (string) stripslashes( $_POST['grade_classification'] ) : '';
            $data['active']         = ( isset( $_POST['active'] ) )         ? (int) $_POST['active']  : 1;

            //check for empty grade
            if( $data['grade'] === "" ) {
                $this->error['grade'] = __("Please Enter Grade.", $this->TD);
            } else {
                //check for already exist slug
                $sql = $wpdb->prepare("SELECT * FROM {$wpdb->rps_grade} WHERE grade=%s AND id != %d", array( $data['grade'], $id ) );
                $row = $wpdb->get_row($sql,ARRAY_A);
                if( $row !== NULL ) {
                    $this->error['grade'] = __("Grade already exist. Please enter another grade.", $this->TD);
                }
            }

            //check for duplicate grade point
            if ( $data['grade_point'] != "" ) {

                //check for numeric grade point
                if ( !is_numeric( $data['grade_point'] ) || floatval($data['grade_point']) < 0.00 ) {
                    $this->error['grade_point'] = __( 'Grade Point  should be a numeric value and should be greate than or equal to zero', $this->TD );
                } else {
                    $sql = $wpdb->prepare("SELECT * FROM {$wpdb->rps_grade} WHERE grade_point=%f AND id != %d", array( $data['grade_point'], $id ) );
                    $row = $wpdb->get_row($sql,ARRAY_A);
                    if( $row !== NULL ) {
                        $this->error['grade_point'] = __("Grade Point already exist. Please enter another Grade Point.", $this->TD);
                    }
                }

            }

            if ( empty($this->messages) && empty($this->error) ) {
                $format = array('%s', '%s', '%f', '%s', '%d');
                $where = array( 'id' => $id );
                $format_where = array( '%d' );

                $wpdb->update( $wpdb->rps_grade, $data, $where, $format, $format_where );

                //delete cache
                $this->deleteCache();

                $url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'updated' => '1' ), 'admin.php?' ) );
                RPS_Helper_Function::javascript_redirect($url);
            } else {
                $this->formTable($data);
            }
        } else {
            $sql = "SELECT * FROM {$wpdb->rps_grade} WHERE id=$id";
            $data = $wpdb->get_row($sql,ARRAY_A);
            $this->formTable($data);
        }
    }

    private function formTable($data = array() ) {
        if ( isset($_REQUEST['edit']) && $_REQUEST['edit'] != "" ) {
            $nonce = wp_nonce_field( 'edit_grade_nonce_' . $_REQUEST['edit'] , 'edit_grade', true, false );
            $readonly = "readonly='readonly'";
            $disabled = "disabled='disabled'";
        }
        else {
            $nonce = wp_nonce_field( 'create_grade_nonce_' . get_current_user_id() , 'create_grade', true, false );
            $readonly = "";
            $disabled = "";
        }
        if ( empty( $data ) ) {
            $data['grade'] = '';
            $data['marks'] = '';
            $data['grade_point'] = '';
            $data['grade_classification'] = '';
            $data['active'] = '1';
        }
        ?>
        <form method="post" action="">
            <?php echo $nonce; ?>

            <table class="form-table">
                <tbody>

                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="grade"><?php _e('Alphabetic Grade', $this->TD); ?> * </label>
                    </th>
                    <td>
                        <input name="grade" id="grade" type="text" size="40" aria-required="true" value="<?php echo $data['grade'];   ?>" />
                        <p><?php _e('Grade should be unique. eg A+, A, B+, B etc', $this->TD); ?></p>
                        <?php if(isset($this->error['grade'])) echo '<p style="color:red;"><strong>' . $this->error['grade'] . '</strong></p>';  ?>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="marks"><?php _e('Marks', $this->TD) ?></label>
                    </th>
                    <td>
                        <input type="text" name="marks" id="marks" value="<?php echo $data['marks']; ?>" class="postform" aria-required="true" />
                        <p><?php _e('Enter marks range for this grade. eg: 80 - 100, 75 - 79 etc', $this->TD); ?></p>
                        <?php if(isset($this->error['marks'])) echo '<p style="color:red;"><strong>' . $this->error['marks'] . '</strong></p>';  ?>
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="grade_point"><?php _e('Grade Point', $this->TD); ?></label>
                    </th>
                    <td>
                        <input type="text" name="grade_point" id="grade_point" value="<?php echo $data['grade_point']; ?>" aria-required="true" class="postform" />
                        <p><?php _e('Enter grade poing. eg: 4.0, 3.7 etc. Grade point must be a numeric value.', $this->TD); ?></p>
                        <?php if(isset($this->error['grade_point'])) echo '<p style="color:red;"><strong>' . $this->error['grade_point'] . '</strong></p>';  ?>
                    </td>
                </tr>

                <tr class="form-field form-required">
                    <th scope="row" valign="top">
                        <label for="grade_classification"><?php _e('Grade Classification', $this->TD); ?></label>
                    </th>
                    <td>
                        <input name="grade_classification" id="grade_classification" value="<?php echo $data['grade_classification']; ?>" class="postform" />
                        <p><?php _e('Enter grade classification here if exist.', $this->TD); ?></p>
                        <?php if(isset($this->error['grade_classification'])) echo '<p style="color:red;"><strong>' . $this->error['grade_classification'] . '</strong></p>';  ?>
                    </td>
                </tr>
                <tr class="form-field">
                    <th scope="row" valign="top">
                        <label for="active"><?php _e('Active', $this->TD); ?></label>
                    </th>
                    <td>
                        <select name="active" id="active" class="postform">
                            <option value='1' <?php selected($data['active'], "1"); ?>><?php _e('Active', $this->TD); ?></option>
                            <option value='2' <?php selected($data['active'], "2");  ?>><?php _e('Inactive', $this->TD); ?></option>
                        </select>
                        <p><?php _e('If status is <strong>Active</strong>, you will view this grade to add result page. If status is <strong>Inactive</strong> this grade will be hidden.', $this->TD);  ?></p>
                        <?php if(isset($this->error['active'])) echo '<p style="color:red;"><strong>' . $this->error['active'] . '</strong></p>';  ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php if(isset($_REQUEST['edit'])) { echo __("Update Grade", $this->TD); } else { echo  __("Add New Grade", $this->TD); } ?>"/>
                <?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) . '</a>';  ?>
            </p>

        </form>
        <?php
    }

    private function delete() {
        global $wpdb;
        $id = isset($_GET['delete']) ? (int) stripslashes($_GET['delete']) : '';

        if ( ! RPS_Helper_Function::is_numeric($id) && $id <= 0 ) {
            $this->messages[] = __("Invalid Entry", $this->TD );
            return;
        }

        if ( ! empty( $_POST ) && check_admin_referer( 'delete_grade_nonce_' . $id, 'delete_grade' ) ) {
            $where = array( 'id' => $id );
            $where_format = array('%d');

            if ( $wpdb->delete( $wpdb->rps_grade, $where, $where_format ) ) {
                //delete success
                $url = esc_url_raw( add_query_arg( array( 'page' => $this->page, 'deleted' => '1' ), 'admin.php?' ) );

                //delete cache
                $this->deleteCache();

                RPS_Helper_Function::javascript_redirect($url);
            }

        } else {
            //check this grade id already exist in database or not ?
            $query = $wpdb->prepare( "SELECT * FROM `{$wpdb->rps_marks}` WHERE `grade_id` = %d LIMIT 1", array($id) );
            $row = $wpdb->get_row( $query, ARRAY_A );

            if ( null !== $row  ) {
                $msg = __( 'Grade ID Exist on Result Table. You can\'t delete this record until you delete record on result table.', $this->TD );
                $go_back = esc_url_raw( add_query_arg( array( 'page' => $this->page ), 'admin.php?' ) );
                echo '<div id="message" class="updated fade below-h2"><p>' . $msg . '<br></p></div>';
                echo '<a class="button button-primary" href="' . $go_back . '">' . __( 'Back', $this->TD ) . '</a>';

                ?>

                <?php

            } else {
                //show delete form
                $nonce = wp_nonce_field( 'delete_grade_nonce_' . $id , 'delete_grade', true, false );
                $text = __( 'Are you sure you want to delete this grade?', $this->TD );
                ?>
                <form method="post">
                    <?php echo $nonce; ?>
                    <p><?php  _e( 'Are you sure you want to delete this grade?', $this->TD ); ?></p>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Delete', $this->TD  ); ?>"/>
                        <?php echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button button-secondary">' . __('Back', $this->TD) . '</a>';  ?>
                    </p>
                </form>
                <?php
            }
        }

    }

    private function showGradeList() {
        $table = new RPS_Admin_Menu_GradeTable();
        //Fetch, prepare, sort, and filter our data...
        $table->prepare_items();
        ?>
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $table->display() ?>
        </form>
        <?php
    }

    private function getHeader() {
        if ( isset( $_REQUEST['add_new'] ) && $_REQUEST['add_new'] != "" ) {
            $title = __("Add New Grade", $this->TD);

        } elseif ( isset( $_REQUEST['edit'] ) && $_REQUEST['edit'] != "" ) {
            $title = __("Edit Grade", $this->TD);
        } elseif ( isset( $_REQUEST['delete'] ) && $_REQUEST['delete'] != '' ) {
            $title = __( 'Delete Grade', $this->TD );
        } else {
            $link = '<a href="' . esc_url_raw( add_query_arg( array('add_new' => '1', 'page' => $this->page ),  admin_url('admin.php?') ) ) .'" class="add-new-h2">' . __('Add New', $this->TD) . '</a>';
            $title = __("Grade Lists ", $this->TD) . $link ;
        }
        ?>
        <div class="wrap">
        <div id="icon-edit" class="icon32 icon32-posts-post">&nbsp;</div>
        <h2><?php echo $title; ?></h2>
        <br class="clear">
        <?php
        $str = '';
        if( !empty( $this->messages) ) {

            foreach ($this->messages as $key => $msg):
                $str .= '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
                $str .= "<p>$msg </p>";
                $str .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            endforeach;

        }
        ?>
        <?php echo $str; ?>
        <?php
        if( isset($_REQUEST['updated']) && !isset( $_REQUEST['orderby'] ) ) {
            echo '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
            echo __('<p>Updated</p>', $this->TD);
            echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        }
        if( isset($_REQUEST['deleted']) && !isset( $_REQUEST['orderby'] ) ) {
            echo '<div id="message" class="updated notice notice-success is-dismissible below-h2">';
            echo __('<p>Grade Deleted.</p>', $this->TD);
            echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        }
        ?>

        <?php
    }

    private function getFooter() {
        echo "</div>";
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

