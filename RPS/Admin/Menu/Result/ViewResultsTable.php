<?php
if ( !defined( 'WPINC' ) ) {
    die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RPS_Admin_Menu_Result_ViewResultsTable extends \WP_List_Table {
    private $TD, $helper, $page, $dbs;
    private $student_meta, $student, $metadata;
    private $exam_record_id, $department_id, $batch_id, $semester_id, $sl;
    private $result;

    function __construct($exam_record_id, $department_id, $batch_id, $semester_id = null ) {
        global $status, $page;

	    $this->result = new RPS_Helper_Result();
        $this->helper = new RPS_Helper_Function();
        $this->dbs = new RPS_Helper_DBS();
        $this->page = 'rps_result_results';
        $this->TD =  RPS_Result_Management::TD;

        $this->exam_record_id = $exam_record_id;
        $this->department_id = $department_id;
        $this->batch_id = $batch_id;
        $this->semester_id = $semester_id;
        $this->sl = 1;

        $this->student = new RPS_Helper_Student();

        $this->student_meta = $this->student->getStudentDetails($this->department_id, $this->batch_id, $this->semester_id, 'all');





        //Set parent defaults
        parent::__construct( array (
            'singular'  => 'view_results_record',   //singular name of the listed records
            'plural'    => 'view_results_records',  //plural name of the listed records
            'ajax'      => false            //does this table support ajax?
        ) );
        
        
    }

	function get_meta_data($student_id) {
		$this->metadata = $this->result->get_exam_record_meta( $this->exam_record_id, $student_id );

		//fix final result metadata
		if ( is_array($this->metadata) && array_key_exists('final_grade', $this->metadata) ) {
			$grade = $this->result->getGradeList();
			if ( array_key_exists( $this->metadata['final_grade'], $grade ) ) {
				$this->metadata['final_grade'] = $grade[ $this->metadata['final_grade'] ];
			}
		}
		//fix highest_marks
		$this->metadata['highest_marks'] = 0.00;

		//fix total_marks_obtained meta data
		if ( is_array($this->metadata) && !array_key_exists('total_marks_obtained', $this->metadata) ) {
			$this->metadata['total_marks_obtained'] = 0;
		}
	}
    
    function get_columns() {
        $columns = array (
            //'sl'                    => 'SL', //Render a checkbox instead of text
            'name'                  => __('Name', $this->TD),
            'roll_no'               => __('Roll No', $this->TD),
            'registration_no'       => __('Reg No', $this->TD),
            'total_marks'           => __('Total Marks', $this->TD),
            'total_marks_obtained'  => __('Marks Obtained', $this->TD),
            'total_percentage'      => __('Percentage', $this->TD),
            'final_grade'           => __('Final Grade', $this->TD),
            'cgpa'                  => __('cgpa', $this->TD),
	        'final_result'          => __('Final Result', $this->TD),
        );
        return apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_view_results_fields',$columns);
    }
    
    function column_highest_marks($item) {
	    global $wpdb;
	    $query = $wpdb->prepare("SELECT highest_total_marks FROM `{$wpdb->rps_exam_record}` WHERE id=%d LIMIT 1", array($this->exam_record_id));
	    $res = $wpdb->get_row($query, ARRAY_A);
	    if ( is_array($res) && !empty($res) ) {
		    $ret = $res['highest_total_marks'];
	    }
	    else {
	    	$ret = 0.0;
	    }

        return sprintf('<strong>%0.2f</strong>', $ret);
        //return sprintf('<input type="checkbox" name="%s[]" value="%s" />',$this->_args['singular'],$item->id );
    }

	function column_sl($item) {
		//echo $item['id'];
		//echo $column_name;

		return sprintf('<strong>%d</strong>', $this->sl++);
		//return sprintf('<input type="checkbox" name="%s[]" value="%s" />',$this->_args['singular'],$item->id );
	}
    
    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
	            $name = !is_wp_error( $this->student_meta ) && array_key_exists( $item->student_id, $this->student_meta ) ? $this->student_meta[ $item->student_id ]['name'] : '';
	            $link = sprintf('<strong><a href="?page=%s&marks=%d&student_id=%d" class="edit">%s</a></strong>',$this->page,$this->exam_record_id, $item->student_id, $name);

	            $actions = array (
		            'edit'      => sprintf('<a href="?page=%s&marks=%d&student_id=%d" class="edit">%s</a>',$this->page,$_REQUEST['marks'], $item->student_id, $add_marks),
	            );

	            return sprintf('%s', $link);

            case 'roll_no':
                $roll_no = !is_wp_error( $this->student_meta ) && array_key_exists( $item->student_id, $this->student_meta ) ? $this->student_meta[ $item->student_id ]['roll_no'] : '';
                return esc_attr(sprintf( "%s", $roll_no ));

            case 'registration_no':
                $registration_no = !is_wp_error( $this->student_meta ) && array_key_exists( $item->student_id, $this->student_meta ) ? $this->student_meta[ $item->student_id ]['registration_no'] : '';
                return esc_attr(sprintf( "%s", $registration_no ));

	        default:
		        //check metadata
		        $this->get_meta_data($item->student_id);

		        if ( is_array($this->metadata) && array_key_exists($column_name, $this->metadata) ) {
		        	return esc_attr(sprintf( "%s", $this->metadata[ $column_name ] ));
		        }
		        else {
		        	return "";
		        }
        }
    }
    
    function prepare_items() {
	    global $wpdb;

        /* -- Preparing your query -- */
        $query = $wpdb->prepare("SELECT DISTINCT `student_id` FROM $wpdb->rps_exam_record_meta WHERE meta_key = %s AND `exam_record_id` = %d ORDER BY cast(meta_value as unsigned) DESC", array( 'total_marks_obtained', $this->exam_record_id ));
        
        
        /* -- Pagination parameters -- */
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 50;
        $paged = !empty($_GET["paged"]) ? stripslashes($_GET["paged"]) : '';
        if(empty($paged) || !RPS_Helper_Function::is_numeric($paged) || $paged<=0 ){ $paged=1; }
        $totalpages = ceil($totalitems/$perpage);
        if(!empty($paged) && !empty($perpage)){
                $offset=($paged-1)*$perpage;
            $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
        }
        
        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $hidden = array();
        $this->_column_headers = array($columns, $hidden);
        
	/* -- Register the pagination -- */
        $this->set_pagination_args( array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage,
        ) );
        //echo $query;
	/* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }
}

