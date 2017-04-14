<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Init_Metaboxes_Course {
    private static $instance;
    private $dbs,$TD;
    
    public static function getInstance(){
        if(self::$instance==null){
            self::$instance = new self;
            self::$instance->dbs = new RPS_Helper_DBS();
            self::$instance->TD = RPS_Result_Management::TD;
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        add_action( 'add_meta_boxes_' . RPS_Result_Management::COURSE, array($this,'metaBoxes'),10,1);
        add_action( 'save_post', array( $this, 'saveMeta' ) );
    }
    
    
    public function metaBoxes() {
        add_meta_box('course-info', __('Course Information',$this->TD), array($this,'courseInfoMeta'));
        add_meta_box('course-details', __('Course Detailed Information',$this->TD), array($this,'courseDetailsMeta'));
    }
    
    public function saveMeta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return $post_id;
        if( get_post_type() != RPS_Result_Management::COURSE )
            return $post_id;
        //check user permission here....
        $cache = false;
        if ( isset( $_POST['course_info_meta_nonce'] ) && check_admin_referer( 'course_info_meta', 'course_info_meta_nonce' ) ) {
            
            $data = array_map('sanitize_text_field',$_POST['course_info_meta']);
            $data = array_map('trim', $data);
            update_post_meta($post_id, '_course_code', $data['course_code']);

            //check if course exist on rps_marks table
            global $wpdb;
            $query = $wpdb->prepare("SELECT id FROM `{$wpdb->rps_marks}` WHERE subject_id = %d LIMIT 1", array( $post_id ));
            if( null ===  $wpdb->get_var($query)) {
                update_post_meta($post_id, '_semester_id', $data['semester_id']);
                update_post_meta($post_id, '_department_id', $data['department_id']);
            }

            $cache = true;
        }
        
        if ( isset( $_POST['course_details_meta_nonce'] ) && check_admin_referer('course_details_meta', 'course_details_meta_nonce') ) {
            
            $data = array_map('sanitize_text_field',$_POST['course_details_meta']);
            $data = array_map('trim',$_POST['course_details_meta']);
            update_post_meta($post_id, '_course_details', $data);
            
            $cache = true;
        }
        
        //delete student cache
        if($cache == true) {
            //delete transient cache
            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
            RPS_Helper_Function::delete_transient();
        }
    }
    
    public function courseInfoMeta($post) {
        $departments = $this->dbs->getDepartmentList();
        
        if( !is_wp_error($departments) ) {
            $dept_ids = array_keys($departments);
            $semesters = array();
            if(!empty($dept_ids)):
                foreach ($dept_ids as $id):
                    $sem = $this->dbs->getSemesterList($id);
                    $semesters[$id] = is_wp_error($sem) ? array() : $sem;
                endforeach;
            endif;
        }
        
        $data = get_post_custom( $post->ID );
        if(!empty($data)) {
            $course_code = $data['_course_code'][0];
            $department_id = $data['_department_id'][0];
            $semester_id = $data['_semester_id'][0];
        }
        else {
            $course_code = '';
            $department_id = '';
            $semester_id = '';
        }
        
        
        
    ?>

        <?php  wp_nonce_field( 'course_info_meta','course_info_meta_nonce' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="course_code"><?php _e('Course Code',$this->TD); ?> *</label>
                </th>
                <td>
                    <input type="text" name="course_info_meta[course_code]" id="course_code" value="<?php echo $course_code; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="department_id"><?php _e('Department/Class',$this->TD); ?> *</label>
                </th>
                <td>
                    <?php if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ): ?>
                    <select name="course_info_meta[department_id]" id="department_id" class="regular-text">
                    <?php
                      foreach ($departments as $id => $name):
                        echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                      endforeach;
                    ?>
                    </select>
                    <p><em><?php _e('You can change as long as there is no entry for this course on result table.', $this->TD); ?></em></p>
                    <?php else: 
                        echo esc_attr($departments->get_error_message());
                    endif; ?>
                    
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="semester_id"><?php _e('Semester/Section',$this->TD); ?></label>
                </th>
                <td>
                    <?php if(!empty($semesters)): ?>
                    <select name="course_info_meta[semester_id]" id="semester_id" class="regular-text">
                        <option value=""><?php _e('Select Semester', $this->TD); ?></option>
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
                        <p><em><?php _e('You can change as long as there is no entry for this course on result table.', $this->TD); ?></em></p>
                    <?php else: 
                        echo __('No semester/section found.', $this->TD);
                    endif; ?>
                </td>
            </tr>
        </table>

        <script type="text/javascript">        
            jQuery(function($) {
                
                
                var semesters = <?php echo json_encode($semesters) ?>;
                $('#department_id').on('change',function() {
                    var id = $(this).val();
                    var i = semesters[id];
                    console.log(i);
                    $('#semester_id option').remove();
                    $('#semester_id')
                        .append($("<option></option>")
                            .attr("value",'')
                            .text('Select Semester'));
                    // Add option to the select box from list
                    if(i !== null) {
                        $.each(i, function(key, value) {   
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
    
    public function courseDetailsMeta( $post ) {
        $data = maybe_unserialize( get_post_meta( $post->ID, '_course_details', true) );

        if( empty( $data ) || $data == "" ) {
            $data['total_marks'] = "";
            $data['credit'] = "";
            $data['course_type'] = "";
            $data['pre_requisist'] = "";
            $data['related_subject'] = "";
        }
        
        
    ?>

        <?php  wp_nonce_field('course_details_meta','course_details_meta_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="total_marks"><?php _e('Total Marks',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="course_details_meta[total_marks]" id="total_marks" value="<?php echo $data['total_marks']; ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="credit"><?php _e('Credit',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="course_details_meta[credit]" id="credit" value="<?php echo $data['credit']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="course_type"><?php _e('Course Type',$this->TD); ?></label>
                </th>
                <td>
                    <select name="course_details_meta[course_type]" id="course_type">
                        <?php
                        echo "<option value='1' " . selected($data['course_type'], "1", false) . ">" .__('Theory',$this->TD). "</option>";
                        echo "<option value='2' " . selected($data['course_type'], "2", false) . ">" .__('Lab',$this->TD). "</option>";
                        echo "<option value='3' " . selected($data['course_type'], "3", false) . ">" .__('Project / Thesis',$this->TD). "</option>";
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="pre_requisist"><?php _e('Pre-requisist Subject',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="course_details_meta[pre_requisist]" id="pre_requisist" value="<?php echo $data['pre_requisist']; ?>" class="regular-text">
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row">
                    <label for="related_subject"><?php _e('Related Theory / Lab Subject',$this->TD); ?></label>
                </th>
                <td>
                    <input type="text" name="course_details_meta[related_subject]" id="related_subject" value="<?php echo $data['related_subject']; ?>" class="regular-text">
                </td>
            </tr>
        </table>
    <?php
    }
    
 
}
