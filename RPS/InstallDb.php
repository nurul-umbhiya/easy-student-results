<?php
if(!defined('WPINC')) {
    die();
}

class RPS_InstallDb {
    private static $instance;
    
    public static function getInstance() {
        if(self::$instance==null) {
            self::$instance = new self;
            //self::$instance->createDB();
            //self::$instance->insertGPA();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        ;
    }


    
    public function createDB() {
        global $wpdb;
        $sqls = array();

        $rps_department   = RPS_Result_Management::getTablePrefix() . 'departments';
        $rps_batch        = RPS_Result_Management::getTablePrefix() . 'batches';
        $rps_exam         = RPS_Result_Management::getTablePrefix() . 'exams';
        $rps_grade        = RPS_Result_Management::getTablePrefix() . 'grade';
        $rps_exam_record  = RPS_Result_Management::getTablePrefix() . 'exam_records';
        $rps_exam_record_meta = RPS_Result_Management::getTablePrefix() . 'exam_record_meta';
        $rps_marks        = RPS_Result_Management::getTablePrefix() . 'marks';

        if ( method_exists( $wpdb, 'get_charset_collate' ) ) {
            $charset_collate = $wpdb->get_charset_collate();
        } else {
            if (!empty ($wpdb->charset))
                $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
            if (!empty ($wpdb->collate))
                $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        //UNIQUE KEY id (id)
        //id mediumint(9) NOT NULL AUTO_INCREMENT,
        $sqls[] = "CREATE TABLE {$rps_department} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name varchar(50) NOT NULL,
                    slug varchar(28) NOT NULL,
                    full_name varchar(150) NOT NULL,
                    description text NOT NULL,
                    credit decimal(5,2) unsigned NOT NULL DEFAULT '0',
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) $charset_collate;";
        
        
        $sqls[] = "CREATE TABLE {$rps_batch} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    department_id bigint(20) unsigned NOT NULL,
                    name varchar(50) NOT NULL,
                    current_sem bigint(20) unsigned NOT NULL,
                    session_month enum('01','02','03','04','05','06','07','08','09','10','11','12') NOT NULL,
                    session_year year(4) NOT NULL,
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) $charset_collate ;";

        $sqls[] = "CREATE TABLE {$rps_grade} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    grade varchar(250) NOT NULL,
                    marks varchar(250) NOT NULL,
                    grade_point numeric (5,2) NOT NULL,
                    grade_classification varchar(250) NOT NULL,
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) $charset_collate ;";

        $sqls[] = "CREATE TABLE {$rps_exam} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name varchar(250) NOT NULL,
                    exam_month enum('01','02','03','04','05','06','07','08','09','10','11','12') NOT NULL,
                    exam_year year(4) NOT NULL,
                    display tinyint(1) unsigned NOT NULL DEFAULT '1',
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) $charset_collate ;";

        $sqls[] = "CREATE TABLE {$rps_exam_record} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    exam_id bigint(20) unsigned NOT NULL,
                    department_id bigint(20) unsigned NOT NULL,
                    batch_id bigint(20) unsigned NOT NULL,
                    semester_id bigint(20) unsigned NOT NULL,
                    highest_grade varchar(250) NOT NULL,
                    highest_total_marks NUMERIC (5,2) NOT NULL DEFAULT '0.0',
                    display tinyint(1) unsigned NOT NULL DEFAULT '1',
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) $charset_collate;";

        $sqls[] = "CREATE TABLE {$rps_exam_record_meta} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    exam_record_id bigint(20) unsigned NOT NULL,
                    student_id bigint(20) unsigned NOT NULL,
                    meta_key varchar(255) NOT NULL,
                    meta_value longtext NOT NULL
                  ) $charset_collate;";

        $sqls[] = "CREATE TABLE {$rps_marks} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    exam_record_id bigint(20) unsigned NOT NULL,
                    student_id bigint(20) unsigned NOT NULL,
                    subject_id bigint(20) unsigned NOT NULL,
                    grade_id bigint(20) unsigned NOT NULL,
                    marks_obtained numeric (5,2) NOT NULL DEFAULT '0.0',
                    display tinyint(1) unsigned NOT NULL DEFAULT '1',
                    active tinyint(1) unsigned NOT NULL DEFAULT '1',
                    added int(10) unsigned NOT NULL,
                    updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY student (exam_record_id,student_id,subject_id)
                  ) $charset_collate;";



        //new table for highest marks/grade subject wise, will do it later
        

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sqls as $key => $sql) {
            dbDelta( $sql );
        }

        //die();
    }


    public function insertGPA() {
        //check cgpa exist on post table
        global $wpdb;

        $rps_grade        = RPS_Result_Management::getTablePrefix() . 'grade';

        $query = "SELECT * FROM `{$rps_grade}`";

        $result = $wpdb->get_results($query, ARRAY_A);

        if ( empty( $result ) ) {
            //now insert data
            $data = array(
                array(
                    'marks'         => '80-100',
                    'grade'         => 'A+',
                    'grade_point'   => '4.0',
                    'grade_classification' => 'Distinction',
                    'added'         => time()
                ),
                array(
                    'marks'         => '75-79',
                    'grade'         => 'A',
                    'grade_point'   => '3.75',
                    'grade_classification' => 'Distinction',
                    'added'         => time()
                ),
                array(
                    'marks'         => '70-74',
                    'grade'         => 'A-',
                    'grade_point'   => '3.50',
                    'grade_classification' => 'Credit',
                    'added'         => time()
                ),
                array(
                    'marks'         => '65-69',
                    'grade'         => 'B+',
                    'grade_point'   => '3.25',
                    'grade_classification' => 'Credit',
                    'added'         => time()
                ),
                array(
                    'marks'         => '60-64',
                    'grade'         => 'B',
                    'grade_point'   => '3.00',
                    'grade_classification' => 'Credit',
                    'added'         => time()
                ),
                array(
                    'marks'         => '55-59',
                    'grade'         => 'B-',
                    'grade_point'   => '2.75',
                    'grade_classification' => 'Pass',
                    'added'         => time()
                ),
                array(
                    'marks'         => '50-54',
                    'grade'         => 'C+',
                    'grade_point'   => '2.50',
                    'grade_classification' => 'Pass',
                    'added'         => time()
                ),
                array(
                    'marks'         => '45-49',
                    'grade'         => 'C',
                    'grade_point'   => '2.25',
                    'grade_classification' => 'Compensatable Fail/Marginal Fail',
                    'added'     => time()
                ),
                array(
                    'marks'         => '40-44',
                    'grade'         => 'D',
                    'grade_point'   => '2.0',
                    'grade_classification' => 'Compensatable Fail/Marginal Fail',
                    'added'     => time()
                ),
                array(
                    'marks'         => 'Less than 40',
                    'grade'         => 'F',
                    'grade_point'   => '0',
                    'grade_classification' => 'Non-compensatable Fail',
                    'added'         => time()
                ),
            );

            $format_value = array( '%s', '%s', '%f', '%s', '%d' );

            foreach ( $data as $key => $value ) {
                $wpdb->insert( $rps_grade, $value, $format_value );
            }
        }
    }
}

