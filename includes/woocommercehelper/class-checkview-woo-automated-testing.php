<?php
/**
 * Hanldes Checkview WooCommerce automatted testing options.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    CheckView
 * @subpackage CheckView/includes/woocommercehelper
 */

use WC_Customer;
use WC_Data_Store;
use WC_Order;
use WC_Product;
use WP_Error;

/**
 * Integration for the WooCommerce Automated Testing system.
 */
class Checkview_Woo_Automated_Testing {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The loader hooks of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool/class    $loader    The hooks loader of this plugin.
	 */
	private $loader;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 * @param      string $loader    Loads the hooks.
	 */
	public function __construct( $plugin_name, $version, $loader ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->loader      = $loader;
		if ( $this->loader ) {
			$this->loader->add_action(
				'init',
				'',
				'checkview_create_test_product',
			);

			$this->loader->add_action(
				'wp_head',
				'',
				'checkview_no_index_for_test_product',
			);

			$this->loader->add_filter(
				'wpseo_exclude_from_sitemap_by_post_ids',
				'',
				'checkview_seo_hide_product_from_sitemap',
			);

			$this->loader->add_filter(
				'wp_sitemaps_posts_query_args',
				'',
				'checkview_hide_product_from_sitemap',
			);

			$this->loader->add_filter(
				'publicize_should_publicize_published_post',
				'',
				'checkview_seo_hide_product_from_jetpack',
			);
		}
	}


	/**
	 * Retrieve active payment gateways for stripe.
	 *
	 * @return array
	 */
	public function get_active_payment_gateways() {
		$active_gateways  = array();
		$payment_gateways = WC_Payment_Gateways::instance()->payment_gateways();
		foreach ( $payment_gateways as $gateway ) {
			if ( 'yes' === $gateway->settings['enabled'] ) {
				$active_gateways[ $gateway->id ] = $gateway->title;
			}
		}
		return $active_gateways;
	}


	/**
	 * Creates a new test customer if one does not exist. Avoids flooding the DB with test customers.
	 *
	 * @return WC_Customer
	 */
	public function checkview_create_test_customer() {
		$customer = $this->checkview_get_test_customer();

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


	/**
	 * Retrieve the test customer.
	 *
	 * If the test user does not yet exist, return false.
	 *
	 * @return WC_Customer|false
	 */
	public function checkview_get_test_customer() {
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
	public function checkview_stop_registration_errors( $errors, $username, $email ) {
		// Check for our WCAT username and email.
		if ( false !== strpos( $username, 'checkview_wc_automated_testing_' )
		&& false !== strpos( $email, 'noreply@checkview.io' ) ) {
			// The default value for this in WC is a WP_Error object, so just reset it.
			$errors = new WP_Error();
		}
		return $errors;
	}

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
	public function checkview_get_test_credentials() {
			add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );

			$password = wp_generate_password();
			$customer = $this->checkview_get_test_customer();

		if ( ! $customer ) {
			$customer = $this->checkview_create_test_customer();
		}

			$customer->set_password( $password );
			$customer->save();

			// Schedule the password to be rotated 15min from now.
			$this->checkview_rotate_password_cron();

			return array(
				'email'    => $customer->get_email(),
				'username' => $customer->get_username(),
				'password' => $password,
			);
	}

	/**
	 * Rotate the credentials for the test customer.
	 *
	 * This method should be called some amount of time after credentials have been shared with the
	 * test runner.
	 */
	public function checkview_rotate_test_user_credentials() {
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );

		$customer = $this->checkview_get_test_customer();

		if ( ! $customer ) {
			return;
		}

		$customer->set_password( wp_generate_password() );
		$customer->save();
	}

	/**
	 * Schedules Cron for 15 minutes to update the User Password.
	 *
	 * @return void
	 */
	public function checkview_rotate_password_cron() {
		wp_schedule_single_event( time() + 15 * MINUTE_IN_SECONDS, 'checkview_rotate_user_credentials' );
	}

	/**
	 * Return cart details.
	 *
	 * @return bool/array
	 */
	public function get_woocommerce_cart_details() {
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

	/**
	 * Retrieves details for test product.
	 *
	 * @return WC_Product/bool
	 */
	public function checkview_get_test_product() {
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

	/**
	 * Creates test product if one does not exist. Avoids flooding the DB with test products.
	 *
	 * @return WC_Product
	 */
	public function checkview_create_test_product() {
		$product = $this->checkview_get_test_product();

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

	/**
	 * Hide test product from Yoast sitemap. Takes $excluded_post_ids if any set, adds our $product_id to the array and
	 * returns the array.
	 *
	 * @param array $excluded_posts_ids post id's to be excluded.
	 *
	 * @return array[]
	 */
	public function checkview_seo_hide_product_from_sitemap( $excluded_posts_ids = array() ) {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( $product_id ) {
			array_push( $excluded_posts_ids, $product_id );
		}

		return $excluded_posts_ids;
	}

	/**
	 * Hide test product from WordPress' sitemap.
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	public function checkview_hide_product_from_sitemap( $args ) {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( $product_id ) {
			$args['post__not_in']   = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
			$args['post__not_in'][] = $product_id;
		}

		return $args;
	}

	/**
	 * Hide test product from JetPack's Publicize module and from Jetpack Social.
	 *
	 * @param bool     $should_publicize bool type.
	 * @param \WP_Post $post WordPress post object.
	 *
	 * @return bool|array
	 */
	public function checkview_seo_hide_product_from_jetpack( $should_publicize, $post ) {
		if ( $post ) {
			$product_id = get_option( 'checkview_woo_product_id' );

			if ( $product_id === $post->ID ) {
				return false;
			}
		}

		return $should_publicize;
	}

	/**
	 * Add noindex to the test product.
	 */
	public function checkview_no_index_for_test_product() {
		$product_id = get_option( 'checkview_woo_product_id' );

		if ( is_int( $product_id ) && 0 !== $product_id && is_single( $product_id ) ) {
			echo '<meta name="robots" content="noindex, nofollow"/>';
		}
	}
}
