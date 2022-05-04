<?php
/**
 * WP Courseware Utility Functions
 *
 * @since 4.3.0
 * @subpackage Functions
 * @package WPCW
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Assets File Helper.
 *
 * @since 4.3.0
 *
 * @param string $file The file name.
 * @param string $path The file path.
 *
 * @return string The asset file url.
 */
function wpcw_asset_file( $file, $path ) {
	$asset_url = trailingslashit( WPCW_ASSETS_URL . $path ) . $file;

	$mix_file     = "/{$path}/{$file}";
	$mix_manifest = WPCW_ASSETS_PATH . 'mix-manifest.json';

	if ( file_exists( $mix_manifest ) ) {
		$mix_assets = json_decode( file_get_contents( $mix_manifest ), true );

		if ( isset( $mix_assets[ $mix_file ] ) ) {
			$asset_url = untrailingslashit( WPCW_ASSETS_URL ) . $mix_assets[ $mix_file ];
		}
	}

	return esc_url( $asset_url );
}

/**
 * Image File Helper.
 *
 * @since 4.3.0
 *
 * @param string $file The file name.
 *
 * @return string The file url.
 */
function wpcw_image_file( $file = '' ) {
	return ( $file ) ? wpcw_asset_file( $file, 'img' ) : WPCW_IMG_URL;
}

/**
 * Javascript File Helper.
 *
 * @since 4.3.0
 *
 * @param string $file The file name.
 *
 * @return string The file url.
 */
function wpcw_js_file( $file = '' ) {
	return ( $file ) ? wpcw_asset_file( $file, 'js' ) : WPCW_JS_URL;
}

/**
 * CSS File Helper.
 *
 * @since 4.3.0
 *
 * @param string $file The file name.
 *
 * @return string The file url.
 */
function wpcw_css_file( $file = '' ) {
	return ( $file ) ? wpcw_asset_file( $file, 'css' ) : WPCW_CSS_URL;
}

/**
 * Get $_POST var.
 *
 * @since 4.3.0
 *
 * @param string $name The name of the variable.
 *
 * @return false|mixed False if var does not exist.
 */
function wpcw_post_var( $name ) {
	return filter_input( INPUT_POST, $name );
}

/**
 * Get $_REQUEST var.
 *
 * @since 4.3.0
 *
 * @param string $name The name of the variable.
 *
 * @return false|mixed False if var does not exist.
 */
function wpcw_request_var( $name ) {
	return filter_input( INPUT_REQUEST, $name );
}

/**
 * Get $_GET var.
 *
 * @since 4.3.0
 *
 * @param string $name The name of the variable.
 *
 * @return false|mixed False if var does not exist.
 */
function wpcw_get_var( $name ) {
	return filter_input( INPUT_GET, $name );
}

/**
 * Get data if set, otherwise return a default value or null.
 *
 * Prevents notices when data is not set.
 *
 * @since 4.3.0
 *
 * @param mixed $var Variable.
 * @param string $default Default value.
 *
 * @return mixed
 */
function wpcw_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @since 4.3.0
 *
 * @param string $key Meta key.
 * @param string $default Default value.
 *
 * @return mixed Value sanitized by wpcw_clean.
 */
function wpcw_get_post_data_by_key( $key, $default = '' ) {
	return wpcw_clean( wp_unslash( wpcw_var( $_POST[ $key ], $default ) ) );
}

/**
 * Array Variable.
 *
 * @since 4.3.0
 *
 * @param array $array The array to check.
 * @param string $key The key name in the array.
 *
 * @return bool|string Value or false
 */
function wpcw_array_var( $array, $key ) {
	if ( isset( $array[ $key ] ) ) {
		return trim( stripslashes( $array[ $key ] ) );
	}

	return false;
}

/**
 * Get Host.
 *
 * @since 4.3.0
 *
 * @return string
 */
function wpcw_get_host() {
	$host = false;

	if ( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} elseif ( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} elseif ( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} elseif ( DB_HOST == 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} elseif ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} elseif ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} elseif ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} elseif ( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} elseif ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} elseif ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} elseif ( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
		$host = 'Flywheel';
	} else {
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}

/**
 * WordPress stores names arrays ( month and weekday and all variants )
 * with text value indices, so this creates an array with numerical indices instead.
 *
 * @since 4.3.0
 *
 * @param array $array The array to strip out the text indices.
 *
 * @return array The array with numerical indicies.
 */
function wpcw_string_array_to_numeric( $array ) {
	$numeric_array = array();

	foreach ( $array as $string ) {
		$numeric_array[] = $string;
	}

	return $numeric_array;
}

/**
 * Strip all whitespace and line breaks from string.
 *
 * @since 4.3.0
 *
 * @param string $string The string to strip.
 *
 * @return mixed The stripped string.
 */
function wpcw_strip( $string ) {
	return preg_replace( '/[ \t]+/', ' ', preg_replace( '/\s*$^\s*/m', "\n", $string ) );
}

/**
 * Sanitize Key.
 *
 * @since 4.3.0
 *
 * @param string $key The key to sanitize.
 *
 * @return string The key sanitized.
 */
function wpcw_sanitize_key( $key = '' ) {
	return preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );
}

/**
 * Sanitize Classes.
 *
 * @since 4.3.0
 *
 * @param array $classes The classes to sanitize.
 * @param bool $convert Convert to string or array.
 *
 * @return array|string
 */
function wpcw_sanitize_classes( $classes, $convert = false ) {
	$array = is_array( $classes );
	$css   = array();

	if ( ! empty( $classes ) ) {
		if ( ! $array ) {
			$classes = explode( ' ', trim( $classes ) );
		}
		foreach ( $classes as $class ) {
			$css[] = sanitize_html_class( $class );
		}
	}
	if ( $array ) {
		return $convert ? implode( ' ', $css ) : $css;
	} else {
		return $convert ? $css : implode( ' ', $css );
	}
}

/**
 * Convert Array to JSON.
 *
 * @since 4.3.0
 *
 * @param array $array The array to be converted.
 *
 * @return string The array as a json string.
 */
function wpcw_convert_array_to_json( $array = array() ) {
	return htmlspecialchars( wp_json_encode( $array ) );
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @since 4.3.0
 *
 * @param string|array $var The variable to sanitize.
 *
 * @return string|array The variable sanitized.
 */
function wpcw_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'wpcw_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Get the User IP Address.
 *
 * @since 4.3.0
 *
 * @return string The current user ip address.
 */
function wpcw_user_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	return '';
}

/**
 * Get User Agent.
 *
 * @since 4.3.0
 *
 * @return string The user agent.
 */
function wpcw_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( wpcw_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
}

/**
 * Make Url Relative.
 *
 * @since 4.3.0
 *
 * @param string $link The link to make relative.
 * @param bool $path Give the path instead of url. Default if false.
 *
 * @return string The relative url.
 */
function wpcw_make_url_relative( $url = '', $path = false ) {
	global $is_IIS, $is_iis7;

	if ( empty( $url ) ) {
		return;
	}

	$upload_dir = wp_upload_dir();

	$stripped_url      = str_replace( array( 'http://', 'https://' ), '', $url );
	$stripped_home_url = str_replace( array( 'http://', 'https://' ), '', home_url() );

	$is_home_url = ( strpos( $stripped_url, $stripped_home_url ) !== false );

	if ( $path && $is_home_url ) {
		return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
	}

	return ( $is_IIS || $is_iis7 || ! $is_home_url ) ? $url : trim( wp_make_link_relative( $url ) );
}

/**
 * Get Fo Passowrd.
 *
 * @since 4.3.0
 *
 * @return string The masked fo password.
 */
function wpcw_get_fo_password() {
	return '&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;';
}

/**
 * Array Column.
 *
 * Fallback for php 5.4.0
 *
 * @param null $input
 * @param null $column_key
 * @param null $index_key
 *
 * @return $resultArray array|bool
 */
function wpcw_array_column( $input = null, $column_key = null, $index_key = null ) {
	if ( function_exists( 'array_column' ) ) {
		return array_column( $input, $column_key, $index_key );
	}

	// Using func_get_args() in order to check for proper number of
	// parameters and trigger errors exactly as the built-in array_column()
	// does in PHP 5.5.
	$argc   = func_num_args();
	$params = func_get_args();
	if ( $argc < 2 ) {
		trigger_error( "array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING );

		return null;
	}

	if ( ! is_array( $params[0] ) ) {
		trigger_error(
			'array_column() expects parameter 1 to be array, ' . gettype( $params[0] ) . ' given',
			E_USER_WARNING
		);

		return null;
	}

	if ( ! is_int( $params[1] )
	     && ! is_float( $params[1] )
	     && ! is_string( $params[1] )
	     && $params[1] !== null
	     && ! ( is_object( $params[1] ) && method_exists( $params[1], '__toString' ) )
	) {
		trigger_error( 'array_column(): The column key should be either a string or an integer', E_USER_WARNING );

		return false;
	}
	if ( isset( $params[2] )
	     && ! is_int( $params[2] )
	     && ! is_float( $params[2] )
	     && ! is_string( $params[2] )
	     && ! ( is_object( $params[2] ) && method_exists( $params[2], '__toString' ) )
	) {
		trigger_error( 'array_column(): The index key should be either a string or an integer', E_USER_WARNING );

		return false;
	}

	$paramsInput     = $params[0];
	$paramsColumnKey = ( $params[1] !== null ) ? (string) $params[1] : null;
	$paramsIndexKey  = null;

	if ( isset( $params[2] ) ) {
		if ( is_float( $params[2] ) || is_int( $params[2] ) ) {
			$paramsIndexKey = (int) $params[2];
		} else {
			$paramsIndexKey = (string) $params[2];
		}
	}

	$resultArray = array();
	foreach ( $paramsInput as $row ) {
		$key    = $value = null;
		$keySet = $valueSet = false;

		if ( $paramsIndexKey !== null && array_key_exists( $paramsIndexKey, $row ) ) {
			$keySet = true;
			$key    = (string) $row[ $paramsIndexKey ];
		}

		if ( $paramsColumnKey === null ) {
			$valueSet = true;
			$value    = $row;
		} elseif ( is_array( $row ) && array_key_exists( $paramsColumnKey, $row ) ) {
			$valueSet = true;
			$value    = $row[ $paramsColumnKey ];
		}

		if ( $valueSet ) {
			if ( $keySet ) {
				$resultArray[ $key ] = $value;
			} else {
				$resultArray[] = $value;
			}
		}
	}

	return $resultArray;
}

/**
 * Get Row Action Link.
 *
 * @since 4.4.0
 *
 * @param string $label The row action label.
 * @param array $args Optional. The additional args for the link
 * @param array $query_args Optional. The query argumnets.
 *
 * @return string The row action link.
 */
function wpcw_table_row_action_link( $label, $args = array(), $query_args = array() ) {
	$base_uri = ! empty( $args['base_uri'] ) ? $args['base_uri'] : false;

	if ( empty( $args['nonce'] ) ) {
		$url = ! empty( $query_args ) ? esc_url( add_query_arg( $query_args, $base_uri ) ) : $base_uri;
	} else {
		$url = ! empty( $query_args ) ? wp_nonce_url( add_query_arg( $query_args, $base_uri ), $args['nonce'] ) : wp_nonce_url( $base_uri, $args['nonce'] );
	}

	$class = empty( $args['class'] ) ? '' : sprintf( ' class="%s"', esc_attr( $args['class'] ) );
	$title = empty( $args['title'] ) ? '' : sprintf( ' title="%s"', esc_attr( $args['title'] ) );
	$atts  = empty( $args['atts'] ) ? '' : ' ' . $args['atts'];

	return sprintf( '<a href="%1$s"%2$s%3$s%4$s>%5$s</a>', $url, $class, $title, $atts, esc_html( $label ) );
}

/**
 * Retrieves the MySQL server version. Based on $wpdb.
 *
 * @since 4.4.0
 *
 * @return array Database Vesion information.
 */
function wpcw_get_server_database_version() {
	global $wpdb;

	if ( empty( $wpdb->is_mysql ) ) {
		return array(
			'string' => '',
			'number' => '',
		);
	}

	if ( $wpdb->use_mysqli ) {
		$server_info = mysqli_get_server_info( $wpdb->dbh );
	} else {
		$server_info = mysql_get_server_info( $wpdb->dbh );
	}

	return array(
		'string' => $server_info,
		'number' => preg_replace( '/([^\d.]+).*/', '', $server_info ),
	);
}

/**
 * Get Duplicates.
 *
 * @since 4.4.2
 *
 * @param array $array The array to look into.
 *
 * @return array $array The array with the duplicates.
 */
function wpcw_array_get_duplicates( $array ) {
	return array_unique( array_diff_assoc( $array, array_unique( $array ) ) );
}

/**
 * Maybe Catch Form Data.
 *
 * @since 4.6.3
 */
function wpcw_maybe_catch_form_data() {
	if ( empty( $_POST['form_data'] ) ) {
		return;
	}

	// Map and merge form_data to $_POST so it's accessible in other functions.
	parse_str( $_POST['form_data'], $form_data );
	$_POST    = array_merge( $_POST, $form_data );
	$_REQUEST = array_merge( $_REQUEST, $_POST );
}

/**
 * Convert String to PascalCase.
 *
 * @since 4.6.3
 *
 * @param string $string The string to convert.
 * @param string $separator The string separator.
 *
 * @return string The converted string.
 */
function wpcw_convert_to_pascal_case( $string, $separator = '_' ) {
	return str_replace( '_', '', ucwords( $string, '_' ) );
}
