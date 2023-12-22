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

/**
 * Decodes JWT TOKEN.
 *
 * @param string $token jwt token to valiate.
 * @return string/bool/void
 * @since    1.0.0
 */
function validate_jwt_token( $token ) {

	$key = get_publickey();

	try {
		$decoded = JWT::decode( $token, new Key( $key, 'RS256' ) );
	} catch ( Exception $e ) {
		return esc_html( $e->getMessage() );
	}
	$jwt = (array) $decoded;

	// if url mismatch return false.
	if ( get_bloginfo( 'url' ) !== $jwt['websiteUrl'] ) {
		return esc_html__( 'Invalid Token', 'checkform-helper' );
	}

	// if token expired.
	if ( $jwt['exp'] < time() ) {

		return esc_html__( 'Token Expired', 'checkform-helper' );
	}
	return true;
}

/**
 * Get Test Id.
 *
 * @return int the test ID.
 */
function get_checkview_test_id() {
	global $wpdb;

	$cv_test_id = isset( $_REQUEST['checkview_test_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkview_test_id'] ) ) : '';

	if ( ! empty( $cv_test_id ) ) {
		return $cv_test_id;
	} else {
		$referer_url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$referer_url = wp_parse_url( $referer_url, PHP_URL_QUERY );
		$qry_str     = array();
		parse_str( $referer_url, $qry_str );
		if ( isset( $qry_str['checkview_test_id'] ) ) {
			return $qry_str['checkview_test_id'];
		}
	}
}

/**
 * Remove sessions after test completion.
 *
 * @return void
 */
function complete_checkview_test() {
	global $wpdb;

	$session_table = $wpdb->prefix . 'cv_session';
	$visitor_ip    = get_visitor_ip();
	$wpdb->delete(
		$session_table,
		array(
			'visitor_ip' => $visitor_ip,
			'test_id'    => CV_TEST_ID,
		)
	);
}

/**
 * Get JWT Public KEY.
 *
 * @return array
 * @since    1.0.0
 */
function get_publickey() {
	// Todo.
	$response = wp_remote_get(
		'https://app-dev.checkview.io/api/helper/public_key',
		array(
			'method'  => 'GET',
			'timeout' => 500,
		)
	);
	return $response['body'];
}

/**
 * Get IP address of CheckView.
 *
 * @return array/void
 * @since    1.0.0
 */
function get_api_ip() {

	// Todo.
	$request = wp_remote_get(
		'https://app-dev.checkview.io/api/helper/container_ip',
		array(
			'method'  => 'GET',
			'timeout' => 500,
		)
	);

	if ( is_wp_error( $request ) ) {
		return;
	}

	$body = wp_remote_retrieve_body( $request );

	$data = json_decode( $body, true );

	return $data['ipAddress'];
}

/**
 * Whitelist checkview Bot IP
 *
 * Only run first time or if ip get changed.
 *
 * @return json/array/void
 * @since    1.0.0
 */
function whitelist_api_ip() {

		$spbc_data  = get_option( 'cleantalk_data', array() );
		$user_token = $spbc_data['user_token'];
		$current_ip = get_visitor_ip();
		$api_ip     = get_api_ip();

	if ( $api_ip === $current_ip ) {
		$response = wp_remote_get(
			'https://api.cleantalk.org/?method_name=private_list_add&user_token=' . $user_token . '&service_id=all&service_type=antispam&product_id=1&record_type=1&status=allow&note=Checkview Bot&records=' . $api_ip,
			array(
				'method'  => 'GET',
				'timeout' => 500,
			)
		);
		$response = wp_remote_get(
			'https://api.cleantalk.org/?method_name=private_list_add&user_token=' . $user_token . '&service_id=all&service_type=antispam&product_id=1&record_type=4&status=allow&note=Checkview Bot&records=checkview.io',
			array(
				'method'  => 'GET',
				'timeout' => 500,
			)
		);
		return json_decode( $response['body'], true );
	}
	return null;
}

/**
 * Convert http to https.
 *
 * @param string $url url to sanitize.
 * @return string Url to be sanitized.
 * @since    1.0.0
 */
function must_ssl_url( $url ) {

	$url = str_replace( 'http:', 'https:', $url );
	return $url;
}

/**
 * Get Visitor IP.
 *
 * @return string ip address of visitor.
 * @since    1.0.0
 */
function get_visitor_ip() {

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		// check ip from share internet.
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// to check ip is pass from proxy.
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} else {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
	return $ip;
}

/**
 * Create check view Test Session.
 *
 * @param string $ip the IP address of the SAAS.
 * @param int    $test_id The test ID to be conducted.
 * @return void
 * @since    1.0.0
 */
function create_cv_session( $ip, $test_id ) {
	global $wp, $wpdb;

	// return if already saved.
	$already_have = get_cv_session( $ip, $test_id );
	if ( ! empty( $already_have ) ) {
		return;
	}

	$current_url = home_url( add_query_arg( array(), $wp->request ) );

	$is_sub_directory = explode( '/', str_replace( '//', '|', $current_url ) );
	if ( count( $is_sub_directory ) > 1 ) {
		// remove subdiretory from home url.
		$current_url = str_replace( '/' . $is_sub_directory[1], '', $current_url );
	}

	// Add WP's redirect URL string.
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$current_url = $current_url . $request_uri;

	// Retrieve the current post's ID based on its URL.
	$page_id       = url_to_postid( $current_url );
	$session_table = $wpdb->prefix . 'cv_session';

	$wpdb->delete( $session_table, array( 'visitor_ip' => $ip ) );
	$test_key     = 'CF_TEST_' . $page_id;
	$session_data = array(
		'visitor_ip' => $ip,
		'test_key'   => $test_key,
		'test_id'    => $test_id,
	);
	$wpdb->insert( $session_table, $session_data );
}

/**
 * Get check view session from database.
 *
 * @param int $ip IP address of the visitor.
 * @param int $test_id test id to be conducted.
 * @return array array of results form DB.
 * @since    1.0.0
 */
function get_cv_session( $ip, $test_id ) {
	global $wpdb;

	$session_table = $wpdb->prefix . 'cv_session';
	$result        = $wpdb->get_results( $wpdb->prepare( 'Select * from %s where visitor_ip=%d and test_id=%d LIMIT 1', $session_table, $ip, $test_id ) );
	return $result;
}

/**
 * Get pages contact wpblock editor template.
 *
 * @param int $block_id ID of GB block.
 * @return WPDB object from WPDB.
 * @since    1.0.0
 */
function get_wp_block_pages( $block_id ) {
	global $wpdb;

	$sql = "SELECT * FROM {$wpdb->prefix}posts WHERE 1=1 and (post_content like '%wp:block {\"ref\":" . $block_id . "}%') and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
	return $wpdb->get_results( $sql );
}
