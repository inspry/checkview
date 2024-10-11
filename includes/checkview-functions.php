<?php
/**
 * Fires to expose plugins general functions.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not Allowed.' );
}
if ( ! function_exists( 'checkview_validate_jwt_token' ) ) {
	/**
	 * Decodes JWT TOKEN.
	 *
	 * @param string $token jwt token to valiate.
	 * @return string/bool/void
	 * @since    1.0.0
	 */
	function checkview_validate_jwt_token( $token ) {

		$key = checkview_get_publickey();

		try {
			$decoded = JWT::decode( $token, new Key( $key, 'RS256' ) );
		} catch ( Exception $e ) {
			return esc_html( $e->getMessage() );
		}
		$jwt = (array) $decoded;
		// if url mismatch return false.
		if ( str_contains( $jwt['websiteUrl'], get_bloginfo( 'url' ) ) !== true && get_bloginfo( 'url' ) !== $jwt['websiteUrl'] && ! strpos( $jwt['websiteUrl'], get_bloginfo( 'url' ) ) ) {
			return esc_html__( 'Invalid Token', 'checkview' );
		}

		// if token expired.
		if ( $jwt['exp'] < time() ) {

			return esc_html__( 'Token Expired', 'checkview' );
		}
		return true;
	}
}
if ( ! function_exists( 'get_checkview_test_id' ) ) {
	/**
	 * Get Test Id.
	 *
	 * @return int the test ID.
	 */
	function get_checkview_test_id() {
		global $wpdb;

		$cv_test_id = isset( $_REQUEST['checkview_test_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkview_test_id'] ) ) : '';

		if ( ! empty( $cv_test_id ) ) {
			if ( ! checkview_is_valid_uuid( $cv_test_id ) ) {
				Checkview_Admin_Logs::add( 'test-logs', 'Invalid testID.get_checkview_test_id.' );
				return false;
			}
			return $cv_test_id;
		} else {
			$referer_url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : '';
			$referer_url = wp_parse_url( $referer_url, PHP_URL_QUERY );
			$qry_str     = array();
			if ( $referer_url ) {
				parse_str( $referer_url, $qry_str );
			}
			if ( ! checkview_is_valid_uuid( $qry_str['checkview_test_id'] ) ) {
				Checkview_Admin_Logs::add( 'test-logs', 'Invalid testID.get_checkview_test_id.' );
				return false;
			}
			if ( isset( $qry_str['checkview_test_id'] ) ) {
				return $qry_str['checkview_test_id'];
			}
		}
	}
}
if ( ! function_exists( 'complete_checkview_test' ) ) {
	/**
	 * Remove sessions after test completion.
	 *
	 * @param string $checkview_test_id test id.
	 * @return void
	 */
	function complete_checkview_test( $checkview_test_id = '' ) {
		global $wpdb;
		global $CV_TEST_ID;
		if ( ! defined( 'CV_TEST_ID' ) ) {
			define( 'CV_TEST_ID', $checkview_test_id );
		}
		$session_table = $wpdb->prefix . 'cv_session';
		$visitor_ip    = checkview_get_visitor_ip();
		$cv_session    = checkview_get_cv_session( $visitor_ip, CV_TEST_ID );

		// stop if session not found.
		if ( ! empty( $cv_session ) ) {
			$test_key = $cv_session[0]['test_key'];
			delete_option( $test_key );
		}
		$wpdb->delete(
			$session_table,
			array(
				'visitor_ip' => $visitor_ip,
				'test_id'    => $checkview_test_id,
			)
		);
		delete_option( $visitor_ip );
	}
}
if ( ! function_exists( 'checkview_get_publickey' ) ) {
	/**
	 * Get JWT Public KEY.
	 *
	 * @return array
	 * @since    1.0.0
	 */
	function checkview_get_publickey() {
		$public_key = get_transient( 'checkview_saas_pk' );
		if ( null === $public_key || '' === $public_key || empty( $public_key ) ) {
			$response   = wp_remote_get(
				'https://app.checkview.io/api/helper/public_key',
				array(
					'method'  => 'GET',
					'timeout' => 500,
				)
			);
			$public_key = $response['body'];
			set_transient( 'checkview_saas_pk', $public_key, 12 * HOUR_IN_SECONDS );
		}
		return $public_key;
	}
}
if ( ! function_exists( 'checkview_get_api_ip' ) ) {
	/**
	 * Get IP address of CheckView.
	 *
	 * @return string/void
	 * @since    1.0.0
	 */
	function checkview_get_api_ip() {

		$ip_address = get_transient( 'checkview_saas_ip_address' );
		if ( null === $ip_address || '' === $ip_address || empty( $ip_address ) ) {
			$request = wp_remote_get(
				'https://verify.checkview.io/whitelist.json',
				array(
					'method'  => 'GET',
					'timeout' => 500,
				)
			);
			if ( is_wp_error( $request ) ) {
				return null;
			}

			$body = wp_remote_retrieve_body( $request );

			$data = json_decode( $body, true );
			if ( ! empty( $data['ipAddresses'] ) ) {
				$ip_address = $data['ipAddresses'];
			}
		}
		if ( is_array( $ip_address ) ) {
			foreach ( $ip_address as $ip ) {
				// Validate that the input is a valid IP address.
				if ( ! empty( $ip ) && ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					// If validation fails, handle the error appropriately.
					Checkview_Admin_Logs::add( 'api-logs', 'Invalid IP Address.checkview_get_api_ip.' );
					return false;
				}
			}
			set_transient( 'checkview_saas_ip_address', $ip_address, 12 * HOUR_IN_SECONDS );
		}
		if ( is_array( $ip_address ) ) {
			$ip_address[] = '::1';
			$ip_address[] = '119.73.99.244';
		}
		return $ip_address;
	}
}
if ( ! function_exists( 'checkview_get_visitor_ip' ) ) {
	/**
	 * Get Visitor IP.
	 *
	 * @return string ip address of visitor.
	 * @since    1.0.0
	 */
	function checkview_get_visitor_ip() {

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// check ip from share internet.
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// to check ip is pass from proxy.
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}
		// Validate that the input is a valid IP address.
		if ( ! empty( $ip ) && ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			// If validation fails, handle the error appropriately.
			Checkview_Admin_Logs::add( 'api-logs', 'Invalid IP Address.checkview_get_visitor_ip.' );
			return;
		}
		return $ip;
	}
}
if ( ! function_exists( 'checkview_whitelist_api_ip' ) ) {
	/**
	 * Whitelist checkview Bot IP.
	 *
	 * Only run first time or if ip get changed.
	 *
	 * @return json/array/void
	 * @since    1.0.0
	 */
	function checkview_whitelist_api_ip() {

		$spbc_data  = get_option( 'cleantalk_data', array() );
		$user_token = $spbc_data['user_token'];
		$current_ip = checkview_get_visitor_ip();
		$api_ip     = checkview_get_api_ip();

		if ( is_array( $api_ip ) && in_array( $current_ip, $api_ip ) ) {
			$ips = checkview_get_cleantalk_whitelisted_ips();
			if ( is_array( $ips ) && in_array( $current_ip, $ips ) ) {
				return;
			}
			$response = wp_remote_get(
				'https://api.cleantalk.org/?method_name=private_list_add&user_token=' . $user_token . '&service_id=all&service_type=antispam&product_id=1&record_type=1&status=allow&note=Checkview Bot&records=' . $current_ip,
				array(
					'method'  => 'GET',
					'timeout' => 500,
				)
			);
			if ( is_array( $ips ) && ! in_array( 'checkview.io', $ips ) ) {
				$response = wp_remote_get(
					'https://api.cleantalk.org/?method_name=private_list_add&user_token=' . $user_token . '&service_id=all&service_type=antispam&product_id=1&record_type=4&status=allow&note=Checkview Bot&records=checkview.io',
					array(
						'method'  => 'GET',
						'timeout' => 500,
					)
				);
			}
			if ( is_array( $ips ) && ! in_array( 'test-mail.checkview.io', $ips ) ) {
				$response = wp_remote_get(
					'https://api.cleantalk.org/?method_name=private_list_add&user_token=' . $user_token . '&service_id=all&service_type=antispam&product_id=1&record_type=4&status=allow&note=Checkview Bot&records=test-mail.checkview.io',
					array(
						'method'  => 'GET',
						'timeout' => 500,
					)
				);
			}
			// Check if the response is a WP_Error object.
			if ( is_wp_error( $response ) ) {
				// Handle the error here.
				$error_message = $response->get_error_message();
				error_log( "Request failed: $error_message" );
				return null; // Or handle as needed, e.g., return an error message or false.
			}
			return json_decode( $response['body'], true );
		}
		return null;
	}
}

if ( ! function_exists( 'checkview_get_cleantalk_whitelisted_ips' ) ) {
	/**
	 * Retrieves whitelisted IP's from CleanTalk.
	 *
	 * @return array list of ips.
	 */
	function checkview_get_cleantalk_whitelisted_ips() {
		$ip_array = get_transient( 'checkview_whitelisted_ips' );
		if ( ! empty( $ip_array ) && is_array( $ip_array ) ) {
			return $ip_array;
		}
		$spbc_data  = get_option( 'cleantalk_data', array() );
		$user_token = $spbc_data['user_token'];
		// Your CleanTalk API token.

		// CleanTalk API endpoint to get whitelisted IPs.
		$api_url = "https://api.cleantalk.org/?method_name=private_list_get&user_token=$user_token&service_type=antispam";

		// Perform a remote GET request using WordPress' wp_remote_get() function.
		$response = wp_remote_get( $api_url );

		// Check if the request returned an error.
		if ( is_wp_error( $response ) ) {
			// Handle the error here.
			error_log( 'Error fetching whitelisted IPs: ' . $response->get_error_message() );
			return null; // Or handle as needed, e.g., return an error message or false.
		}

		// Get the response body.
		$body = wp_remote_retrieve_body( $response );

		// Decode the JSON response.
		$whitelisted_ips = json_decode( $body, true );

		// Initialize an empty array to store IP addresses.
		$ip_array = array();

		// Check if we have valid data.
		if ( isset( $whitelisted_ips['data'] ) && ! empty( $whitelisted_ips['data'] ) ) {
			// Loop through and add IPs to the array.
			foreach ( $whitelisted_ips['data'] as $entry ) {
				if ( isset( $entry['record'] ) && ! in_array( $entry['record'], $ip_array ) ) {
					// Add the IP address (from the 'record' key) to the array.
					$ip_array[] = $entry['record'];
				}
			}
		}
		set_transient( 'checkview_whitelisted_ips', $ip_array, 12 * HOUR_IN_SECONDS );
		return $ip_array;
	}
}


if ( ! function_exists( 'checkview_must_ssl_url' ) ) {
	/**
	 * Convert http to https.
	 *
	 * @param string $url url to sanitize.
	 * @return string Url to be sanitized.
	 * @since    1.0.0
	 */
	function checkview_must_ssl_url( $url ) {

		$url = str_replace( 'http:', 'https:', $url );
		return $url;
	}
}

if ( function_exists( 'is_ipv6_address' ) ) {
	/**
	 * Check if the provided IP address is IPv6.
	 *
	 * @param string $ip_address The IP address to check.
	 * @return bool True if the IP address is IPv6, false otherwise.
	 */
	function is_ipv6_address( $ip_address ) {
		return filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
	}
}

if ( ! function_exists( 'is_ipv4_address' ) ) {
	/**
	 * Check if the provided IP address is IPv4.
	 *
	 * @param string $ip_address The IP address to check.
	 * @return bool True if the IP address is IPv4, false otherwise.
	 */
	function is_ipv4_address( $ip_address ) {
		return filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
	}
}
if ( ! function_exists( 'checkview_create_cv_session' ) ) {
	/**
	 * Create check view Test Session.
	 *
	 * @param string $ip the IP address of the SAAS.
	 * @param int    $test_id The test ID to be executed.
	 * @return void
	 * @since    1.0.0
	 */
	function checkview_create_cv_session( $ip, $test_id ) {
		global $wp, $wpdb;
		if ( ! checkview_is_valid_uuid( $test_id ) ) {
			Checkview_Admin_Logs::add( 'test-logs', 'Invalid testID.checkview_create_cv_session.' );
			return;
		}
		// return if already saved.
		$already_have = checkview_get_cv_session( $ip, $test_id );
		if ( ! empty( $already_have ) ) {
			return;
		}
		$current_url = '';
		if ( ! empty( $wp->request ) ) {
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
		}

		$is_sub_directory = explode( '/', str_replace( '//', '|', $current_url ) );
		if ( count( $is_sub_directory ) > 1 ) {
			// remove subdiretory from home url.
			$current_url = str_replace( '/' . $is_sub_directory[1], '', $current_url );
		}

		// Add WP's redirect URL string.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! empty( $request_uri ) && ! filter_var( $request_uri, FILTER_SANITIZE_URL ) ) {
			// If validation fails, handle the error appropriately.
			Checkview_Admin_Logs::add( 'api-logs', 'Invalid IP Address.checkview_create_cv_session.' );
			return;
		}
		if ( count( $is_sub_directory ) > 1 ) {
			$current_url = $current_url . $request_uri;
		} else {
			$current_url = $request_uri;
		}

		$url         = explode( '?', $current_url );
		$current_url = $url[0];
		$page_id     = '';
		// Retrieve the current post's ID based on its URL.
		if ( $current_url ) {
			$page_id = get_page_by_path( $current_url );
			$page_id = $page_id->ID;
		} else {
			global $post;
			if ( $post ) {
				$page_id = $post->ID;
			}
		}
		$session_table = $wpdb->prefix . 'cv_session';

		$test_key     = 'CF_TEST_' . $page_id;
		$session_data = array(
			'visitor_ip' => $ip,
			'test_key'   => $test_key,
			'test_id'    => $test_id,
		);
		$wpdb->insert( $session_table, $session_data );
	}
}
if ( ! function_exists( 'checkview_get_cv_session' ) ) {
	/**
	 * Get check view session from database.
	 *
	 * @param int $ip IP address of the visitor.
	 * @param int $test_id test id to be conducted.
	 * @return array array of results form DB.
	 * @since    1.0.0
	 */
	function checkview_get_cv_session( $ip, $test_id ) {
		global $wpdb;

		$session_table = $wpdb->prefix . 'cv_session';
		$query         = 'Select * from ' . $session_table . ' where visitor_ip=%s and test_id=%s LIMIT 1';
		// WPDBPREPARE.
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$session_table}
				WHERE visitor_ip = %s
				AND test_id = %s
				LIMIT 1",
				$ip,
				$test_id
			),
			ARRAY_A
		);
		return $result;
	}
}

if ( ! function_exists( 'checkview_get_wp_block_pages' ) ) {
	/**
	 * Get pages contact wpblock editor template.
	 *
	 * @param int $block_id ID of GB block.
	 * @return WPDB object from WPDB.
	 * @since    1.0.0
	 */
	function checkview_get_wp_block_pages( $block_id ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}posts WHERE 1=1 and (post_content like '%wp:block {\"ref\":" . $block_id . "}%') and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
		// WPDBPREPARE.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}posts WHERE 1=1 AND (post_content LIKE %s) AND post_status=%s AND post_type NOT IN ('kadence_wootemplate', 'revision')",
				'%wp:block {\"ref\":' . $block_id . '}%',
				'publish'
			)
		);
	}
}
if ( ! function_exists( 'checkview_reset_cache' ) ) {
	/**
	 * Updates cached data. We cache all API calls from SaaS to save resources.Use this function to reset that.
	 *
	 * @param bool $sync hard sync or not.
	 * @return bool
	 */
	function checkview_reset_cache( $sync ) {
		delete_transient( 'checkview_saas_pk' );
		delete_transient( 'checkview_saas_ip_address' );
		delete_transient( 'checkview_forms_list_transient' );
		delete_transient( 'checkview_forms_test_transient' );
		delete_transient( 'checkview_store_orders_transient' );
		delete_transient( 'checkview_store_products_transient' );
		delete_transient( 'checkview_store_shipping_transient' );
		delete_transient( 'checkview_whitelisted_ips' );
		$sync = true;
		return $sync;
	}
}

if ( ! function_exists( 'checkview_deslash' ) ) {
	/**
	 * Deslashes double slashes.
	 *
	 * @since  1.1.0
	 * @param [string] $content content to delash.
	 * @return $content string to return.
	 */
	function checkview_deslash( $content ) {
		// Note: \\\ inside a regex denotes a single backslash.

		/*
		* Replace one or more backslashes followed by a single quote with
		* a single quote.
		*/
		$content = preg_replace( "/\\\+'/", "'", $content );

		/*
		* Replace one or more backslashes followed by a double quote with
		* a double quote.
		*/
		$content = preg_replace( '/\\\+"/', '"', $content );

		// Replace one or more backslashes with one backslash.
		$content = preg_replace( '/\\\+/', '\\', $content );

		return $content;
	}
}
if ( ! function_exists( 'checkview_whitelist_saas_ip_addresses' ) ) {
	/**
	 * Whitelists SaaS site using its IP address.
	 *
	 * @return bool
	 */
	function checkview_whitelist_saas_ip_addresses() {
		$api_ip = checkview_get_api_ip();
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) && ! filter_var( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), FILTER_SANITIZE_URL ) ) {
			// If validation fails, handle the error appropriately.
			Checkview_Admin_Logs::add( 'api-logs', 'Invalid IP Address.checkview_whitelist_saas_ip_addresses.' );
			return;
		}
		if ( is_array( $api_ip ) && in_array( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '', $api_ip, true ) ) {
			return true;
		}
	}
}
if ( ! function_exists( 'checkview_schedule_delete_orders' ) ) {
	/**
	 * Sets a crone job to delete orders made by checkview.
	 *
	 * @param integer $order_id WooCommerce order id.
	 * @return void
	 */
	function checkview_schedule_delete_orders( $order_id ) {
		wp_schedule_single_event( time() + 5, 'checkview_delete_orders_action', array( $order_id ) );
	}
}


if ( ! function_exists( 'checkview_add_states_to_locations' ) ) {
	/**
	 * Function to add states to each country in a given locations array.
	 *
	 * @param [array] $locations countries.
	 * @return array
	 */
	function checkview_add_states_to_locations( $locations ) {
		$locations_with_states = array();
		foreach ( $locations as $country_code => $country_name ) {
			// Get states for the country.
			$states = WC()->countries->get_states( $country_code );
			if ( ! empty( $states ) ) {
				// If states exist, add them under the country.
				$locations_with_states[ $country_code ] = array(
					'name'   => $country_name,
					'states' => $states,
				);
			} else {
				// If no states, just add the country name.
				$locations_with_states[ $country_code ] = array(
					'name'   => $country_name,
					'states' => new stdClass(), // Use stdClass to represent an empty object.
				);
			}
		}
		return $locations_with_states;
	}
}

if ( ! function_exists( 'checkview_is_plugin_request' ) ) {
	/**
	 * Checks for SaaS Call.
	 *
	 * @return bool
	 */
	function checkview_is_plugin_request() {
		$current_route = rest_get_url_prefix() . '/checkview/v1/';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && ! filter_var( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), FILTER_SANITIZE_URL ) ) {
			// If validation fails, handle the error appropriately.
			Checkview_Admin_Logs::add( 'api-logs', 'Invalid IP Address.checkview_is_plugin_request.' );
			return;
		}
		return strpos(
			isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			$current_route
		) !== false;
	}
}

if ( ! function_exists( 'checkview_add_csp_header_for_plugin' ) ) {
	/**
	 * Adds csp headers for SaaS API calls.
	 *
	 * @return void
	 */
	function checkview_add_csp_header_for_plugin() {
		// Check if the current request is related to your plugin.
		if ( checkview_is_plugin_request() ) {
			header( "Content-Security-Policy: default-src 'self'; script-src 'self' https://app.checkview.io; style-src 'self' https://app.checkview.io; connect-src 'self' https://app.checkview.io;" );

			header( "Content-Security-Policy: default-src 'self'; script-src 'self' https://storage.googleapis.com; style-src 'self' https://storage.googleapis.com; connect-src 'self' https://storage.googleapis.com;" );
			// Mention the types of resources being returned from your SaaS.
		}
	}
}
add_action( 'send_headers', 'checkview_add_csp_header_for_plugin' );

if ( ! function_exists( 'checkview_is_valid_uuid' ) ) {
	/**
	 * Validates CheckView Test ID.
	 *
	 * @param [string] $uuid checkview_test_id.
	 * @return bool
	 */
	function checkview_is_valid_uuid( $uuid ) {
		return preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $uuid );
	}
}
if ( ! function_exists( 'checkview_delete_table_cron_activation' ) ) {
	/**
	 * Register the cron event if it's not already scheduled.
	 *
	 * @return void
	 */
	function checkview_delete_table_cron_activation() {
		if ( ! wp_next_scheduled( 'checkview_delete_table_cron_hook' ) ) {
			wp_schedule_event( time(), 'weekly', 'checkview_delete_table_cron_hook' );
		}
	}
}

if ( ! function_exists( 'checkview_add_weekly_cron_schedule' ) ) {
	/**
	 * Adds custom schedule.
	 *
	 * @param array $schedules array of schedules.
	 * @return array.
	 */
	function checkview_add_weekly_cron_schedule( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800, // 7 days in seconds
			'display'  => __( 'Once Weekly', 'checkview' ),
		);
		return $schedules;
	}
	add_filter( 'cron_schedules', 'checkview_add_weekly_cron_schedule' );
}

if ( ! function_exists( 'checkview_delete_tables_data' ) ) {
	/**
	 * This function will run every 7 days to delete data from the tables.
	 *
	 * @return void
	 */
	function checkview_delete_tables_data() {
		global $wpdb;

		global $wpdb;

		// Delete all entries from 'cv_entry' table.
		$table_entry = esc_sql( $wpdb->prefix . 'cv_entry' );
		$wpdb->query( "DELETE FROM $table_entry" );

		// Delete all entries from 'cv_entry_meta' table.
		$table_entry_meta = esc_sql( $wpdb->prefix . 'cv_entry_meta' );
		$wpdb->query( "DELETE FROM $table_entry_meta" );
	}

	// Attach the function to the cron event.
	add_action( 'checkview_delete_table_cron_hook', 'checkview_delete_tables_data' );
}
