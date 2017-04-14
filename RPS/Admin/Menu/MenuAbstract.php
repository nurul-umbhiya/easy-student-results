<?php

if( !defined( 'WPINC' ) ) {
	die();
}

abstract class RPS_Admin_Menu_MenuAbstract {
	protected static $instance;
	protected $pagehook, $error, $messages, $TD;
	protected $helper, $page, $dbs, $plugin_slug;
	protected $DIR, $URL, $VER;
	protected $student, $result, $course;

	protected function __construct() {
		$this->helper = new RPS_Helper_Function();
		$this->dbs = new RPS_Helper_DBS();

		$this->TD =  RPS_Result_Management::TD;
		$this->plugin_slug = RPS_Result_Management::PLUGIN_SLUG;

		$this->DIR = RPS_Result_Management::DIR();
		$this->URL = RPS_Result_Management::URL();
		$this->VER = RPS_Result_Management::VER;

		$this->error = array();
		$this->messages = array();
	}

	abstract public function onLoadPage();
	abstract public function mainDiv();

	public static function getPageHook() {
		return self::$instance->page;
		//return $this->pagehook;
	}

}