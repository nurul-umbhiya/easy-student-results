<?php
if ( !defined( 'WPINC' ) ) {
    die();
}

class RPS_Helper_Function {
    public function __construct() {
        //nothing here
    }

	function generate_random($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
	{
		$keyspace = str_shuffle($keyspace );
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[wp_rand(0, $max)];
		}
		return $str;
	}

	function random_number( $length ) {
        return $this->generate_random($length, '0123456789');
    }

    function random_string( $length ) {
	    return $this->generate_random($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    function random_alpha_num( $length ) {
        $this->generate_random($length);
    }



	/**
	 *  A method for inserting multiple rows into the specified table
	 *  Updated to include the ability to Update existing rows by primary key
	 *
	 *  Usage Example for insert:
	 *
	 *  $insert_arrays = array();
	 *  foreach($assets as $asset) {
	 *  $time = current_time( 'mysql' );
	 *  $insert_arrays[] = array(
	 *  'type' => "multiple_row_insert",
	 *  'status' => 1,
	 *  'name'=>$asset,
	 *  'added_date' => $time,
	 *  'last_update' => $time);
	 *
	 *  }
	 *
	 *
	 *  wp_insert_rows($insert_arrays, $wpdb->tablename);
	 *
	 *  Usage Example for update:
	 *
	 *  wp_insert_rows($insert_arrays, $wpdb->tablename, true, "primary_column");
	 *
	 *
	 * @param array $row_arrays
	 * @param string $wp_table_name
	 * @param boolean $update
	 * @param string $primary_key
	 * @return false|int
	 *
	 * @author	Ugur Mirza ZEYREK
	 * @contributor Travis Grenell
	 * @source http://stackoverflow.com/a/12374838/1194797
	 */
	public static function wp_insert_rows($row_arrays = array(), $wp_table_name, $update = false, $primary_key = null) {
		global $wpdb;
		$wp_table_name = esc_sql($wp_table_name);
		// Setup arrays for Actual Values, and Placeholders
		$values        = array();
		$place_holders = array();
		$query         = "";
		$query_columns = "";

		$query .= "INSERT INTO `{$wp_table_name}` (";
		foreach ($row_arrays as $count => $row_array) {
			foreach ($row_array as $key => $value) {
				if ($count == 0) {
					if ($query_columns) {
						$query_columns .= ", " . $key . "";
					} else {
						$query_columns .= "" . $key . "";
					}
				}

				$values[] = $value;

				$symbol = "%s";
				if (is_numeric($value)) {
					if (is_float($value)) {
						$symbol = "%f";
					} else {
						$symbol = "%d";
					}
				}
				if (isset($place_holders[$count])) {
					$place_holders[$count] .= ", '$symbol'";
				} else {
					$place_holders[$count] = "( '$symbol'";
				}
			}
			// mind closing the GAP
			$place_holders[$count] .= ")";
		}

		$query .= " $query_columns ) VALUES ";

		$query .= implode(', ', $place_holders);

		if ($update) {
			$update = " ON DUPLICATE KEY UPDATE $primary_key=VALUES( $primary_key ),";
			$cnt    = 0;
			foreach ($row_arrays[0] as $key => $value) {
				if ($cnt == 0) {
					$update .= "$key=VALUES($key)";
					$cnt = 1;
				} else {
					$update .= ", $key=VALUES($key)";
				}
			}
			$query .= $update;
		}

		$sql = $wpdb->prepare($query, $values);
		if ($wpdb->query($sql)) {
			return true;
		} else {
			return false;
		}
	}

	public static function javascript_back_button( $echo = true ) {
		ob_start(); ?>
        <button type="button" class="btn btn-primary" onclick="window.location='<?php echo get_permalink(); ?>'"><?php _e('Back', RPS_Result_Management::TD); ?></button>
		<?php
		$data = ob_get_clean();
		if ( $echo ) {
			echo $data;
		}
		else {
			return $data;
		}
	}

    public static function array_insert(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

	public static function insert_into_array( $array, $search_key, $insert_key, $insert_value, $insert_after_founded_key = true, $append_if_not_found = false ) {

		$new_array = array();

		foreach( $array as $key => $value ) {

			// INSERT BEFORE THE CURRENT KEY?
			// ONLY IF CURRENT KEY IS THE KEY WE ARE SEARCHING FOR, AND WE WANT TO INSERT BEFORE THAT FOUNDED KEY
			if( $key === $search_key && ! $insert_after_founded_key )
				$new_array[ $insert_key ] = $insert_value;

			// COPY THE CURRENT KEY/VALUE FROM OLD ARRAY TO A NEW ARRAY
			$new_array[ $key ] = $value;

			// INSERT AFTER THE CURRENT KEY?
			// ONLY IF CURRENT KEY IS THE KEY WE ARE SEARCHING FOR, AND WE WANT TO INSERT AFTER THAT FOUNDED KEY
			if( $key === $search_key && $insert_after_founded_key )
				$new_array[ $insert_key ] = $insert_value;

		}

		// APPEND IF KEY ISNT FOUNDED
		if( $append_if_not_found && count( $array ) == count( $new_array ) )
			$new_array[ $insert_key ] = $insert_value;

		return $new_array;

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
        //if ( ! defined( RPS_Result_Management::PLUGIN_SLUG . '_delete_transient' ) )
            //return;
        /*
        $transient_keys = get_option( 'rps_result_transient_keys', array() );

        if ( !empty( $transient_keys ) ) {
            foreach ( $transient_keys as $transient ) {
                delete_transient( $transient );
            }
            update_option( 'rps_result_transient_keys', array() );
        }
        */
        global $wpdb;
        $query = "delete FROM `{$wpdb->options}` where option_name like '_transient_rps_%' or option_name like '_transient_timeout_rps_%'";
        $wpdb->query($query);

    }

    public static function delete_taxonomy( $taxonomy ) {
        global $wpdb;

	    $wpdb->get_results( $wpdb->prepare( "DELETE t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s')", $taxonomy ) );
	    // Delete Taxonomy
	    $wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
    }

    public static function getCourseType( $course_type_id ) {
        $course_type = array(
            '1' => __('Theory',             RPS_Result_Management::TD),
            '2' => __('Lab',                RPS_Result_Management::TD),
            '3' => __('Project / Thesis',   RPS_Result_Management::TD),
        );

        $course_type_id = intval($course_type_id);

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

    public static function reserved_terms() {
	    $reserved_terms = array (
		    'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and',
		    'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage',
		    'customize_messenger_channel', 'customized', 'day',
		    'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name',
		    'nav_menu', 'nonce', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm',
		    'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type',
		    'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence',
		    'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id',
		    'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'theme', 'type', 'w', 'withcomments', 'withoutcomments', 'year',
	    );

	    return apply_filters(RPS_Result_Management::PLUGIN_SLUG . '_reserved_terms', $reserved_terms);
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

