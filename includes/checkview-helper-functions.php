<?php
/**
 * Fires to expose plugins helper functions.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes
 */

/**
 * Validates IP address.
 *
 * @param IP $ip IP address.
 * @return bool
 */
function checkview_validate_ip( $ip ) {
	// Validate that the input is a valid IP address.
	if ( ! empty( $ip ) && ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		// If validation fails, handle the error appropriately.
		error_log( esc_html__( 'Invalid IP Address', 'checkview' ) );
		return false;
	} elseif ( empty( $ip ) ) {
		return false;
	}
	return true;
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Filter hCaptcha activation flag.
 *
 * @param bool $activate Activate flag.
 *
 * @return bool
 */
function checkview_my_hcap_activate( $activate ) {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		// check ip from share internet.
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// to check ip is pass from proxy.
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	} else {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
	// If validation fails, handle the error appropriately.
	if ( ! checkview_validate_ip( $ip ) ) {
		return $activate;
	}

	if ( isset( $_REQUEST['checkview_test_id'] ) || 'checkview-saas' === get_option( $ip ) ) {
		return false;
	}
	return $activate;
}

add_filter( 'hcap_activate', 'checkview_my_hcap_activate' );

if ( ! function_exists( 'checkview_hcap_whitelist_ip' ) ) {
	/**
	 * Filter user IP to check if it is whitelisted.
	 * For whitelisted IPs, hCaptcha will not be shown.
	 *
	 * @param bool   $whitelisted Whether IP is whitelisted.
	 * @param string $ip          IP.
	 *
	 * @return bool
	 */
	function checkview_hcap_whitelist_ip( $whitelisted, $ip ) {

		// Whitelist local IPs.
		if ( false === $ip ) {
			return true;
		}
		if ( function_exists( 'checkview_get_api_ip' ) ) {
			$cv_bot_ip = checkview_get_api_ip();
		} else {
			return $whitelisted;
		}
		// Whitelist some other IPs.
		if ( is_array( $cv_bot_ip ) && in_array( $ip, $cv_bot_ip ) ) {
			return true;
		}

		return $whitelisted;
	}
	add_filter( 'hcap_whitelist_ip', 'checkview_hcap_whitelist_ip', 10, 2 );
}
/**
 * Function to remove the specific action.
 *
 * @return void
 */
function remove_gravityforms_recaptcha_addon() {
	// Make sure the class exists before trying to remove the action.
	if ( class_exists( 'GF_RECAPTCHA_Bootstrap' ) && isset( $_REQUEST['checkview_test_id'] ) ) {
		remove_action( 'gform_loaded', array( 'GF_RECAPTCHA_Bootstrap', 'load_addon' ), 5 );
	}
}
// Use a hook with a priority higher than 5 to ensure the action is removed after it is added.
add_action( 'gform_loaded', 'remove_gravityforms_recaptcha_addon', 1 );
