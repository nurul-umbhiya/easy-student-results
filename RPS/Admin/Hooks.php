<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Hooks {
    private static $instance;
    
    public static function getInstance() {
        if(self::$instance==null){
            self::$instance = new self;
            self::$instance->actions();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }
    
    private function actions() {
        if(is_admin()){
            add_action( RPS_Result_Management::PLUGIN_SLUG . '_department_edited', array($this,'department_edited'),10,1);
            add_action( RPS_Result_Management::PLUGIN_SLUG . '_department_added', array($this,'department_added'),10,2);
            add_action( RPS_Result_Management::PLUGIN_SLUG . '_batch_added', array($this,'batch_added'),10,1);
            add_action( RPS_Result_Management::PLUGIN_SLUG . '_batch_edited', array($this,'batch_edited'),10,2);
        }
    }
    
    function department_edited( $dept_id ) {
        //delete transient here
        delete_transient( RPS_Result_Management::PLUGIN_SLUG . "_department_list");
        delete_transient( RPS_Result_Management::PLUGIN_SLUG . "_semester_list");

        //maybe delete student list here
    }
    
    function department_added( $insert_id, $data ) {
        //delete transient here
        delete_transient( RPS_Result_Management::PLUGIN_SLUG . "_department_list");
        delete_transient( RPS_Result_Management::PLUGIN_SLUG . "_semester_list");

        //maybe delete student list here
    }
    
    function batch_edited( $data, $id ) {
        $transient = RPS_Result_Management::PLUGIN_SLUG . "_batch_list_" . $data['department_id'];
        delete_transient($transient);

        //maybe delete student list here
    }
    function batch_added( $data ) {
        $transient = RPS_Result_Management::PLUGIN_SLUG . "_batch_list_" . $data['department_id'];
        delete_transient($transient);

        //maybe delete student list here
    }
}