<?php
if(!defined('WPINC')) {
	die();
}

class RPS_Admin_Menu_MigrateData extends RPS_Admin_Menu_MenuAbstract {

	public static function getInstance($slug) {
		if( self::$instance == null ) {

			self::$instance = new self;

			self::$instance->pagehook = $slug;
			self::$instance->page = 'rps_result_migrate_data';

			self::$instance->student = new RPS_Helper_Student();
			self::$instance->result = new RPS_Helper_Result();
			self::$instance->course = new RPS_Helper_Course();
		}

		return self::$instance;
	}

	/**
	 * This function will hold all html related functions
	 */
	public function mainDiv() {
		if( isset($_REQUEST['page']) && $_REQUEST['page'] === $this->page ) {
			$title = __('Migrate Data', $this->TD);
		    ?>
            <div class="wrap">
                <h2><?php echo $title; ?></h2>
                <a href="#TB_inline?width=150&height=250&inlineId=migrate_data_modal" class="button button-primary thickbox" id="esr_import_batches_btn" title="<?php echo $title; ?>"><?php _e('Start Migration', 'aunty-api'); ?></a>

                <div id="migrate_data_modal" style="display:none;">
                    <div class="rps_result" style="padding: 20px 15px;">
                        <button class="button button-primary" id="start_migration">Start Migration</button>
                        <div id="migrate_data_modal_notices" style="padding-top: 15px;"></div>
                    </div>
                </div>

                <script type="text/template" id="progressbar_html">
                    <div><span class="total_percent" id="total_percent">0</span>% Complete</div>
                    <div class="progress">
                        <div class="progress progress-striped active">
                            <div class="progress-bar progress-bar-success progress_success" id="progress_success"></div>
                        </div>
                    </div>
                </script>

                <script type="text/javascript">
                    jQuery(function($) {
                        $('body').on('click', '#start_migration', function(e) {
                            e.preventDefault();

                            $('#migrate_data_modal_notices').show('slow').html('<div class="alert alert-info" role="alert"><p>Collecting Data. Please wait...</p></div>');

                            var th = $(this);

                            th.hide();

                            $.ajax({
                                dataType : "json",
                                type : 'POST',
                                url : '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                                data: {nonce: '<?php echo wp_create_nonce( $this->plugin_slug . '_collect_course_data_' . get_current_user_id()) ?>', action: '<?php echo $this->plugin_slug . '_collect_course_data'; ?>'},
                                success: function(response) {
                                    console.log(response);
                                    if ( response.type && response.type == 'success' ) {
                                        var str = '<div class="alert alert-info" role="alert">Migrating Data... Please don\'t close this window until the task is completed..</div>';
                                        $('#migrate_data_modal_notices').html( str + $('#progressbar_html').html());
                                        if ( response.count == 0 ) {
                                            var success_msg = '<div class="alert alert-success alert-dismissible" role="alert">' +
                                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                                'All data migrated successfully.</div>';
                                            $('#migrate_data_modal_notices').html(success_msg);
                                            $('#start_migration').show();
                                        }
                                        else {
                                            migrate_course_data(0, response.count, response.courses);
                                        }
                                    }

                                    if ( response.errors ) {
                                        var str = '';
                                        $.each(response.errors, function(index, error) {
                                            str += '<p>' + error + '</p>';
                                        });
                                        $('#migrate_data_modal_notices').show('slow').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                                        th.show();
                                    }

                                },
                                complete: function(jqXHR, textStatus) {

                                },
                                error: function() {
                                    $('#migrate_data_modal_notices').show('slow').html('<div class="alert alert-danger" role="alert">File upload error!</div>');
                                }
                            });
                        });

                        function migrate_course_data(index, total_count, courses) {
                            if ( typeof total_count !== 'undefined' && typeof index !== 'undefined') {
                                $.ajax({
                                    dataType : "json",
                                    type : 'POST',
                                    url : '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                                    data: {action: '<?php echo $this->plugin_slug . '_migrate_course_data'; ?>', nonce: '<?php echo wp_create_nonce($this->plugin_slug . '_migrate_course_data_' . get_current_user_id()) ?>', course_id: courses[index]},
                                    success: function(response) {
                                        //console.log(response);
                                        if ( response.errors ) {
                                            var str = '';
                                            $.each(response.errors, function(index, error) {
                                                str += '<p>' + error + '</p>';
                                            });
                                            $('#migrate_data_modal_notices').show('slow').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                                            $('#start_migration').show();
                                        }
                                        else {
                                            index++;
                                            var total = (index/total_count)*100;
                                            $("#total_percent").html(Math.floor(total));
                                            $("#progress_success").css("width", total + "%");
                                            if (index != total_count) {
                                                migrate_course_data(index, total_count, courses);
                                            } else {
                                                if ( index == total_count ) {
                                                    //show success message
                                                    var success_msg = '<div class="alert alert-success alert-dismissible" role="alert">' +
                                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                                        'All data migrated successfully.</div>';
                                                    $('#migrate_data_modal_notices').html(success_msg);
                                                    $('#start_migration').show();
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    })
                </script>

            </div>
		    <?php
		}
	}

	/**
	 * This function will load all required css, js and other function on wp hook
	 */
	public function onLoadPage() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles' ) );
	}

	public function load_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('rps_bootstrap' );
		wp_enqueue_script('thickbox');
	}

	public function load_styles() {
		wp_enqueue_style('thickbox');
		wp_enqueue_style('rps_bootstrap' );
	}

	public function wpFooter() {

        ?>
        <style type="text/css" rel="stylesheet">
            .wp-list-table th#sl {
                width: 50px;
            }
            a.action_button {
            	margin-bottom: 5px !important;
            }
            @media (min-width: 768px) {
            	.modal-dialog {
            		margin: 5% auto !important;
            	}
            }

            .modal-dialog {
            	margin: 5% auto !important;
            }

        </style>

        <script type="application/javascript">
        	jQuery(function($){
        		var exam_id = 0;

        		$('#result_table').on('change', '#department_id', function() {
                    change_batch_semester();
                    change_student_course();
                });

        		$('#result_table').on('change', '#semester_id, #batch_id', function() {
                    change_student_course();
                });

                function change_batch_semester() {

                    var id = $('#department_id').val();

                    // Popupate semester dropdown box
                    var i = typeof semesters != 'undefined' ? semesters[id] : null;
                    //console.log(i);
                    $('#semester_id option').remove();

                    $('#semester_id')
                        .append($("<option></option>")
                        .attr("value",'')
                        .text('Select Term'));


                    if (i !== null) {
                        $.each(i, function (key, value) {
                            //populate semester dropdown box
                            $('#semester_id')
                                .append($("<option></option>")
                                    .attr("value", key)
                                    .text(value));

                        });
                    }

                    //populate batch dropdown box
                    var j = typeof batches != 'undefined' ? batches[id] : null;
                    //console.log(j);
                    $('#batch_id option').remove();

                    // Add option to the select box from list
                    if (j !== null) {
                        $.each(j, function (key, value) {
                            $('#batch_id')
                                .append($("<option></option>")
                                    .attr("value", key)
                                    .text(value));
                        });
                    }
                }
                change_batch_semester();

                function change_student_course() {
                    var department_id = $('#department_id').val();
                    var batch_id = $('#batch_id').val();
                    var semester_id = $('#semester_id').val();

                    //call ajax
                    jQuery.ajax({
                        type : "post",
                        dataType : "json",
                        url : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data : {action: "rps_result_load_courses", department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: '<?php echo wp_create_nonce('rps_result_load_courses');  ?>'},
                        success: function(response) {
                            if(response.type == "success") {

                                if ( response.data && response.data != '' ) {
                                    $('#submit_btn').prop('disabled', false);
                                    $('#subject_div').html( response.data );
                                }
                                else {
                                    $('#submit_btn').prop('disabled', true);
                                    $('#subject_div').html( 'No subject is selected.' );
                                }
                            }
                            else {
                                alert("Invalid Request.")
                            }
                        }
                    })

                }
                change_student_course();

				$('body').on('click', '#submit_btn', function(e) {
				    e.preventDefault();
					var th = $(this);


					var exam_id = $('#exam_id').val();
					var department_id = $('#department_id').val();
                    var batch_id = $('#batch_id').val();
                    var semester_id = $('#semester_id').val();

                    var subjects = [];
                    $.each($('input[name="course_id[]"]:checked'), function(){
                        subjects.push($(this).val());
                    });

                    $('#addNewResultModal').modal('show');
                    $('#addNewResultModalBody').html('<p>Checking for existing result. Please wait...</p>');

                    if ( subjects.length == 0 ) {
                        $('#addNewResultModalBody').html('<p>Please select some subjects to continue.</p>');
                        return false;
                    }

					//call ajax
                    jQuery.ajax({
                        type : "post",
                        dataType : "json",
                        url : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        data : {action: "rps_result_check_existing_results", exam_id: exam_id, department_id : department_id, batch_id:batch_id, semester_id:semester_id, secure: '<?php echo wp_create_nonce('rps_result_check_existing_results');  ?>'},
                        success: function(response) {
                            if(response.type == "success") {
                                if ( response.students.length > 0 && response.exam_record_id > 0 ) {
                                    var str = '<div class="alert alert-info" role="alert">Adding Result... Please don\'t close this window until the task is completed..</div>';
                                    $('#addNewResultModalBody').html( str + $('#progressbar_html').html());
                                    add_student_results(0, response.students, response.exam_record_id, subjects);
                                }
                                else {
                                    $('#addNewResultModalBody').html('<p>No Student Found or Database Error. Please check your input</p>');
                                }
                            }
                            else if( response.errors ) {
                                var str = '';
                                $.each(response.errors, function(index, error) {
                                    str += '<p>' + error + '</p>';
                                });
                                $('#addNewResultModalBody').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                            }
                            else {
                                alert("Invalid Request.")
                            }
                        }
                    });
					return false;
				});

				function add_student_results(index, students, exam_record_id, subjects) {
                            if ( typeof exam_record_id !== 'undefined' && typeof index !== 'undefined') {
                                $.ajax({
                                    dataType : "json",
                                    type : 'POST',
                                    url : '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                                    data: {action: 'rps_result_add_student_results', nonce: '<?php echo wp_create_nonce('rps_result_add_student_results') ?>', exam_record_id: exam_record_id, student_id: students[index], subjects: subjects},
                                    success: function(response) {
                                        //console.log(response);
                                        if ( response.errors ) {
                                            var str = '';
                                            $.each(response.errors, function(index, error) {
                                                str += '<p>' + error + '</p>';
                                            });
                                            $('#addNewResultModalBody').html('<div class="alert alert-danger" role="alert">'+str+'</div>');
                                        }
                                        else {
                                            index++;
                                            var total = (index/students.length)*100;
                                            $("#total_percent").html(Math.floor(total));
                                            $("#progress_success").css("width", total + "%");
                                            if (index != students.length) {
                                                add_student_results(index, students, exam_record_id, subjects);
                                            } else {
                                                if ( index == students.length ) {
                                                    //show success message
                                                    var success_msg = '<div class="alert alert-success alert-dismissible" role="alert">' +
                                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                                        'Result Added successfully.</div>';
                                                    $('#addNewResultModalBody').html(success_msg);
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
        	});
		</script>
		<div class="rps_result">
			<div class="modal fade" tabindex="-1" role="dialog" id="addNewResultModal">
			  <div class="modal-dialog" role="document">
				<div class="modal-content">
				  <div class="modal-header">
					<h4 class="modal-title">Add New Result</h4>
				  </div>
				  <div class="modal-body" id="addNewResultModalBody"></div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				  </div>
				</div><!-- /.modal-content -->
			  </div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
		</div>

        <script type="text/template" id="progressbar_html">
            <div><span class="total_percent" id="total_percent">0</span>% Complete</div>
            <div class="progress">
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success progress_success" id="progress_success"></div>
                </div>
            </div>
        </script>
        <?php
    }

}
