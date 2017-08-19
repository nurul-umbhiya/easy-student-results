<?php
if ( !defined( 'WPINC' ) ) {
    die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RPS_Admin_Menu_Result_MarksTable extends \WP_List_Table {
    private $TD, $helper, $page, $dbs;
    private $student_meta, $exams, $course, $student;
    private $exam_id, $department_id, $batch_id, $semester_id, $sl;

    function __construct($exam_id, $department_id, $batch_id, $semester_id = null ) {
        global $status, $page;

        $this->helper = new RPS_Helper_Function();
        $this->dbs = new RPS_Helper_DBS();
        $this->page = 'rps_result_results';
        $this->TD =  RPS_Result_Management::TD;

        $this->exam_id = $exam_id;
        $this->department_id = $department_id;
        $this->batch_id = $batch_id;
        $this->semester_id = $semester_id;
        $this->sl = 1;

        $this->student = new RPS_Helper_Student();

        $this->student_meta = $this->student->getStudentDetails($this->department_id, $this->batch_id, $this->semester_id, 'all');


        //Set parent defaults
        parent::__construct( array (
            'singular'  => 'exam_record',   //singular name of the listed records
            'plural'    => 'exam_records',  //plural name of the listed records
            'ajax'      => false            //does this table support ajax?
        ) );
        
        
    }
    
    function get_columns() {
        $columns = array (
            'sl'            => 'SL', //Render a checkbox instead of text
            'name'          => __('Student Name', $this->TD),
            'roll_no'          => __('Roll Number', $this->TD),
            'registration_no'        => __('Registration Number', $this->TD),
            'actions'       => __('Actions', $this->TD),
        );
        return $columns;
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
                $link = sprintf('<strong><a href="?page=%s&marks=%d&student_id=%d" class="edit">%s</a></strong>',$this->page,$_REQUEST['marks'], $item->student_id, $name);
                $add_marks = __( 'Add Marks', $this->TD );

                $actions = array (
                    'edit'      => sprintf('<a href="?page=%s&marks=%d&student_id=%d" class="edit">%s</a>',$this->page,$_REQUEST['marks'], $item->student_id, $add_marks),
                );

                return sprintf('%s', $link);

                return sprintf('%1$s %2$s',
                    /*$1%s*/ $link,
                    /*$2%s*/ $this->row_actions($actions)
                );

            case 'roll_no':
                $roll_no = !is_wp_error( $this->student_meta ) && array_key_exists( $item->student_id, $this->student_meta ) ? $this->student_meta[ $item->student_id ]['roll_no'] : '';
                return sprintf( "%s", $roll_no );

            case 'registration_no':
                $registration_no = !is_wp_error( $this->student_meta ) && array_key_exists( $item->student_id, $this->student_meta ) ? $this->student_meta[ $item->student_id ]['registration_no'] : '';

                return sprintf( "%s", $registration_no );

            case 'actions':
                $name = __( 'Add Marks', $this->TD );
                $link = sprintf('<strong><a href="?page=%s&marks=%d&student_id=%d" class="button button-primary">%s</a></strong>',$this->page,$_REQUEST['marks'], $item->student_id, $name);

                return sprintf('%s',$link);

            case 'display':
                if( $item->$column_name === '1' )
                    $str = __( "Show", $this->TD );
                elseif( $item->$column_name === '2' )
                    $str = __( "Hide", $this->TD );
                return sprintf('%s',$str);

            case 'active':
                if($item->$column_name === '1')
                    $str = __( "Active", $this->TD );
                elseif($item->$column_name === '2') 
                    $str = __( "Inactive", $this->TD );
                return sprintf('%s',$str);

            case 'updated':
                return sprintf('%s',$item->$column_name);
                
        }
    } 
    
    
    function get_sortable_columns() {
        $sortable_columns = array(
            //'grade'     => array('grade',false),     //true means it's already sorted
        );
        return $sortable_columns;
    }
    
    function get_bulk_actions() {
        $actions = array(
            //'delete'    => 'Delete'
        );
        return $actions;
    }
    
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() ) {
            echo('Items deleted (or they would be if we had items to delete)!');
        }
        
    }
    
    function extra_tablenav( $which ) {
        if ( $which == "top" ) {

        }
        if ( $which == "bottom" ) {
            //The code that goes after the table is there
            //echo"Hi, I'm after the table";
            echo '<a href="' . esc_url_raw( add_query_arg(array('page' => $this->page),  admin_url('admin.php?')) ) .'" class="button">' . __('Back', $this->TD) . '</a>';
        }
    }
    
    function prepare_items() {
	    global $wpdb;
        $this->process_bulk_action();

	    //count total value
        //$totalitems = $wpdb->get_var('SELECT count(*) as cnt FROM `' . $wpdb->batch .'`');
        
        
        /* -- Preparing your query -- */
        $query = $wpdb->prepare("SELECT DISTINCT `student_id` FROM $wpdb->rps_marks WHERE `exam_record_id` = %d", array( $this->exam_id ));
        
        
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
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
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

