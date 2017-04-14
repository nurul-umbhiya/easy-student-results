<?php

if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_Dashboard extends RPS_Admin_Menu_MenuAbstract {

    public static function getInstance( $page ) {
        if( self::$instance == null ) {
            self::$instance = new self;
            self::$instance->pagehook = RPS_Admin_Menu_Main::getSlug('dashboard');
            self::$instance->page = $page;
        }
        
        return self::$instance;
    }
    
    /**
     * This function will hold all html related functions
     */

    public function mainDiv(){
        global $screen_layout_columns;
        ?>
        <div id="<?php echo RPS_Result_Management::PLUGIN_SLUG ?>_dashboard" class="wrap">
            <h2><i class="dashicons dashicons-align-left" style="line-height: 1.5em;"></i> Dashboard</h2>

            <form method="post" action="admin-post.php">
                <input type="hidden" name="action" value="save_<?php echo RPS_Result_Management::PLUGIN_SLUG ?>_dashboard_options" />

                <!-- Adding security through hidden referrer field -->
                <?php wp_nonce_field(RPS_Result_Management::PLUGIN_SLUG . '_dashboard'); ?>
                <!-- Security fields for meta box save processing -->
                <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

                <div id="dashboard-widgets-wrap">
                    <div id="dashboard-widgets" class="metabox-holder">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( $this->pagehook, 'normal', array() ); ?>
                        </div>

                        <div id="postbox-container-2" class="postbox-container">
                            <?php do_meta_boxes( $this->pagehook, 'side', array() ); ?>
                        </div>
                    </div>
                    <br class="clear"/>
                </div>
            </form>
        </div>

        <script type="text/javascript">

            //<![CDATA[
            jQuery( document ).ready( function( $ ) {
                // close postboxes that should be closed
                $( '.if-js-closed' ) .removeClass( 'if-js-closed' ).addClass( 'closed' );

                // postboxes setup
                postboxes.add_postbox_toggles( '<?php echo $this->pagehook; ?>' );
            });
            //]]>

        </script>
        <?php
    }


    public function tutorial() {

        ?>

            <div class="rps_result container">
                <div class="row">

                    <div class="col-xs-12 col-md-12 col-lg-12">


                        <!--Start-->
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#1" aria-expanded="true" aria-controls="faq-1">
                                        Step 1 <span class="pull-right"><i class="dashicons dashicons-arrow-down-alt2"></i></span>
                                    </a>
                                </h4>
                            </div>
                            <div id="1" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                                <div class="panel-body">
                                    <div class="embed-responsive embed-responsive-16by9">
                                        <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/Sff7zkAmVmE" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--END-->

                        <!--Start-->
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#2" aria-expanded="true" aria-controls="faq-1">
                                        Step 2 <span class="pull-right"><i class="dashicons dashicons-arrow-down-alt2"></i></span>
                                    </a>
                                </h4>
                            </div>
                            <div id="2" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                                <div class="panel-body">
                                    <div class="embed-responsive embed-responsive-16by9">
                                        <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/Sff7zkAmVmE" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--END-->


                    </div>


                </div>

            
            </div>

        <?php
    }

    public function shortcodes() {

    }
    
    /**
     * This function will load all required css, js and other function on wp hook
     */
    public function onLoadPage() {
        $this->loadMetaBoxs();
        add_action('admin_enqueue_scripts', array( $this, 'loadCss' ) );
        add_action('admin_enqueue_scripts', array( $this, 'loadJs' ) );
    }

    public function loadMetaBoxs() {
        add_meta_box('metabox-tutorial', 'Tutorial',
            array($this, 'tutorial'), $this->pagehook, 'normal', 'core', array()
        );

        add_meta_box('metabox-shortcode', 'Shortcodes',
            array($this, 'shortcodes'), $this->pagehook, 'side', 'core', array()
        );
    }
    
    public function loadCss() {
        wp_enqueue_style( 'rps_bootstrap' );
    }
    
    public function loadJs() {
        wp_enqueue_script( 'rps_bootstrap' );
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'wp-lists' );
        wp_enqueue_script( 'postbox' );

    }
}

