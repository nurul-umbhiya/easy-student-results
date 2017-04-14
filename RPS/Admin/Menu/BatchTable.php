<?php
if(!defined('WPINC')) {
    die();
}

if(!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RPS_Admin_Menu_BatchTable extends \WP_List_Table {
    private $dbs,$util,$page,$TD,$sl;
    function __construct() {
        global $status, $page;
        $this->dbs = new RPS_Helper_DBS();  
        $this->util = new RPS_Helper_Function();
        $this->page = RPS_Admin_Menu_Main::getPage('batch');
        $this->TD =  RPS_Result_Management::TD;
        $this->sl = 1;
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'batch',     //singular name of the listed records
            'plural'    => 'batches',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
        
    }
    
    function get_columns() {
        $columns = array (
            'sl'            => 'SL', //Render a checkbox instead of text
            'title'         => 'Name',
            'department'    => 'Department',
            //'semester'      => 'Current Semester',
            'session'       => 'Session',
            'active'        => 'Active',
        );
        return $columns;
    }
    
    function column_sl($item) {
        //echo $item['id'];
        //echo $column_name;
        return sprintf('<strong>%d</strong>',$this->sl++ );
    }
    
    function column_default($item, $column_name){
        switch($column_name){
            case 'title':
                //Build row actions
                $actions = array(
                    'edit'      => sprintf(__('<a href="?page=%s&edit=%s" class="edit">Edit</a>', $this->TD), $this->page, $item->id),
                    //'delete'    => sprintf(__('<a href="?page=%s&delete=%s" class="delete">Delete</a>', $this->TD), $this->page, $item->id),
                );

                $edit_link = esc_url_raw( add_query_arg( array('page' => $this->page, 'edit' => $item->id), 'admin.php' ) );
                $edit_text = "<a href='{$edit_link}'>{$item->name}</a>";

                //Return the title contents
                return sprintf('%1$s %2$s',
                    /*$1%s*/ $edit_text,
                    /*$2%s*/ $this->row_actions($actions)
                );
                
            case 'department':
                $dpt = $this->dbs->getDepartmentInfo($item->department_id);
                if( !is_wp_error($dpt) ) {
                    return sprintf("%s",$dpt['name']);
                } else {
                    return "error message here";
                }
                
            case 'semester':
                $sem = $this->dbs->getSemesterInfo($item->department_id, $item->current_sem);
                if( !is_wp_error($sem) ) {
                    return sprintf("%s",$sem['name']);
                } else {
                    return "error message here";
                }
                
            case 'session':
                return sprintf("%s - %d", RPS_Helper_Function::retriveMonth($item->session_month), $item->session_year);
                
            case 'active':
                $ret = "";
                if($item->active === "1"){
                    $ret = "Active";
                }
                elseif($item->active==="2"){
                    $ret = "In-active";
                }
                return sprintf('%s',$ret);
        }
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'department'     => array('department_id',false),     //true means it's already sorted
            'semester'    => array('current_sem',false),
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
        if( 'delete'===$this->current_action() ) {
            //echo('Items deleted (or they would be if we had items to delete)!');
        }
        
    }
    
    function extra_tablenav( $which ) {
	if ( $which == "top" ) {
            //add department filter
            
            $departments = $this->dbs->getDepartmentList();
            if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ) {
                $department_id =  isset($_GET['department_id']) ? stripslashes(trim($_GET['department_id'])) :'';
            ?>
                <select name="department_id" id="department_id" class="postform">
                    <option value=""><?php _e('Select Department', $this->TD); ?></option>
                <?php
                  foreach ($departments as $id => $name):
                    echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                  endforeach;

                ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
            <?php  }  ?>

        <style>
            .tablenav .actions {
                padding: 0px !important;
            }
        </style>
            
        <?php
	}
	if ( $which == "bottom" ) {
		//The code that goes after the table is there
		//echo"Hi, I'm after the table";
	}
    }
    
    function prepare_items() {
	global $wpdb;
        $this->process_bulk_action();

        /* -- Preparing your query -- */
        $query = "SELECT * FROM $wpdb->rps_batch";

        /* -- Search Parameters -- */
        $department_id = (isset($_REQUEST['department_id']) && RPS_Helper_Function::is_numeric($_REQUEST['department_id'])) ? (int) $_REQUEST['department_id'] : '';
        if($department_id != '') {
            $query .= sprintf(" WHERE department_id=%d", $department_id);
        }
        
        /* -- Order by Parameters --*/
        $orderby = ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] != '' ) ? esc_sql($_REQUEST['orderby']) : ''; //If no sort, default to title
        $order = ( isset($_REQUEST['order']) && $_REQUEST['order'] != '' ) ? esc_sql($_REQUEST['order']) : 'desc'; //If no order, default to desc
        
        if($orderby != '') {
            $query .= sprintf(' ORDER BY %s %s' , $orderby, $order);
        }
        
        
        /* -- Pagination parameters -- */
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 10;
        $paged = !empty($_GET["paged"]) ? (int) $_GET["paged"] : '';
        if(empty($paged) || !RPS_Helper_Function::is_numeric($paged) || $paged<=0 ){ $paged=1; }
        $totalpages = ceil($totalitems/$perpage);
        if(!empty($paged) && !empty($perpage)) {
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
