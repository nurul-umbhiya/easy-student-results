<?php
if ( !defined( 'WPINC' ) ) {
    die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RPS_Admin_Menu_Result_MainTable extends \WP_List_Table {
    private $TD, $helper, $page, $dbs, $sl;
    private $exam_table_data, $exams, $course;

    function __construct() {
        global $status, $page;

        $this->helper = new RPS_Helper_Function();
        $this->dbs = new RPS_Helper_DBS();
        $this->exams = new RPS_Helper_Result();
        $this->page = 'rps_result_results';
        $this->TD =  RPS_Result_Management::TD;
        $this->sl = 1;

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
            'exam_id'       => __('Exam', $this->TD),
            'department_id' => __('Department/Class', $this->TD),
            'batch_id'      => __('Batch/Year', $this->TD),
            'semester_id'   => __('Semester/Section', $this->TD),
            'display'       => __('Display Frontend', $this->TD),
            //'active'        => __('Active', $this->TD),
            //'updated'       => __('Updated', $this->TD),
            'actions'       => __('Actions', $this->TD),
        );
        return apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_result_main_table_cloumns', $columns);
    }
    
    function column_sl($item) {
        //echo $item['id'];
        //echo $column_name;
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->rps_exam} WHERE id= %d", array($item->exam_id));
        $this->exam_table_data = $wpdb->get_row($query,ARRAY_A);

        return sprintf('<strong>%d</strong>', $this->sl++ );
    }
    
    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'exam_id':
                $exam_name = sprintf('<strong><a href="?page=%s&edit=%s" class="edit">%s</a></strong>',$this->page,$item->id, $this->exam_table_data['name'] . ' - ' . $this->exam_table_data['exam_year']);
                //$url = esc_url_raw( add_query_arg(array('page' => $this->page, 'marks' => $item->id),  admin_url('admin.php?')) );
                $actions = array (
                    'edit'      => sprintf(__('<a href="?page=%s&edit=%s" class="edit">Edit</a>', $this->TD),$this->page,$item->id),
                    //'add_marks' => sprintf(__('<a href="?page=%s&marks=%s" class="edit">Add Marks</a>', $this->TD),$this->page,$item->id),
	                'view_results' => sprintf(__('<a href="?page=%s&view_results=%s" class="edit">View Results</a>', $this->TD),$this->page,$item->id),
                );

                $actions = apply_filters(RPS_Result_Management::PLUGIN_SLUG .'_exam_record_row_actions', $actions, $this->page, $item->id);

                //return sprintf( '%s', $exam_name );

                return sprintf('%1$s %2$s',
                    /*$1%s*/ $exam_name,
                    /*$2%s*/ $this->row_actions($actions)
                );

            case 'department_id':
                $department_id = $item->department_id;
                $info = $this->dbs->getDepartmentInfo($department_id);
                $dept_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];
                return sprintf( "%s", $dept_name );

            case 'batch_id':
                $batch_id       = $item->batch_id;
                $department_id  = $item->department_id;

                $info = $this->dbs->getBatchInfo( $department_id, $batch_id );
                $batch_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];

                return sprintf( "%s", $batch_name );

            case 'semester_id':
                $department_id  = $item->department_id;
                $semester_id    = $item->semester_id;

                if ( RPS_Helper_Function::is_numeric($department_id) && RPS_Helper_Function::is_numeric($semester_id) ) {
                    $info           = $this->dbs->getSemesterInfo($department_id, $semester_id);
                    $semester_name = is_wp_error( $info ) ? $info->get_error_message() : $info['name'];
                } else {
                    $semester_name = __('N/A', $this->TD);
                }

                return sprintf('%s',$semester_name);

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

            case 'actions':
                $name = __( 'Add Marks', $this->TD );
	            $name2 = __( 'View Results', $this->TD );
                $delete = __( 'Delete', $this->TD );
                $exam_name = $this->exam_table_data['name'] . ' - ' . $this->exam_table_data['exam_year'];
                $link = sprintf('<strong><a href="?page=%s&marks=%d" class="button button-primary action_button">%s</a></strong>',$this->page,$item->id, $name);
	            $link .= sprintf('<strong><a href="?page=%s&view_results=%d" class="button button-primary action_button">%s</a></strong>',$this->page,$item->id, $name2);


                $link2 = wp_nonce_url ( admin_url( sprintf( 'admin.php?page=%s&delete=%d', $this->page,$item->id ) ), 'delete_result_' . $item->id, 'delete_result' );
                $link .= sprintf('<br><strong><a href="%s" class="button button-default action_button delete_marks exam_record_id_%d" data-exam_name="%s" data-exam_id="%d">%s</a></strong>',$link2, $item->id, $exam_name, $item->id, $delete);


                return sprintf( '%s', apply_filters(RPS_Result_Management::PLUGIN_SLUG .'_exam_record_list_actions', $link, $this->page, $item->id));

            default:
                do_action(RPS_Result_Management::PLUGIN_SLUG . '_result_main_table_data', $column_name, $item);
        }
    }
    
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'id'     => array('id',false),     //true means it's already sorted
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
            //get all exam ids
            $exams = $this->exams->getExamID();
            $exam_id =  isset($_GET['exam_id']) ? stripslashes(trim($_GET['exam_id'])) :'';
            if ( !empty($exams) ) {
            ?>
                <select name="exam_id" id="exam_id" class="postform">
                    <option value=""><?php _e('Select Exam'); ?></option>
                    <?php
                    foreach ($exams as $row):
                        echo "<option value='{$row['id']}' ".selected($exam_id, $row['id'], false)." >{$row['name']} - {$row['exam_year']}</option>";
                    endforeach;
                    ?>
                </select>
            <?php
            }

            $departments = $this->dbs->getDepartmentList();
            if( !is_wp_error($departments) && is_array($departments) && !empty($departments) ) {
                $dept_ids = array_keys($departments);
                //$semesters = array();
                $semesters = array();
                $batches = array();

                if ( !empty( $dept_ids ) ) :
                    foreach ( $dept_ids as $id ):
                        //get batch
                        $batch_data = $this->dbs->getAllBatchListByDepartment( $id );
                        $batches[$id] = is_wp_error($batch_data) ? array() : $batch_data;
                        //get semester
                        $sem = $this->dbs->getSemesterList($id);
                        $semesters[$id] = is_wp_error($sem) ? array() : $sem;
                    endforeach;
                endif;

                $department_id =  isset($_GET['department_id']) ? stripslashes(trim($_GET['department_id'])) :'';
                //add department filter

                ?>
                <select name="department_id" id="department_id" class="postform">
                    <option value=""><?php _e('Select Department'); ?></option>
                    <?php
                    foreach ($departments as $id => $name):
                        echo "<option value='{$id}' ".selected($department_id, $id, false)." >{$name}</option>";
                    endforeach;
                    ?>
                </select>
                <?php

                //add batch filter
                $batch_id = isset($_GET['batch_id']) ? stripslashes(trim($_GET['batch_id'])) :'';
                ?>
                <select name="batch_id" id="batch_id" class="postform">
                    <option value=""><?php _e('Select Batch'); ?></option>
                    <?php
                    if($department_id != '' && RPS_Helper_Function::is_numeric($department_id)) {
                        $batch = isset($batches[$department_id]) ? $batches[$department_id] : '';
                        if(is_array($batch) && !empty($batch)) {
                            foreach ($batch as $key => $name):
                                echo "<option value='{$key}' ".selected($batch_id, $key, false).">{$name}</option>";
                            endforeach;
                        }
                    }
                    ?>
                </select>

                <?php
                //add semester filter
                $semester_id = isset($_GET['semester_id']) ? stripslashes(trim($_GET['semester_id'])) :'';
                if(!empty($semesters)): ?>
                    <select name="semester_id" id="semester_id" class="postform">
                        <option value=""><?php _e('Select Semester'); ?></option>
                        <?php
                        if( $department_id != '' && RPS_Helper_Function::is_numeric($department_id) ) {
                            $semester = isset($semesters[$department_id]) ? $semesters[$department_id] : array();
                            if( is_array($semester) && !empty($semester) ) {
                                foreach ($semester as $id => $name):
                                    echo "<option value='{$id}' ".selected($semester_id, $id, false).">{$name}</option>";
                                endforeach;
                            }
                        }
                        ?>
                    </select>

                <?php endif; ?>

                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">

                <script type="text/javascript">
                    jQuery(function($) {
                        var semesters = <?php echo json_encode($semesters) ?>;
                        var batches = <?php echo json_encode($batches) ?>;

                        $('#department_id').on('change',function() {
                            var id = $(this).val();

                            // Popupate semester dropdown box
                            var i = semesters[id];
                            //console.log(i);
                            $('#semester_id option').remove();


                            $('#semester_id')
                                .append($("<option></option>")
                                    .attr("value",'')
                                    .text('Select Semester'));

                            if(i !== null){
                                $.each(i, function(key, value) {
                                    //populate semester dropdown box
                                    $('#semester_id')
                                        .append($("<option></option>")
                                            .attr("value",key)
                                            .text(value));

                                });
                            }

                            //populate batch dropdown box
                            var j = batches[id];
                            //console.log(j);
                            $('#batch_id option').remove();
                            $( '#batch_id' )
                                .append($("<option></option>")
                                    .attr("value",'')
                                    .text('Select Batch'));

                            // Add option to the select box from list
                            if ( j !== null ) {
                                $.each( j, function( key, value ) {
                                    $( '#batch_id' )
                                        .append($("<option></option>")
                                            .attr("value",key)
                                            .text(value));
                                });
                            }


                        });
                    });
                </script>

                <style>
                    .tablenav .actions {
                        padding: 0px !important;
                    }
                </style>

                <?php
            }
        }
        if ( $which == "bottom" ) {
            //The code that goes after the table is there
            //echo"Hi, I'm after the table";
        }
    }
    
    function prepare_items() {
	    global $wpdb;
        $this->process_bulk_action();

	    //count total value
        //$totalitems = $wpdb->get_var('SELECT count(*) as cnt FROM `' . $wpdb->batch .'`');
        
        
        /* -- Preparing your query -- */
        $query = "SELECT * FROM $wpdb->rps_exam_record ";


        /* -- Filter Parameter -- */
        $exam_id =  isset($_GET['exam_id']) ? stripslashes(trim($_GET['exam_id'])) :'';
        $department_id =  isset($_GET['department_id']) ? stripslashes(trim($_GET['department_id'])) :'';
        $batch_id = isset($_GET['batch_id']) ? stripslashes(trim($_GET['batch_id'])) :'';
        $semester_id = isset($_GET['semester_id']) ? stripslashes(trim($_GET['semester_id'])) :'';
        $where = array();

        if ( RPS_Helper_Function::is_numeric( $exam_id ) ) {
            $where[] = 'exam_id = ' . intval($exam_id);
        }

        if ( RPS_Helper_Function::is_numeric( $department_id ) ) {
            $where[] = 'department_id = ' . intval($department_id);
        }

        if ( RPS_Helper_Function::is_numeric( $batch_id ) ) {
            $where[] = 'batch_id = ' . intval($batch_id);
        }

        if ( RPS_Helper_Function::is_numeric( $semester_id ) ) {
            $where[] = 'semester_id = ' . intval($semester_id);
        }

        if ( !empty($where) ) {
            $query .= " WHERE ";
            $query .= implode(' AND ', $where);
        }

	    $query .= ' ORDER BY id DESC';


        /* -- Pagination parameters -- */

        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 20;
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

