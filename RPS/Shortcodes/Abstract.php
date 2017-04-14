<?php

if( !defined( 'WPINC' ) ) {
    die();
}

abstract class RPS_Shortcodes_Abstract {

    protected $pagehook, $error, $messages, $TD;
    protected $helper, $page, $dbs, $plugin_slug;
    protected $DIR, $URL, $VER;
    protected $student, $result, $course;
    protected $atts;
    protected $const;

    public function __construct() {
        $this->helper = new RPS_Helper_Function();
        $this->dbs = new RPS_Helper_DBS();
        $this->student = new RPS_Helper_Student();
        $this->result = new RPS_Helper_Result();

        $this->TD =  RPS_Result_Management::TD;
        $this->plugin_slug = RPS_Result_Management::PLUGIN_SLUG;

        $this->DIR = RPS_Result_Management::DIR();
        $this->URL = RPS_Result_Management::URL();
        $this->VER = RPS_Result_Management::VER;

        $this->atts = array();
        $this->const = false;

        $this->error = array();
        $this->messages = array();
    }

}