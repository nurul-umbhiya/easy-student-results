<?php
if(!defined('WPINC')) {
    die();
}

class RPS_Admin_Menu_PremiumAddOns extends RPS_Admin_Menu_MenuAbstract {

    public static function getInstance($page)
    {
        if (self::$instance == null) {
            self::$instance = new self;
            //self::$instance->pagehook = $slug;
            self::$instance->page = $page;
        }

        return self::$instance;
    }


    public function mainDiv() {
        //check transient, if false call api
        $transient = $this->plugin_slug . '_premium_products';

        //delete_transient($transient);

        if ( false === ( $products = get_transient( $transient ) ) ) {
            $url = 'https://www.nurul.me/wp-json/nurul_woocommerce_api/v1/route/get_products';
            $remote_call = wp_remote_get($url);
            $products = json_decode($remote_call['body']);


            if ( is_array($products) ) {
                //save transient
                set_transient( $transient, $products, HOUR_IN_SECONDS );
            }
            //Array ( [0] => stdClass Object ( [title] => Test Paid Product 2 [excerpt] => this is test excerpt... [permalink] => http://projects.dev/result/product/test-paid-product-2/ [price] => 7 ) )
            //stdClass Object ( [code] => error-getting-data [message] => No Products found. [data] => stdClass Object ( [status] => 400 ) )
        }
        ?>
        <div class="wrap">
            <div class="rps_result">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Begin MailChimp Signup Form -->
                        <link href="//cdn-images.mailchimp.com/embedcode/horizontal-slim-10_7.css" rel="stylesheet" type="text/css">
                        <style type="text/css">
                            #mc_embed_signup{font:14px Helvetica,Arial,sans-serif; width:100%;
                                padding: 20px 0px; margin-bottom: 20px; margin-right: 40px;}
                            /* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
							   We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
                        </style>
                        <div id="mc_embed_signup">
                            <form action="//nurul.us16.list-manage.com/subscribe/post?u=60b8abdeb4c7e05bc3716cac7&amp;id=d878844af6" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                                <div id="mc_embed_signup_scroll">
                                    <label for="mce-EMAIL">Want to get notified about future updates of ESR? Subscribe to our mailing list below.</label>
                                    <input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
                                    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                                    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_60b8abdeb4c7e05bc3716cac7_892810db81" tabindex="-1" value=""></div>
                                    <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
                                </div>
                            </form>
                        </div>

                        <!--End mc_embed_signup-->
                    </div>
                    <div class="row">
                        <?php
                            if ( is_object($products) ) {
                                //error
                            ?>
                                <div class="col-md-4">
                                    <div class="card1">
                                        <div class="card-header">
                                            Easy Student Results
                                        </div>
                                        <div class="card-block">
                                            <h4 class="card-title"><?php echo $products->message; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                            else {
                                $i = 1;
                                $background = array(
                                    'bg-info', 'bg-primary', 'bg-secondary', 'bg-success',
	                                'bg-danger', 'bg-warning', 'bg-dark'
                                );
                                echo "<div class='row'>";
                                foreach ( $products as $product ) {
                                    if ( $i == 1 ) {
                                        echo '<div class="card-deck" style="margin: 0 20px 0 0 !important;">';
                                    }
                                    ?>

                                    <div class="card " style="padding: 0px !important;">
                                        <div class="card-header"><h6><?php echo str_replace('Easy Student Results : ', '', $product->title); ?></h6></div>
                                        <div class="card-body">
                                            <div class="card-text"><?php echo $product->excerpt; ?></div>
                                        </div>

                                        <div class="card-footer">
                                            <button type="button" href="<?php echo $product->permalink; ?>" class="btn btn-light" style="float: left"><?php _e('Price: $', $this->TD); echo $product->price; ?></button>
                                            <a href="<?php echo $product->permalink; ?>" class="btn btn-light" style="float: right" target="_blank"><?php _e('Buy Now', $this->TD); ?></a>
                                        </div>

                                    </div>
                                    <?php
                                    if ( $i % 3 == 0 ) {
                                        echo "</div>";
                                        $i = 0;
                                    }
                                    $i++;
                                }

                                if ( $i % 3 != 0 ) {
                                    echo "</div>";
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }



    /**
     * This function will load all required css, js and other function on wp hook
     */
    public function onLoadPage() {
        //die('called');
        //$this->loadCss();
        //$this->loadJs();
        add_action('admin_enqueue_scripts', array( $this, 'loadCss' ) );
        add_action('admin_enqueue_scripts', array( $this, 'loadJs' ) );
    }

    public function loadCss() {
        wp_enqueue_style('rps_bootstrap4');
    }

    public function loadJs() {
        wp_enqueue_script('rps_bootstrap4');
        //wp_enqueue_script('fluidvids');
    }

}