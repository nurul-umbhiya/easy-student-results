<?php

if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_Main {
    private static $instance;
    private $slug, $TD, $page_hook;
    private $role;

    public static function getInstance() {
        if(self::$instance==null){
            self::$instance = new self;
            self::$instance->slug = array();
            self::$instance->TD = RPS_Result_Management::TD;
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    public static function getSlug($slug) {
        if( isset( self::$instance->slug[$slug] ) ) {
            return self::$instance->slug[$slug];
        } else {
            return '';
        }
    }

    public static function getPage($page) {
        if( isset( self::$instance->page_hook[$page] ) ) {
            return self::$instance->page_hook[$page];
        } else {
            return '';
        }
    }


    private function actions() {
        //user role
        $option = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
        if ( isset($option['user_role']) && $option['user_role'] != ''
            && in_array($option['user_role'], array('manage_options','edit_pages','publish_posts','edit_posts','read')) ) {
            $this->role = $option['user_role'];
        } else {
            $this->role = 'manage_options';
        }

        add_action('admin_menu',array($this,'adminMenu'),5);
        add_action('admin_menu',array($this,'departmentMenu'),6);
        add_action('admin_menu',array($this,'batchMenu'),7);
        add_action('admin_menu',array($this,'gradeMenu'),14);
        add_action('admin_menu',array($this,'examsMenu'),15);
        add_action('admin_menu',array($this,'resultsMenu'),16);
        add_action('admin_menu', array($this, 'premiumMenu'), 1000);
    }

    public function adminMenu() {
        //$this->slug['main'] = add_menu_page( __('Easy Student Results', $this->TD), __('Student Results', $this->TD), "administrator", RPS_Result_Management::PLUGIN_SLUG, array($this,'dashboard'),'dashicons-book-alt' );

        //$this->slug['dashboard'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Dashboard", $this->TD), __("Dashboard", $this->TD), 'administrator', RPS_Result_Management::PLUGIN_SLUG, array($this,'dashboard'));
        //add_action('load-' . $this->slug['dashboard'] , array($this,'loadDashboard'));

        //$this->page_hook['main'] = $this->page_hook['dashboard'] = RPS_Result_Management::PLUGIN_SLUG;

        $this->slug['main'] = add_menu_page( __('Easy Student Results', $this->TD), __('Student Results', $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG, array($this,'department'),'dashicons-book-alt' );
    }

    public function departmentMenu() {
        $this->slug['department'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Department", $this->TD), __("Department", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG, array($this,'department'));
        add_action('load-' . $this->slug['department'] , array($this,'loadDepartment'));

        //$this->page_hook['department'] = RPS_Result_Management::PLUGIN_SLUG . '_department';
        $this->page_hook['department'] = RPS_Result_Management::PLUGIN_SLUG;
    }

    public function batchMenu() {
        $this->slug['batch'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Batch", $this->TD), __("Batch", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG . '_batch',array($this,'batch'));
        add_action('load-' . $this->slug['batch'] , array($this,'loadBatch'));

        $this->page_hook['batch'] = RPS_Result_Management::PLUGIN_SLUG . '_batch';
    }

    public function gradeMenu() {
        $this->slug['grade'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Grade / Division", $this->TD), __("Grade", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG . '_grade',array($this,'grade'));
        add_action('load-' . $this->slug['grade'] , array($this,'loadGrade'));

        $this->page_hook['grade'] = RPS_Result_Management::PLUGIN_SLUG . '_grade';
    }

    public function examsMenu() {
        $this->slug['exams'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Exams", $this->TD), __("Exams", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG . '_exams',array($this,'exams'));
        add_action('load-' . $this->slug['exams'] , array($this,'loadExams'));

        $this->page_hook['exams'] = RPS_Result_Management::PLUGIN_SLUG . '_exams';
    }

    public function resultsMenu() {
        $this->slug['results'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Results", $this->TD), __("Results", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG . '_results',array($this,'results'));
        add_action('load-' . $this->slug['results'] , array($this,'loadResults'));

        $this->page_hook['results'] = RPS_Result_Management::PLUGIN_SLUG . '_results';
    }

    public function premiumMenu() {
        $this->slug['premium'] = add_submenu_page(RPS_Result_Management::PLUGIN_SLUG, __('Easy Student Results', $this->TD) . ' - ' . __("Premium AddOns", $this->TD), __("Premium AddOns", $this->TD), $this->role, RPS_Result_Management::PLUGIN_SLUG . '_premium',array($this,'premiumAddons'));
        add_action('load-' . $this->slug['premium'] , array($this,'loadPremiumAddons'));

        $this->page_hook['premium'] = RPS_Result_Management::PLUGIN_SLUG . '_premium';
    }


    /* Main Dashboard Menu Functions */
    
    public function loadDashboard() {
        $obj = RPS_Admin_Menu_Dashboard::getInstance($this->page_hook['dashboard']);
        $obj->onLoadPage();
    }
    
    public function dashboard() {
        $obj = RPS_Admin_Menu_Dashboard::getInstance($this->page_hook['dashboard']);
        $obj->mainDiv();
    }
    
    /* Department Menu Functions */
    
    public function loadDepartment() {
        $obj = RPS_Admin_Menu_Department::getInstance($this->page_hook['department']);
        $obj->onLoadPage();
    }
    
    public function department() {
        $obj = RPS_Admin_Menu_Department::getInstance($this->page_hook['department']);
        $obj->mainDiv();
    }
    
    /* Batch Menu Functions */
    
    public function loadBatch() {
        $obj = RPS_Admin_Menu_Batch::getInstance($this->page_hook['batch']);
        $obj->onLoadPage();
    }
    
    public function batch() {
        $obj = RPS_Admin_Menu_Batch::getInstance($this->page_hook['batch']);
        $obj->mainDiv();
    }

    /* Exams Menu Functions */

    public function loadExams() {
        $obj = RPS_Admin_Menu_Exams::getInstance($this->page_hook['exams']);
        $obj->onLoadPage();
    }

    public function exams() {
        $obj = RPS_Admin_Menu_Exams::getInstance($this->page_hook['exams']);
        $obj->mainDiv();
    }

    /* Grade Menu Functions */

    public function loadGrade() {
        $obj = RPS_Admin_Menu_Grade::getInstance($this->page_hook['grade']);
        $obj->onLoadPage();
    }

    public function grade() {
        $obj = RPS_Admin_Menu_Grade::getInstance($this->page_hook['grade']);
        $obj->mainDiv();
    }

    /* Results Menu Functions */

    public function loadResults() {
        $obj = RPS_Admin_Menu_Result_Main::getInstance($this->page_hook['results']);
        $obj->onLoadPage();
    }

    public function results() {
        $obj = RPS_Admin_Menu_Result_Main::getInstance($this->page_hook['results']);
        $obj->mainDiv();
    }

    /*
    Premium Addons
    */
    public function LoadPremiumAddons() {
        $obj = RPS_Admin_Menu_PremiumAddOns::getInstance($this->page_hook['premium']);
        $obj->onLoadPage();
    }

    public function premiumAddons() {
        $obj = RPS_Admin_Menu_PremiumAddOns::getInstance($this->page_hook['premium']);
        $obj->mainDiv();
    }

}

