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
if ( ! function_exists( 'validate_jwt_token' ) ) {
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
		$visitor_ip    = get_visitor_ip();
		$wpdb->delete(
			$session_table,
			array(
				'visitor_ip' => $visitor_ip,
				'test_id'    => CV_TEST_ID,
			)
		);
		delete_option( $visitor_ip );
		delete_option( $visitor_ip . 'use_stripe' );
	}
}
if ( ! function_exists( 'get_publickey' ) ) {
	/**
	 * Get JWT Public KEY.
	 *
	 * @return array
	 * @since    1.0.0
	 */
	function get_publickey() {
		$public_key = get_transient( 'checkview_saas_pk' );
		// Todo.
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
if ( ! function_exists( 'get_api_ip' ) ) {
	/**
	 * Get IP address of CheckView.
	 *
	 * @return string/void
	 * @since    1.0.0
	 */
	function get_api_ip() {

		// Todo.
		$ip_address = get_transient( 'checkview_saas_ip_address' );
		if ( null === $ip_address || '' === $ip_address || empty( $ip_address ) ) {
			$request = wp_remote_get(
				'https://app.checkview.io/api/helper/container_ip',
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
			if ( ! empty( $data ) ) {
				$ip_address = $data['ipAddress'];
				set_transient( 'checkview_saas_ip_address', $ip_address, 12 * HOUR_IN_SECONDS );
			}
		}
		return $ip_address;
	}
}
if ( ! function_exists( 'whitelist_api_ip' ) ) {
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
}
if ( ! function_exists( 'must_ssl_url' ) ) {
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
}
if ( ! function_exists( 'get_visitor_ip' ) ) {
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
}
if ( ! function_exists( 'create_cv_session' ) ) {
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
		if ( count( $is_sub_directory ) > 1 ) {
			$current_url = $current_url . $request_uri;
		} else {
			$current_url = $request_uri;
		}

		$url         = explode( '?', $current_url );
		$current_url = $url[0];
		// Retrieve the current post's ID based on its URL.
		if ( $current_url ) {
			$page_id = get_page_by_path( $current_url );
			$page_id = $page_id->ID;
		} else {
			global $post;
			$page_id = $post->ID;
		}
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
}
if ( ! function_exists( 'get_cv_session' ) ) {
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
		$query         = 'Select * from ' . $session_table . ' where visitor_ip=%s and test_id=%s LIMIT 1';
		$result        = $wpdb->get_results( $wpdb->prepare( $query, $ip, $test_id ), ARRAY_A );
		return $result;
	}
}

if ( ! function_exists( 'get_wp_block_pages' ) ) {
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
}
if ( ! function_exists( 'checkview_reset_cache' ) ) {
	/**
	 * Updates cached data.
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
		$sync = true;
		return $sync;
	}
}

if ( ! function_exists( 'checkview_deslash' ) ) {
	/**
	 * Deslashed double slashes
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
	 * Whitelists SaaS site.
	 *
	 * @return bool
	 */
	function checkview_whitelist_saas_ip_addresses() {
		$api_ip = get_api_ip();
		if ( in_array( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '', array( $api_ip ), true ) ) {
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

if ( ! function_exists( 'delete_orders_from_backend' ) ) {
	/**
	 * Directly deletes orders.
	 *
	 * @return void
	 */
	function delete_orders_from_backend() {

		// don't run on ajax calls.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		return checkview_delete_orders();
	}
}

if ( ! function_exists( 'checkview_delete_orders' ) ) {
	/**
	 * Deletes Woocommerce orders.
	 *
	 * @param integer $order_id Woocommerce Order Id.
	 * @return bool
	 */
	function checkview_delete_orders( $order_id = '' ) {

		global $wpdb;
		// Get all checkview orders from wp tables legacy.
		$orders = $wpdb->get_results(
			"SELECT p.id
		FROM {$wpdb->prefix}posts as p
		LEFT JOIN {$wpdb->prefix}postmeta AS pm ON (p.id = pm.post_id AND pm.meta_key = '_payment_method')
		WHERE meta_value = 'checkview' "
		);
		if ( empty( $orders ) ) {
			$args = array(
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'OR', // Use 'AND' for both conditions to apply.
					array(
						'key'     => 'payment_made_by', // Meta key for payment method.
						'value'   => 'checkview', // Replace with your actual payment gateway ID.
						'compare' => '=', // Use '=' for exact match.
					),
				),
			);

			$orders = wc_get_orders( $args );
		}
		// Delete orders.
		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order ) {

				try {
					$order_object = new WC_Order( $order->id );
					$customer_id  = $order_object->get_customer_id();

					// Delete order.
					if ( $order_object ) {
						$order_object->delete( true );
						delete_transient( 'checkview_store_orders_transient' );
					}

					$order_object = null;
					$current_user = get_user_by( 'id', $customer_id );
					// Delete customer if available.
					if ( $customer_id && isset( $current_user->roles ) && ! in_array( 'administrator', $current_user->roles ) ) {
						$customer = new WC_Customer( $customer_id );

						if ( ! function_exists( 'wp_delete_user' ) ) {
							require_once ABSPATH . 'wp-admin/includes/user.php';
						}

						$res      = $customer->delete( true );
						$customer = null;
					}
				} catch ( \Exception $e ) {
					if ( ! class_exists( 'Checkview_Admin_Logs' ) ) {
						/**
						 * The class responsible for defining all actions that occur in the admin area.
						 */
						require_once CHECKVIEW_ADMIN_DIR . '/class-checkview-admin-logs.php';
					}
					Checkview_Admin_Logs::add( 'cron-logs', 'Crone job failed.' );
				}
			}
			return true;
		}
	}
}

if ( ! function_exists( 'checkview_add_custom_fields_after_purchase' ) ) {
	/**
	 * Adds custom fields after order status changes.
	 *
	 * @param int    $order_id order id.
	 * @param string $old_status order old status.
	 * @param string $new_status order new status.
	 * @return void
	 */
	function checkview_add_custom_fields_after_purchase( $order_id, $old_status, $new_status ) {
		if ( isset( $_COOKIE['checkview_test_id'] ) && '' !== $_COOKIE['checkview_test_id'] ) {
			$order = new WC_Order( $order_id );
			$order->update_meta_data( 'payment_made_by', 'checkview' );

			$order->update_meta_data( 'checkview_test_id', sanitize_text_field( wp_unslash( $_COOKIE['checkview_test_id'] ) ) );
			complete_checkview_test( sanitize_text_field( wp_unslash( $_COOKIE['checkview_test_id'] ) ) );

			$order->save();
			unset( $_COOKIE['checkview_test_id'] );
			setcookie( 'checkview_test_id', '', time() - 6600, COOKIEPATH, COOKIE_DOMAIN );

		}
	}
}

if ( ! function_exists( 'checkview_is_stripe_test_mode_configured' ) ) {
	/**
	 * Verifies if stripe is properly configured or not.
	 *
	 * @return bool/keys/string
	 */
	function checkview_is_stripe_test_mode_configured() {
		$stripe_settings = get_option( 'woocommerce_stripe_settings' );

		// Check if test publishable and secret keys are set.
		$test_publishable_key = isset( $stripe_settings['test_publishable_key'] ) ? $stripe_settings['test_publishable_key'] : '';
		$test_secret_key      = isset( $stripe_settings['test_secret_key'] ) ? $stripe_settings['test_secret_key'] : '';

		// Check if both test keys are set.
		$test_keys_set = ! empty( $test_publishable_key ) && ! empty( $test_secret_key );

		return $test_keys_set;
	}
}

if ( ! function_exists( 'get_active_payment_gateways' ) ) {
	/**
	 * Retrieve active payment gateways for stripe.
	 *
	 * @return array
	 */
	function get_active_payment_gateways() {
		$active_gateways  = array();
		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();
		foreach ( $payment_gateways as $gateway ) {
			if ( 'yes' === $gateway->settings['enabled'] ) {
				$active_gateways[ $gateway->id ] = $gateway->title;
			}
		}
		return $active_gateways;
	}
}
if ( ! function_exists( 'checkview_create_test_customer' ) ) {
	/**
	 * Creates a new test customer if one does not exist. Avoids flooding the DB with test customers.
	 *
	 * @return WC_Customer
	 */
	function checkview_create_test_customer() {
		$customer = checkview_get_test_customer();

		if ( false === $customer ) {
			$customer = new WC_Customer();
			$customer->set_username( uniqid( 'checkview_wc_automated_testing_' ) );
			$customer->set_password( wp_generate_password() );
			$customer->set_email( 'noreply@checkview.io' );
			$customer->set_display_name( 'CheckView WooCommerce Automated Testing User' );

			$customer_id = $customer->save();

			update_option( 'checkview_test_user', $customer_id );
		}

		return $customer;
	}
}
if ( ! function_exists( 'checkview_get_test_customer' ) ) {
	/**
	 * Retrieve the test customer.
	 *
	 * If the test user does not yet exist, return false.
	 *
	 * @return WC_Customer|false
	 */
	function checkview_get_test_customer() {
		$customer_id = get_option( 'checkview_test_user', false );

		if ( $customer_id ) {
			$customer = new WC_Customer( $customer_id );

			// WC_Customer will return a new customer with an ID of 0 if
			// one could not be found with the given ID.
			if ( is_a( $customer, 'WC_Customer' ) && 0 !== $customer->get_id() ) {
				return $customer;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'checkview_stop_registration_errors' ) ) {
	/**
	 * Prevent registration errors on WooCommerce registration.
	 * This serves to prevent captcha-related errors that break the test-user creation for WCAT.
	 *
	 * @param WP_Error $errors   Registration errors.
	 * @param string   $username Username for the registration.
	 * @param string   $email    Email for the registration.
	 *
	 * @return WP_Error
	 */
	function checkview_stop_registration_errors( $errors, $username, $email ) {
		// Check for our WCAT username and email.
		if ( false !== strpos( $username, 'checkview_wc_automated_testing_' )
		&& false !== strpos( $email, 'noreply@checkview.io' ) ) {
			// The default value for this in WC is a WP_Error object, so just reset it.
			$errors = new WP_Error();
		}
		return $errors;
	}
}
if ( ! function_exists( 'checkview_get_test_credentials' ) ) {
	/**
	 * Get credentials for the test user.
	 *
	 * It's important to note that every time this method is called the password for the test user
	 * will be reset. This is to prevent passwords from being stored in plain-text anywhere.
	 *
	 * @return string[] Credentials for the test user.
	 *
	 * @type string $email    The test user's email address.
	 * @type string $username The test user's username.
	 * @type string $password The newly-generated password for the test user.
	 */
	function checkview_get_test_credentials() {
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );

		$password = wp_generate_password();
		$customer = checkview_get_test_customer();

		if ( ! $customer ) {
			$customer = checkview_create_test_customer();
		}

		$customer->set_password( $password );
		$customer->save();

		// Schedule the password to be rotated 15min from now.
		checkview_rotate_password_cron();

		return array(
			'email'    => $customer->get_email(),
			'username' => $customer->get_username(),
			'password' => $password,
		);
	}
}

if ( ! function_exists( 'checkview_rotate_test_user_credentials' ) ) {
	/**
	 * Rotate the credentials for the test customer.
	 *
	 * This method should be called some amount of time after credentials have been shared with the
	 * test runner.
	 */
	function checkview_rotate_test_user_credentials() {
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );

		$customer = checkview_get_test_customer();

		if ( ! $customer ) {
			return;
		}

		$customer->set_password( wp_generate_password() );
		$customer->save();
	}
}

if ( ! function_exists( 'checkview_rotate_password_cron' ) ) {
	/**
	 * Schedules Cron for 15 minutes to update the User Password.
	 *
	 * @return void
	 */
	function checkview_rotate_password_cron() {
		wp_schedule_single_event( time() + 15 * MINUTE_IN_SECONDS, 'checkview_rotate_user_credentials' );
	}
}
if ( ! function_exists( 'get_woocommerce_cart_details' ) ) {
	/**
	 * Return cart details.
	 *
	 * @return bool/array
	 */
	function get_woocommerce_cart_details() {
		$url             = get_rest_url() . 'wc/v3/cart';
		$consumer_key    = 'ck_c0e08bbe91c3b0b85940b3005fd62782a7d91e67';
		$consumer_secret = 'cs_7e077b6af86eb443b9d2f0d6ca5f1fa986be7ee6';

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $consumer_key . ':' . $consumer_secret ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false; // Error occurred.
		}

		$body         = wp_remote_retrieve_body( $response );
		$cart_details = json_decode( $body, true );

		return $cart_details;
	}
}
if ( ! function_exists( 'checkview_get_test_product' ) ) {
	/**
	 * Retrieves details for test product.
	 *
	 * @return WC_Product/bool
	 */
	function checkview_get_test_product() {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( $product_id ) {
			try {
				$product = new WC_Product( $product_id );

				// In case WC_Product returns a new customer with an ID of 0 if
				// one could not be found with the given ID.
				if ( is_a( $product, 'WC_Product' ) && 0 !== $product->get_id() ) {
					return $product;
				}
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( \Exception $e ) {
				// The given test product was not valid, so we should fallback to the
				// default response if one was not found in the first place.
			}
		}

		return false;
	}
}
if ( ! function_exists( 'checkview_create_test_product' ) ) {
	/**
	 * Creates test product if one does not exist. Avoids flooding the DB with test products.
	 *
	 * @return WC_Product
	 */
	function checkview_create_test_product() {
		$product = checkview_get_test_product();

		if ( ! $product ) {
			$product = new WC_Product();
			$product->set_status( 'publish' );
			$product->set_name( 'WooCommerce Automated Testing Product' );
			$product->set_short_description( 'An example product for automated testing.' );
			$product->set_description( 'This is a placeholder product used for automatically testing your WooCommerce store. It\'s designed to be hidden from all customers.' );
			$product->set_regular_price( '1.00' );
			$product->set_price( '1.00' );
			$product->set_stock_status( 'instock' );
			$product->set_stock_quantity( 5 );
			$product->set_catalog_visibility( 'hidden' );

			// This filter is added here to prevent the WCAT test product from being publicized on creation.
			add_filter( 'publicize_should_publicize_published_post', '__return_false' );

			$product_id = $product->save();
			update_option( 'checkview_woo_product_id', $product_id, true );
		}

		return $product;
	}
}

if ( ! function_exists( 'checkview_seo_hide_product_from_sitemap' ) ) {
	/**
	 * Hide test product from Yoast sitemap. Takes $excluded_post_ids if any set, adds our $product_id to the array and
	 * returns the array.
	 *
	 * @param array $excluded_posts_ids post id's to be excluded.
	 *
	 * @return array[]
	 */
	function checkview_seo_hide_product_from_sitemap( $excluded_posts_ids = array() ) {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( $product_id ) {
			array_push( $excluded_posts_ids, $product_id );
		}

		return $excluded_posts_ids;
	}
}

if ( ! function_exists( 'checkview_hide_product_from_sitemap' ) ) {
	/**
	 * Hide test product from WordPress' sitemap.
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	function checkview_hide_product_from_sitemap( $args ) {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( $product_id ) {
			$args['post__not_in']   = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
			$args['post__not_in'][] = $product_id;
		}

		return $args;
	}
}

if ( ! function_exists( 'checkview_seo_hide_product_from_jetpack' ) ) {
	/**
	 * Hide test product from JetPack's Publicize module and from Jetpack Social.
	 *
	 * @param bool     $should_publicize bool type.
	 * @param \WP_Post $post WordPress post object.
	 *
	 * @return bool|array
	 */
	function checkview_seo_hide_product_from_jetpack( $should_publicize, $post ) {
		if ( $post ) {
			$product_id = get_option( 'checkview_woo_product_id' );

			if ( $product_id === $post->ID ) {
				return false;
			}
		}

		return $should_publicize;
	}
}
if ( ! function_exists( 'checkview_no_index_for_test_product' ) ) {
	/**
	 * Add noindex to the test product.
	 */
	function checkview_no_index_for_test_product() {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( is_int( $product_id ) && 0 !== $product_id && is_single( $product_id ) ) {
			echo '<meta name="robots" content="noindex, nofollow"/>';
		}
	}
}

if ( ! function_exists( 'add_states_to_locations' ) ) {
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
