<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_PromoteStudents extends RPS_Admin_Menu_MenuAbstract {

    public static function getInstance($page)
    {
        if (self::$instance == null) {
            self::$instance = new self;
            //self::$instance->pagehook = $slug;
            self::$instance->page = $page;
        }

        return self::$instance;
    }


    public function mainDiv() {


        //workflow
        /*
         * 1. Display Student Search form
         * 2. Show student list by ajax
         * 3. display Student Search form2
         * 4. Submit Form
         */
        /*
         1. get post_id
         2. check same postmeta already exist for this post_id
         3. insert new post
         */
    }

    function duplicate_post( $id ) {

        // Get the original post
        $post = get_post($id);

        // Copy the post and insert it
        if (isset($post) && $post!=null) {
            $new_id = $this->create_duplicate($post);

            if( $new_id ) {

                $this->copy_children($new_id, $post);

                $this->copy_post_meta($new_id, $post);

                $this->add_post_meta($new_id , array());


            }




            exit;

        } else {
            $post_type_obj = get_post_type_object( $post->post_type );
            wp_die(esc_attr(__('Copy creation failed, could not find original:', DUPLICATE_POST_I18N_DOMAIN)) . ' ' . htmlspecialchars($id));
        }
    }

    private function create_duplicate($post, $parent_id = '') {

        // We don't want to clone revisions
        if ($post->post_type == 'revision') return;

        $new_post_author = $this->get_current_user();

        $new_post = array(
            'menu_order' => $post->menu_order,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_author' => $new_post_author->ID,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_mime_type' => $post->post_mime_type,
            'post_parent' => $new_post_parent = empty($parent_id)? $post->post_parent : $parent_id,
            'post_password' => $post->post_password,
            'post_status' => $new_post_status = $post->post_status,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
        );

        $new_post_id = wp_insert_post($new_post);

        // If the copy is published or scheduled, we have to set a proper slug.
        if ($new_post_status == 'publish' || $new_post_status == 'future') {
            $post_name = wp_unique_post_slug($post->post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent);

            $new_post = array();
            $new_post['ID'] = $new_post_id;
            $new_post['post_name'] = $post_name;

            // Update the post into the database
            wp_update_post( $new_post );
        }

        delete_post_meta($new_post_id, '_dp_original');
        add_post_meta($new_post_id, '_dp_original', $post->ID);

        return $new_post_id;
    }

    private function copy_children($new_id, $post) {
        // get children
        $children = get_posts(array( 'post_type' => 'any', 'numberposts' => -1, 'post_status' => 'any', 'post_parent' => $post->ID ));
        // clone old attachments
        foreach($children as $child) {
            if ( $child->post_type != 'attachment') continue;
            $this->create_duplicate($child, $new_id);
        }
    }

    private function add_post_meta( $new_id, $metas = array() ) {
        $department_id  = isset( $data['_department_id'][0] ) ? $data['_department_id'][0] : '';
        $batch_id       = isset( $data['_batch_id'][0] ) ? $data['_batch_id'][0] : '';
        $semester_id    = isset( $data['_semester_id'][0] ) ? $data['_semester_id'][0] : '';
    }

    private function copy_post_meta($new_id, $post) {
        $data = get_post_custom($post->ID);
        $reg_no         = isset( $data['_reg_no'][0]) ? $data['_reg_no'][0] : '';
        $roll_no        = isset( $data['_roll_no'][0] ) ? $data['_roll_no'][0] : '';
        $lib_card       = isset( $data['_lib_card'][0] ) ? $data['_lib_card'][0] : '';

        $contact_info = isset( $data['_student_contact_info'][0] ) ? maybe_unserialize( $data['_student_contact_info'][0] ) : '';
        $personal_info = isset( $data['_student_personal_info'][0] ) ? maybe_unserialize( $data['_student_personal_info'][0] ) : '';
    }

    /**
     * Get the currently registered user
     */
    private function get_current_user() {
        if (function_exists('wp_get_current_user')) {
            return wp_get_current_user();
        } else if (function_exists('get_currentuserinfo')) {
            global $userdata;
            get_currentuserinfo();
            return $userdata;
        } else {
            global $wpdb;
            $user_login = $_COOKIE[USER_COOKIE];
            $sql = $wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_login=%s", $user_login);
            $current_user = $wpdb->get_results($sql);
            return $current_user;
        }
    }



    /**
     * This function will load all required css, js and other function on wp hook
     */
    public function onLoadPage() {
        //die('called');
        //$this->loadCss();
        //$this->loadJs();
        add_action('admin_enqueue_scripts', array( $this, 'loadCss' ) );
        add_action('admin_enqueue_scripts', array( $this, 'loadJs' ) );
    }

    public function loadCss() {
        wp_enqueue_style('rps_bootstrap');
    }

    public function loadJs() {
        wp_enqueue_script('rps_bootstrap');
        //wp_enqueue_script('fluidvids');
    }

}