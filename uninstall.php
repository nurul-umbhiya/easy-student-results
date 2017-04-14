<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

//add required files
require_once dirname(__FILE__) . '/rps_result.php';
require_once dirname(__FILE__) . '/RPS/Helper/Function.php';
require_once dirname(__FILE__) . '/RPS/Uninstall.php';



if ( !is_multisite() ) {
    //check options
    $options = get_option( RPS_Result_Management::PLUGIN_SLUG . '_basics', array() );
    if ( isset($options['delete_data']) && $options['delete_data'] == 'on' ) {
        RPS_Uninstall::getInstance();
    }

} else {
    global $wpdb;
    // Get all blogs in the network and activate plugin on each one
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );

        RPS_Uninstall::getInstance();

        restore_current_blog();
    }
}