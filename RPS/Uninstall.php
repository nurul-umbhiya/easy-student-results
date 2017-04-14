<?php
if(!defined('WPINC')) {
    die();
}

//1. delete all students and courses
//2. delete all departments
//3. delete all semesters
//4. delete all batches
//5. Drop all custom db tables

final class RPS_Uninstall {
    private static $instance;

    public static function getInstance() {
        if(self::$instance==null) {
            self::$instance = new self;
        }

        //make sure we are calling it properly
        if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            $options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
            if ( isset($options['delete_data']) && $options['delete_data'] == 'on' ) {
                self::$instance->deleteDbTables();
                self::$instance->deleteOptions();
                self::$instance->deleteStudents();
                self::$instance->deleteCourses();
                self::$instance->deleteTransient();
            }
        }

        return self::$instance;
    }

    public function __construct() {
        ;
    }

    private function deleteDbTables() {
        global $wpdb;
        $tables = array();

        $tables[]   = RPS_Result_Management::getTablePrefix() . 'departments';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'batches';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'exams';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'grade';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'exam_records';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'exam_record_meta';
        $tables[]   = RPS_Result_Management::getTablePrefix() . 'marks';

        foreach ( $tables as $table ) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    private function deleteOptions() {
        $options = array(
            RPS_Result_Management::PLUGIN_SLUG . '_basics',
            RPS_Result_Management::PLUGIN_SLUG . '_students',
            RPS_Result_Management::PLUGIN_SLUG . '_results',
            RPS_Result_Management::PLUGIN_SLUG . '_api'
        );

        foreach ( $options as $option ) {
            delete_option($option);
        }
    }

    private function deleteStudents() {
        global $wpdb;

        $query = $wpdb->prepare("SELECT `ID` FROM `{$wpdb->posts}` WHERE post_type= %s", array(RPS_Result_Management::STUDENT));
        $results = $wpdb->get_results($query, ARRAY_A);
        foreach ( $results as $row ) {
            wp_delete_post($row['ID'], true);
        }
    }

    private function deleteCourses() {
        global $wpdb;

        $query = $wpdb->prepare("SELECT `ID` FROM `{$wpdb->posts}` WHERE post_type= %s", array(RPS_Result_Management::COURSE));
        $results = $wpdb->get_results($query, ARRAY_A);
        foreach ( $results as $row ) {
            wp_delete_post($row['ID'], true);
        }
    }

    private function deleteTransient() {
        if ( ! defined( RPS_Result_Management::PLUGIN_SLUG . '_delete_transient' ) )
            define(RPS_Result_Management::PLUGIN_SLUG . '_delete_transient', true);

        RPS_Helper_Function::delete_transient();
    }
}