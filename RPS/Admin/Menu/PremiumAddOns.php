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
                                echo "<div class='row'>";
                                foreach ( $products as $product ) {
                                    ?>
                                    <div class="<?php echo $i%3 == 0 ? 'col-md-4 col-lg4 col-sm-4' : 'col-md-3 col-lg3 col-sm-3'; ?> col-xs-11">
                                        <div class="card1">
                                            <h6 class="card-header bg-primary text-white"><?php echo str_replace('Easy Student Results : ', '', $product->title); ?></h6>

                                            <div class="card-block" style="min-height: 180px;">
                                                <p class="card-text text-justify"><?php echo $product->excerpt; ?></p>
                                            </div>

                                            <div class="card-footer text-white">
                                                <button type="button" href="<?php echo $product->permalink; ?>" class="btn btn-success" style="float: left"><?php _e('Price: $', $this->TD); echo $product->price; ?></button>
                                                <a href="<?php echo $product->permalink; ?>" class="btn btn-success" style="float: right" target="_blank"><?php _e('Buy Now', $this->TD); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    if ( $i % 3 == 0 ) {
                                        echo "</div><div class='row'>";
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