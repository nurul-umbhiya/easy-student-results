<?php
/*
Plugin Name: Easy Student Results
Plugin URI: https://www.nurul.me/
Description: Result Management System for School, College and University. Use [esr_results] to display result and [esr_students] to display student list.
Text Domain: easy-student-results
Version: 2.2.8
Author: Nurul Umbhiya
Author URI: https://www.nurul.me/
*/

if( !defined( 'WPINC' ) ) {
    die();
}

if( !class_exists( 'RPS_Result_Management' ) ) {
    final class RPS_Result_Management {

        private static $instance;
        private $dir, $url;

        const VER = 2.7;

        //do not change below constant
        const DS = '/';
        const PLUGIN_SLUG = 'rps_result';
        const TD = 'easy-student-results';
        const TABLE_PREFIX = 'rps_';
        const FILE = __FILE__;


        //Post type constant
        const STUDENT = 'rps_result_student';
        const COURSE = 'rps_result_course';

        public static function getInstance() {
            if (self::$instance == null) {
                self::$instance = new self;
                self::$instance->dir = dirname(__FILE__);
                self::$instance->url = plugins_url() . self::DS . basename(self::$instance->dir);
                self::$instance->autoload();
                self::$instance->setDbTables();
                self::$instance->actions();
            } else {
                throw new BadFunctionCallException(sprintf('Plugin %s already instantiated', __CLASS__));
            }
            return self::$instance;
        }

        /**
         * Get path to plagin dir relative to wordpress root
         * @param bool[optional] $noForwardSlash Whether path should be returned withot forwarding slash
         * @return string
         */
        public function getRelativePath($noForwardSlash = false) {
            $wp_root = str_replace('\\', '/', ABSPATH);
            return ($noForwardSlash ? '' : '/') . str_replace($wp_root, '', self::$instance->dir);
        }

        /**
         * Check whether plugin is activated as network one
         * @return bool
         */
        public function isNetwork() {
            if ( !is_multisite() )
                return false;

            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins[plugin_basename(self::FILE)]))
                return true;

            return false;
        }

        /**
         * Check whether permalinks is enabled
         * @return bool
         */
        public function isPermalinks() {
            global $wp_rewrite;

            return $wp_rewrite->using_permalinks();
        }

        /**
         * Return prefix for plugin database tables
         * @return string
         */
        public static function getTablePrefix() {
            global $wpdb;
            return $wpdb->prefix . self::TABLE_PREFIX;
        }

        /**
         * Return prefix for wordpress database tables
         * @return string
         */
        public function getWPPrefix() {
            global $wpdb;
            return ($this->isNetwork() ? $wpdb->base_prefix : $wpdb->prefix);
        }
        
        private function __construct() {
            ;
        }
        
        public function autoload() {
            require_once ( $this->dir . self::DS . "RPS" . self::DS . "Autoloader" . self::DS . "LoaderClass.php" );

            // Add the Autoloader
            $loader = new RPS_Autoloader_LoaderClass( "RPS", dirname( __FILE__ ) );
            $loader->register( );
        }
        
        private function setDbTables () {
            global $wpdb;
            $wpdb->rps_department   = self::getTablePrefix() . 'departments';
            $wpdb->rps_batch        = self::getTablePrefix() . 'batches';
            $wpdb->rps_exam         = self::getTablePrefix() . 'exams';
            $wpdb->rps_grade        = self::getTablePrefix() . 'grade';
            $wpdb->rps_exam_record  = self::getTablePrefix() . 'exam_records';
            $wpdb->rps_exam_record_meta = self::getTablePrefix() . 'exam_record_meta';
            $wpdb->rps_marks        = self::getTablePrefix() . 'marks';
        }

        /**
         * call all actions/filters here
         */
        private function actions() {
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));

            //this is for api access
            add_action( 'rest_api_init', array( $this, 'api' ) );

            //multisite install tables after adding a new blog
            add_action( 'wpmu_new_blog', array( $this, 'onCreateBlog'), 10, 6 );
            //delete tables after blog is deleted
            add_filter( 'wpmu_drop_tables', array($this, 'onDeleteBlog'), 10, 2 );

            if ( is_admin() ) {
                //register and localize all script/stylesheet for admin panel
                add_action( 'admin_enqueue_scripts', array( $this, 'adminRegisterScripts' ) , 1 );
                add_action( 'admin_enqueue_scripts', array( $this, 'adminRegisterStyles' ) , 1 );
                add_action( 'admin_enqueue_scripts', array( $this, 'adminLocalizeScripts' ) , 1 );
                
                //register menus
                RPS_Admin_Menu_Main::getInstance();
                
                //load hooks
                RPS_Admin_Hooks::getInstance();
                add_action( 'admin_init', array($this, 'actionAdminInit') );
                
                //menu fix
                add_filter( 'parent_file', array($this,'semesterMenu') );

                //settings page
                RPS_Admin_Menu_Settings::getInstance();
            }
            
            if ( !is_admin() ) {
                //register and localize all scripts/stylesheet for frontend
                add_action( 'wp_enqueue_scripts', array($this, 'adminRegisterScripts'), 1 );
                add_action( 'wp_enqueue_scripts', array($this, 'adminRegisterStyles'), 1 );
            }

            //load plugin text domain
            add_action( 'plugins_loaded', array($this,'loadTextdomain') );
            
            //load all post type here
            RPS_Admin_Init_PostType::getInstance();
            
            //load all taxonomies here
            RPS_Admin_Init_Taxonomi::getInstance();

            //shortcodes
            add_shortcode( 'esr_students',  array($this,'studentShortcode') );
            add_shortcode( 'esr_results',   array($this,'resultShortcode') );
            add_shortcode( 'esr_results2',   array($this,'resultShortcode2') );

            //load styles if shortcode exist
            add_action( 'wp_enqueue_scripts', array( $this, 'loadShortcodeScripts' ), 100 );
            
            //load ajax hooks
            if ( defined('DOING_AJAX') && DOING_AJAX ) {
                RPS_Helper_Ajax::getInstance();
            }

            add_action( 'init', array($this, 'custom_post_status'), 0 );

            if ( is_admin() ) {
	            //check for updates
	            $this->check_for_updates();
            }

            //advanced custom field support
            add_action(RPS_Result_Management::PLUGIN_SLUG . '_student_section_meta_single_print', array($this, 'esr_student_section_meta_single_print'), 10, 2 );
            add_filter(RPS_Result_Management::PLUGIN_SLUG . '_result_student_section_meta_single', array($this, 'esr_result_student_section_meta_single'), 10, 3);
        }

        function esr_student_section_meta_single_print($student_id, $meta_key) {
            if ( function_exists('get_fields') ) {
	            $all_fields = get_fields($student_id);
	            if ( is_array($all_fields) && array_key_exists($meta_key, $all_fields) ) {
		            echo esc_attr($all_fields[$meta_key]);
	            }
            }
        }

        function esr_result_student_section_meta_single($value, $student_id, $meta_key) {
	        if ( function_exists('get_fields') ) {
		        $all_fields = get_fields($student_id);
		        if ( is_array($all_fields) && array_key_exists($meta_key, $all_fields) ) {
			        $value = esc_attr($all_fields[$meta_key]);
		        }
	        }
            return $value;
        }

        private function check_for_updates() {
            $version = get_option( self::PLUGIN_SLUG . '_version', '' );

            if ( $version == '' ) {
                //delete cache
                RPS_Helper_Function::delete_transient();

                //this is version 1.8, run db updates
	            $db_class = RPS_InstallDb::getInstance();
	            $db_class->createDB();

	            //set default options
                $general_option = get_option(RPS_Result_Management::PLUGIN_SLUG .'_basics');
                if ( !array_key_exists('marks_js', $general_option) ) {
                    $general_option['marks_js'] = 'on';
                }
	            if ( !array_key_exists('percentage_js', $general_option) ) {
		            $general_option['percentage_js'] = 'on';
	            }
	            update_option(RPS_Result_Management::PLUGIN_SLUG .'_basics', $general_option);

                //update current version
	            $version = '1.9';
	            update_option(self::PLUGIN_SLUG . '_version', $version);
            }


            if ( $version == '1.9' ) {
	            $general_option = get_option(RPS_Result_Management::PLUGIN_SLUG .'_basics');
	            if ( !array_key_exists('show_picture', $general_option) ) {
		            $general_option['show_picture'] = 'off';
	            }

	            //delete transient cache
	            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);
	            RPS_Helper_Function::delete_transient();

	            //update current version
	            $version = '2.0';
	            update_option(self::PLUGIN_SLUG . '_version', $version);
            }

            if ( $version == '2.0' ) {
	            $db_class = RPS_InstallDb::getInstance();
	            $db_class->createDB();

	            //update current version
	            $version = '2.1';
	            update_option(self::PLUGIN_SLUG . '_version', $version);


            }

            if ( $version == '2.1' ) {
	            $version = '2.2';
	            update_option(self::PLUGIN_SLUG . '_version', '2.2');
            }

            if ( $version == '2.2' ) {
	            //delete cache
	            RPS_Helper_Function::delete_transient();

	            //update current version
	            $version = '2.2.1';
	            update_option(self::PLUGIN_SLUG . '_version', $version);
            }

	        if ( $version == '2.2.1' ) {
		        //delete cache
		        RPS_Helper_Function::delete_transient();

		        //update current version
		        $version = '2.2.2';
		        update_option(self::PLUGIN_SLUG . '_version', $version);
	        }

	        if ( $version == '2.2.2' ) {
		        //delete cache
		        RPS_Helper_Function::delete_transient();

		        //update current version
		        $version = '2.2.3';
		        update_option(self::PLUGIN_SLUG . '_version', $version);
	        }

	        if ( $version == '2.2.3' ) {
		        //delete cache
		        RPS_Helper_Function::delete_transient();

		        //update current version
		        $version = '2.2.4';
		        update_option(self::PLUGIN_SLUG . '_version', $version);
	        }

	        if ( $version == '2.2.4') {
		        $db_class = RPS_InstallDb::getInstance();
		        $db_class->createDB();

		        //update current version
		        $version = '2.2.7';
		        update_option(self::PLUGIN_SLUG . '_version', $version);
            }
        }

        // Register Custom Status
        public function custom_post_status() {

            $args = array(
                'label'                     => _x( 'Promote', 'Status General Name', 'easy-student-results' ),
                'label_count'               => _n_noop( 'Promote (%s)',  'Promoted (%s)', 'easy-student-results' ),
                'public'                    => false,
                'show_in_admin_all_list'    => false,
                'show_in_admin_status_list' => false,
                'exclude_from_search'       => true,
            );
            register_post_status( 'promoted', $args );

        }

        public function api() {
            //check api enabled or not
            $options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_api', array() );
            if ( isset($options['enable_api']) && $options['enable_api'] == 'on' ) {
                if ( class_exists('WP_REST_Controller') ) {
                    $api = new RPS_API();
                    $api->register_routes();
                }
            }
        }

        public function resultShortcode( $atts ) {
            ob_start();
            new RPS_Shortcodes_Result($atts);
            return ob_get_clean();
        }

        public function studentShortcode( $atts ) {
            ob_start();
            new RPS_Shortcodes_StudentList( $atts );
            return ob_get_clean();
        }

        public function resultShortcode2( $atts ) {
            ob_start();
            new RPS_Shortcodes_Result2( $atts );
            return ob_get_clean();
        }


        /**
         * This function will return RPS Result as menu
         * @param $file
         *
         * @return string
         */
        public function semesterMenu($file) {
            global $wpdb;
            $screen = get_current_screen();
            //echo "<pre>"; print_r($screen); 
            //echo $file; die;
            //echo RPS_Admin_Menu_Main::getSlug('department');
            //die;
            //rps-result_page_rps_result_department
            if( 'edit-tags' === $screen->base ) {
                $query = "SELECT slug FROM `{$wpdb->rps_department}`";
                $results = $wpdb->get_results($query,ARRAY_A);

                if($results !== NULL) {
                    foreach ($results as $row):
                        $slug = RPS_Result_Management::PLUGIN_SLUG . '_' . $row['slug'];
                        if ( $slug === $screen->taxonomy ) {
                            $file = 'rps_result';
                            add_action( 'admin_footer', array( $this, 'wpFooter' ) );
                            break;
                        }
                    endforeach;
                }
            }
            
            return $file;
        }

        public function wpFooter() {
            ?>
            <script>
                var url = '<?php echo 'admin.php?page=' . RPS_Result_Management::PLUGIN_SLUG . '_department' ?>';
                jQuery('a[href="'+url+'"]').addClass('current').parent().addClass('current');
            </script>
            <?php
        }

        
        public function actionAdminInit() {
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

            // check for one of our custom post types,
            // and start admin handling for that type
            switch ($typenow) {
                case self::STUDENT:
                    RPS_Admin_Init_PostFilters_Student::getInstance();
                    RPS_Admin_Init_Metaboxes_Student::getInstance();
                    break;
                case self::COURSE:
                    RPS_Admin_Init_PostFilters_Course::getInstance();
                    RPS_Admin_Init_Metaboxes_Course::getInstance();
                    break;


            }
        }

        public function adminRegisterStyles() {
            wp_register_style( 'jquery-ui',         $this->url .'/assets/jquery-ui/jquery-ui-1.10.4.custom.min.css', array(), '1.10.4' );
            wp_register_style( 'rps_bootstrap',     $this->url . '/assets/bootstrap-3.3.5/css/bootstrap.css', array(), '3.3.5' );
	        wp_register_style( 'rps_bootstrap4',     $this->url . '/assets/bootstrap-4/css/bootstrap.min.css', array(), '4.0' );

            wp_register_style( 'rps_sc_student_list',  $this->url . '/assets/css/sc_student_list.css', null, self::VER);

            wp_register_style( 'rps_bootstrap4',     $this->url . '/assets/bootstrap-4/css/bootstrap.css', array(), '4.0' );
        }

        public function adminRegisterScripts() {
            //register bootstrap
            wp_register_script( 'rps_bootstrap',    $this->url . '/assets/bootstrap-3.3.5/js/bootstrap.min.js', array( 'jquery' ), '3.3.5', true);

            //register sortcode script
            wp_register_script( 'rps_sc_student_list', $this->url . '/assets/js/sc_student_list.js', array('jquery'), self::VER, true);
            wp_register_script( 'rps_sc_result',    $this->url . '/assets/js/sc_result.js', array('jquery'), self::VER, true);
            wp_register_script( 'rps_sc_result2',    $this->url . '/assets/js/sc_result2.js', array('jquery'), self::VER, true);


            //register admin scripts
            wp_register_script( 'rps_marks',    $this->url . '/assets/js/marks.js', array('jquery'), self::VER, true);
	        wp_register_script( 'rps_percentage',    $this->url . '/assets/js/percentage.js', array('jquery'), self::VER, true);
	        wp_register_script( 'rps_result', $this->url . '/assets/js/result.js', array('jquery'), self::VER, true );

            //bootstrap 4
	        wp_register_script( 'rps_popper_js',    $this->url . '/assets/bootstrap-4/js/popper.min.js', array( 'jquery' ), '1.11.0');
	        wp_register_script( 'rps_bootstrap4',    $this->url . '/assets/bootstrap-4/js/bootstrap.min.js', array( 'jquery', 'rps_popper_js' ), '4.0', true);

            wp_localize_script(
                'rps_sc_result', "result",
                array (
                    'nonce' => wp_create_nonce('rps_print_student_result'),
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                )
            );

            wp_register_script( 'jquery_print',    $this->url . '/assets/js/jQuery.print.js', array('jquery'), '1.5.1', true);



        }

        function loadShortcodeScripts() {
            global $post;
            if( is_a( $post, 'WP_Post' ) ) {
                $flag = false;
                if ( has_shortcode( $post->post_content, 'esr_students') ) {
                    wp_enqueue_style('rps_sc_student_list');
                    $flag = true;
                }

                if ( has_shortcode( $post->post_content, 'esr_results') ) {
                    $flag = true;
                }

                if ( has_shortcode( $post->post_content, 'esr_results2') ) {
                    $flag = true;
                }

                if ( $flag ) {
                    $general = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );

                    if ( $general['bootstrap_css'] != 'on') {
                        wp_enqueue_style('rps_bootstrap');
                    }
                }

            }
        }


        public function adminLocalizeScripts() {
            //this function is for localize scripts
        }
        
        public function loadTextdomain() {
            //http://codex.wordpress.org/I18n_for_WordPress_Developers
            load_plugin_textdomain( self::TD, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' ); 
        }

        public function activate( $network_wide ) {
            // uncaught exception doesn't prevent plugin from being activated, therefore replace it with fatal error so it does
            set_exception_handler(create_function('$e', 'trigger_error($e->getMessage(), E_USER_ERROR);'));

            global $wpdb;

            if ( is_multisite() && $network_wide ) {

                // Get all blogs in the network and activate plugin on each one
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    //install/update all required database
                    $db_class = RPS_InstallDb::getInstance();
                    $db_class->createDB();
                    $db_class->insertGPA();

                    //predefine all options
                    $option_class = RPS_DefaultOptions::getInstance();
                    $option_class->createOptions();

                    restore_current_blog();
                }

            } else {
                //install/update all required database
                $db_class = RPS_InstallDb::getInstance();
                $db_class->createDB();
                $db_class->insertGPA();

                //predefine all options
                $option_class = RPS_DefaultOptions::getInstance();
                $option_class->createOptions();
            }
        }

        public function deactivate() {

        }

        public function onCreateBlog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
            $plugin = plugin_basename( __FILE__ );

            if ( is_plugin_active_for_network( $plugin ) ) {
                switch_to_blog( $blog_id );

                //install/update all required database
                $db_class = RPS_InstallDb::getInstance();
                $db_class->createDB();
                $db_class->insertGPA();

                //predefine all options
                $option_class = RPS_DefaultOptions::getInstance();
                $option_class->createOptions();

                restore_current_blog();
            }
        }

        function onDeleteBlog( $tables, $blog_id ) {

            $tables[]   = self::getTablePrefix() . 'departments';
            $tables[]   = self::getTablePrefix() . 'batches';
            $tables[]   = self::getTablePrefix() . 'exams';
            $tables[]   = self::getTablePrefix() . 'grade';
            $tables[]   = self::getTablePrefix() . 'exam_records';
            $tables[]   = self::getTablePrefix() . 'exam_record_meta';
            $tables[]   = self::getTablePrefix() . 'marks';

            return $tables;
        }
        
        public static function DIR() {
            return self::$instance->dir;
        }

        public static function URL() {
            return self::$instance->url;
        }
    }
    RPS_Result_Management::getInstance();
}
