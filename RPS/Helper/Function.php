<?php
if ( !defined( 'WPINC' ) ) {
    die();
}

class RPS_Helper_Function {
    public function __construct() {
        //nothing here
    }

    public static function is_numeric( $arg ) {
        if ( is_numeric( $arg ) && $arg ) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_option( $option, $section, $default = '' ) {

        $options = get_option( $section );

        if ( isset( $options[$option] ) ) {
            return $options[$option];
        }

        return $default;
    }

    public static function retriveMonth( $month ) {
        switch ( $month ) {
            case '01':
                return __('January', RPS_Result_Management::TD);
                break;

            case '02':
                return __('February', RPS_Result_Management::TD);
                break;

            case '03':
                return __('March', RPS_Result_Management::TD);
                break;

            case '04':
                return __('April', RPS_Result_Management::TD);
                break;

            case '05':
                return __('May', RPS_Result_Management::TD);
                break;

            case '06':
                return __('June', RPS_Result_Management::TD);
                break;

            case '07':
                return __('July', RPS_Result_Management::TD);
                break;

            case '08':
                return __('August', RPS_Result_Management::TD);
                break;

            case '09':
                return __('September', RPS_Result_Management::TD);
                break;

            case '10':
                return __('October', RPS_Result_Management::TD);
                break;

            case '11':
                return __('November', RPS_Result_Management::TD);
                break;

            case '12':
                return __('December', RPS_Result_Management::TD);
                break;

            default:
                return $month;
        }
    }

    public static function delete_transient() {
        if ( ! defined( RPS_Result_Management::PLUGIN_SLUG . '_delete_transient' ) )
            return;

        $transient_keys = get_option( 'rps_result_transient_keys', array() );

        if ( !empty( $transient_keys ) ) {
            foreach ( $transient_keys as $transient ) {
                delete_transient( $transient );
            }
            update_option( 'rps_result_transient_keys', array() );
        }

    }

    public static function getCourseType( $course_type_id ) {
        $course_type = array(
            '1' => __('Theory',             RPS_Result_Management::TD),
            '2' => __('Lab',                RPS_Result_Management::TD),
            '3' => __('Project / Thesis',   RPS_Result_Management::TD),
        );

        if ( array_key_exists($course_type_id, $course_type) ) {
            return $course_type[ $course_type_id ];
        } else {
            return __('N/A', RPS_Result_Management::TD);
        }
    }

    /**
     * Sanitize taxonomy names. Slug format (no spaces, lowercase).
     *
     * Doesn't use sanitize_title as this destroys utf chars.
     *
     * @access public
     * @param mixed $taxonomy
     * @return void
     */
    public function sanitize_taxonomy_name($taxonomy) {
        $taxonomy = strtolower(stripslashes(strip_tags($taxonomy)));
        $taxonomy = preg_replace('/&.+?;/', '', $taxonomy); // Kill entities
        $taxonomy = str_replace(array('.', '\'', '"'), '', $taxonomy); // Kill quotes and full stops.
        $taxonomy = str_replace(array(' ', '_'), '-', $taxonomy); // Replace spaces and underscores.

        return $taxonomy;
    }

    public static function javascript_redirect($location) {
        // redirect after header here can't use wp_redirect($location);
        ?>
          <script type="text/javascript">
          <!--
          window.location= '<?php echo $location; ?>';
          //-->
          </script>
        <?php
        exit;
    }

    public static function PR( $data = array() ) {
        echo "<pre>"; print_r($data); echo "</pre>";
    }
}

