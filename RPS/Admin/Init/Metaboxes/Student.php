<?php
if(!defined('WPINC')) {
    die();
}

final class RPS_Admin_Init_Metaboxes_Student {
    private static $instance;
    private $TD, $dbs;
    public static function getInstance(){
        if(self::$instance==null){
            self::$instance = new self;
            self::$instance->TD = RPS_Result_Management::TD;
            self::$instance->dbs = new RPS_Helper_DBS();
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        //metaboxes
        add_action( 'add_meta_boxes_' . RPS_Result_Management::STUDENT, array($this,'metaBoxes'),10,1);
        add_action( 'save_post', array( $this, 'saveMeta' ) );
    }


    public function metaBoxes( $post ) {
        add_meta_box('faculty-info', __('Faculty Information',$this->TD), array($this,'facultyMeta'));
        add_meta_box('personal-info', __('Personal Details',$this->TD), array($this,'personalMeta'));
        add_meta_box('contact-info', __('Contact Information',$this->TD), array($this,'contactMeta'));
    }
    
    public function saveMeta( $post_id ) {
        //print_r($_POST);
        //die();
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return $post_id;
        if( get_post_type() != RPS_Result_Management::STUDENT )
            return $post_id;
        //check user permission here....
        $change = false;
        if ( isset($_POST['student_faculty_meta_nonce']) && check_admin_referer( 'student_faculty_meta', 'student_faculty_meta_nonce' ) ) {
            
            $data = array_map('sanitize_text_field',$_POST['student_faculty_meta']);
            $data = array_map('trim', $data);
            
            update_post_meta($post_id,'_reg_no',$data['reg_no']);
            update_post_meta($post_id,'_roll_no',$data['roll_no']);
            update_post_meta($post_id,'_lib_card',$data['lib_card']);

            global $wpdb;
            $query = $wpdb->prepare("SELECT id FROM `{$wpdb->rps_marks}` WHERE student_id = %d LIMIT 1", array( $post_id ));
            if( null ===  $wpdb->get_var($query)) {
                update_post_meta($post_id, '_department_id', $data['department_id']);
                update_post_meta($post_id, '_batch_id', $data['batch_id']);
                update_post_meta($post_id, '_semester_id', $data['semester_id']);
            }

            //delete student cache - will think about it later...
            $change = true;
        }
        
        if ( isset( $_POST['student_personal_meta_nonce'] ) && check_admin_referer( 'student_personal_meta', 'student_personal_meta_nonce' ) ) {
            $data = array_map('sanitize_text_field',$_POST['student_personal_meta']);
            $data = array_map('trim', $data);
            update_post_meta($post_id, '_student_personal_info', $data);
            
            $change = true;
        }
        
        if ( isset( $_POST['student_contact_meta_nonce'] ) && check_admin_referer( 'student_contact_meta', 'student_contact_meta_nonce' ) ) {
            $data = array_map('sanitize_text_field',$_POST['student_contact_meta']);
            $data = array_map('trim', $data);
            update_post_meta($post_id, '_student_contact_info', $data);
            
            $change = true;
        }
        
        if($change == true) {
            //delete transient cache
            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
            RPS_Helper_Function::delete_transient();
        }
        
    } // end of saveMeta
    
    public function facultyMeta( $post ) {
        $data = get_post_custom($post->ID);
        //print_r($data);
        if( !empty( $data ) ) {
            $reg_no         = isset( $data['_reg_no'][0]) ? $data['_reg_no'][0] : '';
            $roll_no        = isset( $data['_roll_no'][0] ) ? $data['_roll_no'][0] : '';
            $department_id  = isset( $data['_department_id'][0] ) ? $data['_department_id'][0] : '';
            $batch_id       = isset( $data['_batch_id'][0] ) ? $data['_batch_id'][0] : '';
            $semester_id    = isset( $data['_semester_id'][0] ) ? $data['_semester_id'][0] : '';
            $lib_card       = isset( $data['_lib_card'][0] ) ? $data['_lib_card'][0] : '';
        }
        else {
            $reg_no = "";
            $roll_no = "";
            $department_id = "";
            $batch_id = "";
            $semester_id = "";
            $lib_card = "";
        }
        
        $departments = $this->dbs->getDepartmentList();

        $dept_ids = array();
        $semesters = array();
        $batches = array();

        if( !is_wp_error( $departments ) ) {
            $dept_ids = array_keys( $departments );

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
        }

        $department_id = RPS_Helper_Function::is_numeric( $department_id ) ?  $department_id : ( empty($dept_ids) ? '' : $dept_ids[0] );
        $semester_id = RPS_Helper_Function::is_numeric($semester_id) ? $semester_id : ( empty($semesters) ? '' : key( $semesters[ $department_id ] ) );
        $batch_id = RPS_Helper_Function::is_numeric($batch_id) ? $batch_id : ( empty($batches) ? '' : key( $batches[ $department_id ] ) );

        //echo "$department_id $batch_id $semester_id";
        
        //print_r($batches);
        
        wp_nonce_field( 'student_faculty_meta','student_faculty_meta_nonce' );
    ?>
        <table class="form-table" id="student_faculty_meta">
            <tr valign="top">
                <th scope="row">
                    <label for="reg_no"><?php _e('Registration No',$this->TD); ?> * </label>
                </th>
                <td>
                    <input type="text" name="student_faculty_meta[reg_no]" id="reg_no" value="<?php echo $reg_no; ?>" class="">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="roll_no"><?php _e('Class Roll No',$this->TD); ?> * </label>
                </th>
                <td>
                    <input type="text" name="student_faculty_meta[roll_no]" id="roll_no" value="<?php echo $roll_no; ?>" class="">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="department_id"><?php _e('Department/Class',$this->TD); ?> * </label>
                </th>
                <td>
                    <?php if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ): ?>
                    <select name="student_faculty_meta[department_id]" id="department_id" class="regular-text">
                    <?php
                      foreach ($departments as $id => $name):
                        echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                      endforeach;
                    ?>
                    </select>
                        <p><em><?php _e('You can change as long as there is no entry for this student on result table.', $this->TD); ?></em></p>
                    <?php else: 
                        echo esc_attr($departments->get_error_message());
                    endif; ?>
                    
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="batch_id"><?php _e('Batch/Year',$this->TD); ?> * </label>
                </th>
                <td>
                    <?php if(!empty($batches)) { ?>
                    <select name="student_faculty_meta[batch_id]" id="batch_id" class="">
                    <?php
                        if($department_id != '' && RPS_Helper_Function::is_numeric($department_id)) {
                            $batch = isset($batches[$department_id]) ? $batches[$department_id] : '';
                            if(is_array($batch) && !empty($batch)) {
                                foreach ($batch as $key => $name):
                                    echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
                                endforeach;
                            }
                            
                        }
                    ?>
                    </select>
                    <p><em><?php _e('You can change as long as there is no entry for this student on result table.', $this->TD); ?></em></p>
                    <?php } else {
                        $link = '';
                        echo __('No batch found. Please add batch first to continue.', $this->TD);
                    }
                    ?>
                </td>
            </tr>

            <!-- Semester Select Box -->
            <tr valign="top">
                <th scope="row">
                    <label for="semester_id"><?php _e('Semester',$this->TD); ?></label>
                </th>
                <td>
                    <?php if(!empty($semesters)): ?>
                        <select name="student_faculty_meta[semester_id]" id="semester_id" class="">
                            <?php
                            if( $department_id != '' && RPS_Helper_Function::is_numeric($department_id) ) {
                                $semester = isset($semesters[$department_id]) ? $semesters[$department_id] : array();
                                if( is_array($semester) && !empty($semester) ) {
                                    foreach ($semester as $id => $name):
                                        echo "<option value='{$id}' ".selected($semester_id, $id, false).">{$name}</option>";
                                    endforeach;
                                }
                            }
                            ?>
                        </select>
                        <p><em><?php _e('You can change as long as there is no entry for this student on result table.', $this->TD); ?></em></p>
                    <?php else:
                        echo __('No semester/section found.', $this->TD);
                    endif; ?>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for=""><?php _e('Library Card',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_faculty_meta[lib_card]" id="lib_card" value="<?php echo $lib_card; ?>" class="medium-text">
                </td>
            </tr>
            
        </table>

        <script type="text/javascript">        
            jQuery(function($){
                var semesters = <?php echo json_encode($semesters) ?>;
                var batches = <?php echo json_encode($batches) ?>;

                //$('#wpbody').on('#department_id', 'change', function() {
                   // alert('hi');
                $('#department_id').on('change',function() {
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

                    //populate batch dropdown box
                    var j = batches[id];
                    console.log(j);
                    $('#batch_id option').remove();

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
    
    public function personalMeta($post) {
        $data = maybe_unserialize(get_post_meta($post->ID,'_student_personal_info',true));
        
        if(empty($data) || $data == "") {
            $data = array(
                'f_name' => "",
                'm_name' => "",
                'gender' => "",
                'dob' => "",
                'email' => "",
                'stu_phone' => "",
                'blood_group' => "",
                'religion' => "",
                'nationality' => "",
            );
        }
        
        
    ?>
        
        <?php wp_nonce_field( 'student_personal_meta','student_personal_meta_nonce' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="f_name"><?php _e('Father\'s Name',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[f_name]" id="f_name" value="<?php echo $data['f_name']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="m_name"><?php _e('Mother\'s Name',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[m_name]" id="m_name" value="<?php echo $data['m_name']; ?>" class="regular-text">
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="gender"><?php _e('Gender',$this->TD); ?></label>
                </th>
                <td>
                    <select name="student_personal_meta[gender]" id="gender">
                        <option value="Male"    <?php echo isset($data['gender']) && $data['gender'] == "Male"    ? "selected='selected'" : ''; ?> ><?php _e( 'Male', $this->TD ); ?></option>
                        <option value="Female"  <?php echo isset($data['gender']) && $data['gender'] == "Female"  ? "selected='selected'" : ''; ?> ><?php _e( 'Female', $this->TD ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="dob"><?php _e('Date of birth',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[dob]" id="dob" value="<?php echo $data['dob']; ?>" class="regular-text" readonly="readonly">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="email"><?php _e('Email Address',$this->TD); ?></label>
                </th>
                <td>
                    <input type="email" name="student_personal_meta[email]" id="email" value="<?php echo $data['email']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="stu_phone"><?php _e('Contact Number',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[stu_phone]" id="stu_phone" value="<?php echo $data['stu_phone']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="blood_group"><?php _e('Blood Group',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[blood_group]" id="blood_group" value="<?php echo $data['blood_group']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="religion"><?php _e('Religion',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[religion]" id="religion" value="<?php echo $data['religion']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="nationality"><?php _e('Nationality',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_personal_meta[nationality]" id="nationality" value="<?php echo $data['nationality']; ?>" class="regular-text">
                </td>
            </tr>
            
        </table>
        
        <script type="text/javascript">
            jQuery(function($){
                $( "#dob" ).datepicker({
                    yearRange: "c-100:c+0",
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: "yy-mm-dd"
                });
            });    
        </script>    
        
        
    <?php
    }

    public function contactMeta($post) {
        $data = maybe_unserialize(get_post_meta($post->ID,'_student_contact_info',true));
        if(empty($data) || $data == ""){
            $data['present_address'] = "";
            $data['permanent_address'] = "";
            $data['gaurdian_name'] = "";
            $data['gaurdian_address'] = "";
            $data['gaurdian_contact_no'] = "";
            $data['gaurdian_email'] = "";
        }
        $data = array_map('trim', $data);
    ?>    
        
        <?php wp_nonce_field( 'student_contact_meta','student_contact_meta_nonce' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="present_address"><?php _e('Present Address',$this->TD); ?></label>
                </th>
                <td>
                    <textarea name="student_contact_meta[present_address]" id="present_address" class="large-text" rows="4"><?php echo $data['present_address']; ?></textarea>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="permanent_address"><?php _e('Permanent Address',$this->TD); ?></label>
                </th>
                <td>
                    <textarea name="student_contact_meta[permanent_address]" id="permanent_address" class="large-text" rows="4"><?php echo $data['permanent_address']; ?></textarea>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="gaurdian_name"><?php _e('Local Gaurdian Name',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_contact_meta[gaurdian_name]" id="gaurdian_name" value="<?php echo $data['gaurdian_name']; ?>" class="large-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="gaurdian_address"><?php _e('Gaurdian Address',$this->TD); ?></label>
                </th>
                <td>
                    <textarea name="student_contact_meta[gaurdian_address]" id="gaurdian_address" class="large-text" rows="4"><?php echo $data['gaurdian_address']; ?></textarea>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="gaurdian_contact_no"><?php _e('Gaurdian Contact No',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="student_contact_meta[gaurdian_contact_no]" id="gaurdian_contact_no" value="<?php echo $data['gaurdian_contact_no']; ?>" class="large-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="gaurdian_email"><?php _e('Gaurdian Email Address',$this->TD); ?></label>
                </th>
                <td>
                    <input type="email" name="student_contact_meta[gaurdian_email]" id="gaurdian_email" value="<?php echo $data['gaurdian_email']; ?>" class="large-text">
                </td>
            </tr>
            
        </table>
        
        
    <?php    
    }
}

?>
