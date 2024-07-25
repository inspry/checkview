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
				$this,
				'checkview_create_test_product',
			);
			// $this->loader->add_action(
			// 'init',
			// $this,
			// 'checkview_create_test_customer',
			// );
			$this->loader->add_action(
				'template_redirect',
				$this,
				'checkview_empty_woocommerce_cart_if_parameter',
			);

			$this->loader->add_action(
				'wp_head',
				$this,
				'checkview_no_index_for_test_product',
			);

			$this->loader->add_filter(
				'wpseo_exclude_from_sitemap_by_post_ids',
				$this,
				'checkview_seo_hide_product_from_sitemap',
			);

			$this->loader->add_filter(
				'wp_sitemaps_posts_query_args',
				$this,
				'checkview_hide_product_from_sitemap',
			);

			$this->loader->add_filter(
				'publicize_should_publicize_published_post',
				$this,
				'checkview_seo_hide_product_from_jetpack',
			);

			$this->loader->add_filter(
				'woocommerce_webhook_should_deliver',
				$this,
				'checkview_filter_webhooks',
				10,
				3
			);

			$this->loader->add_filter(
				'woocommerce_email_recipient_new_order',
				$this,
				'checkview_filter_admin_emails',
				10,
				3
			);

			$this->loader->add_action(
				'checkview_delete_orders_action',
				$this,
				'checkview_delete_orders',
				10,
				1
			);
			$this->loader->add_action(
				'checkview_rotate_user_credentials',
				$this,
				'checkview_rotate_test_user_credentials',
				10,
			);

			$this->loader->add_filter(
				'woocommerce_registration_errors',
				$this,
				'checkview_stop_registration_errors',
				15,
				3
			);

			// Delete orders on backend page load if crons are disabled.
			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				$this->loader->add_action(
					'admin_init',
					$this,
					'delete_orders_from_backend',
				);
			}

			$this->loader->add_filter(
				'woocommerce_can_reduce_order_stock',
				$this,
				'checkview_maybe_not_reduce_stock',
				10,
				2
			);

			$this->loader->add_filter(
				'woocommerce_prevent_adjust_line_item_product_stock',
				$this,
				'checkview_woocommerce_prevent_adjust_line_item_product_stock',
				10,
				3
			);
		}
		$this->checkview_test_mode();
	}

	/**
	 * Empties the Cart before sessions inits.
	 *
	 * @return void
	 */
	public function checkview_empty_woocommerce_cart_if_parameter() {
		// Check if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			// Check if the parameter exists in the URL.
			if ( isset( $_GET['checkview_empty_cart'] ) && 'true' === $_GET['checkview_empty_cart'] && ( is_product() || is_shop() ) ) {
				// Get WooCommerce cart instance.
				$woocommerce_instance = WC();
				// Check if the cart is not empty.
				if ( ! $woocommerce_instance->cart->is_empty() ) {
					// Clear the cart.
					$woocommerce_instance->cart->empty_cart();
				}
			}
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
		$email    = 'verify@test-mail.checkview.io';

		if ( false === $customer || empty( $customer ) ) {
			// Get user object by email.
			$customer = get_user_by( 'email', $email );
			if ( $customer ) {
				update_option( 'checkview_test_user', $customer->ID );
				return $customer;
			}
			$customer = new WC_Customer();
			$customer->set_username( uniqid( 'checkview_wc_automated_testing_' ) );
			$customer->set_password( wp_generate_password() );
			$customer->set_email( 'verify@test-mail.checkview.io' );
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
		&& false !== strpos( $email, 'verify@test-mail.checkview.io' ) ) {
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
			return false;
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
			$product->set_name( 'CheckView Testing Product' );
			$product->set_short_description( 'An example product for automated testing.' );
			$product->set_description( 'This is a placeholder product used for automatically testing your WooCommerce store. It\'s designed to be hidden from all customers.' );
			$product->set_regular_price( '1.00' );
			$product->set_price( '1.00' );
			$product->set_stock_status( 'instock' );
			$product->set_stock_quantity( 5 );
			$product->set_catalog_visibility( 'hidden' );
			// Set weight and dimensions.
			$product->set_weight( '1' ); // 1 ounce in pounds.
			$product->set_length( '1' ); // Length in store units (e.g., inches, cm).
			$product->set_width( '1' ); // Width in store units (e.g., inches, cm).
			$product->set_height( '1' ); // Height in store units (e.g., inches, cm).
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

	/**
	 * Turns test mode on.
	 *
	 * @return void
	 */
	public function checkview_test_mode() {

		// Current Vsitor IP.
		$visitor_ip = get_visitor_ip();
		// Check view Bot IP. Todo.
		$cv_bot_ip = get_api_ip();
		if ( ! is_admin() && class_exists( 'WooCommerce' ) && ( 'checkview-saas' === get_option( $visitor_ip ) || isset( $_REQUEST['checkview_test_id'] ) || $visitor_ip === $cv_bot_ip ) ) {
			if ( ( isset( $_GET['checkview_use_stripe'] ) && 'yes' === sanitize_text_field( wp_unslash( $_GET['checkview_use_stripe'] ) ) ) || 'yes' === get_option( $visitor_ip . 'use_stripe' ) ) {
				// Always use Stripe test mode when on dev or staging.
				add_filter(
					'option_woocommerce_stripe_settings',
					function ( $value ) {

						$value['testmode'] = 'yes';

						return $value;
					}
				);
				add_filter(
					'cfturnstile_whitelisted',
					'__return_true',
					999
				);
			} elseif ( ( isset( $_GET['checkview_use_stripe'] ) && 'no' === sanitize_text_field( wp_unslash( $_GET['checkview_use_stripe'] ) ) ) || 'no' === get_option( $visitor_ip . 'use_stripe' ) ) {
				// Load payment gateway.
				require_once CHECKVIEW_INC_DIR . 'woocommercehelper/class-checkview-payment-gateway.php';

				// Add fake payment gateway for checkview tests.
				$this->loader->add_filter(
					'woocommerce_payment_gateways',
					$this,
					'checkview_add_payment_gateway',
					11,
					1
				);

				if ( isset( $_REQUEST['checkview_test_id'] ) ) {
					// Registers WooCommerce Blocks integration.
					$this->loader->add_action(
						'woocommerce_blocks_loaded',
						$this,
						'checkview_woocommerce_block_support',
					);
				}
				add_filter(
					'cfturnstile_whitelisted',
					'__return_true',
					999
				);
			}
			// Make the test product visible in the catalog.
			add_filter(
				'woocommerce_product_is_visible',
				function ( $visible, $product_id ) {
					$product = $this->checkview_get_test_product();

					if ( ! $product ) {
						return false;
					}

					return $product_id === $product->get_id() ? true : $visible;
				},
				9999,
				2
			);
			$this->loader->add_action(
				'woocommerce_order_status_changed',
				$this,
				'checkview_add_custom_fields_after_purchase',
				10,
				3
			);
			// bypass hcaptcha.
			add_filter( 'hcap_activate', array( $this, '__return_false' ), -999, 1 );
		}
	}
	public function __return_false( $activate ) {
		return false;
	}
	/**
	 * Disable admin notifications on checkview checks.
	 *
	 * @param string   $recipient recipient.
	 * @param Wc_order $order WooCommerce order.
	 * @param Email    $self WooCommerce Email object.
	 * @return string
	 */
	public function checkview_filter_admin_emails( $recipient, $order, $self ) {

		$payment_method  = ( \is_object( $order ) && \method_exists( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : false;
		$payment_made_by = is_object( $order ) ? $order->get_meta( 'payment_made_by' ) : '';
		$visitor_ip      = get_visitor_ip();
		// Check view Bot IP. Todo.
		$cv_bot_ip = get_api_ip();
		if ( ( 'checkview-saas' === get_option( $visitor_ip ) || isset( $_REQUEST['checkview_test_id'] ) || $visitor_ip === $cv_bot_ip ) || ( 'checkview' === $payment_method || 'checkview' === $payment_made_by ) ) {
			return 'verify@test-mail.checkview.io';
		}

		return $recipient;
	}


	/**
	 * Disable webhooks on checkview checks.
	 *
	 * @param bool   $should_deliver delivery status.
	 * @param object $webhook_object wenhook object.
	 * @param array  $arg args to support.
	 * @return bool
	 */
	public function checkview_filter_webhooks( $should_deliver, $webhook_object, $arg ) {

		$topic = $webhook_object->get_topic();

		if ( ! empty( $topic ) && ! empty( $arg ) && 'order.' === substr( $topic, 0, 6 ) ) {

			$order = wc_get_order( $arg );

			if ( ! empty( $order ) ) {
				$payment_method  = ( \is_object( $order ) && \method_exists( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : false;
				$payment_made_by = $order->get_meta( 'payment_made_by' );
				if ( ( $payment_method && 'checkview' === $payment_method ) || ( 'checkview' === $payment_made_by ) ) {
					return false;
				}
			}
		} elseif ( ! empty( $topic ) && ! empty( $arg ) && 'subscription.' === substr( $topic, 0, 13 ) ) {

			$order = wc_get_order( $arg );

			if ( ! empty( $order ) ) {
				$payment_method  = ( \is_object( $order ) && \method_exists( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : false;
				$payment_made_by = is_object( $order ) ? $order->get_meta( 'payment_made_by' ) : '';
				if ( ( $payment_method && 'checkview' === $payment_method ) || ( 'checkview' === $payment_made_by ) ) {
					return false;
				}
			}
		}

		return $should_deliver;
	}

	/**
	 * Adds checkview payment gateway to WooCommerce.
	 *
	 * @param string $methods methods to add payments.
	 * @return array $methods
	 */
	public function checkview_add_payment_gateway( $methods ) {
		$methods[] = 'Checkview_Payment_Gateway';
		return $methods;
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 * @return void
	 */
	public function checkview_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			// Load block payment gateway.
			require_once CHECKVIEW_INC_DIR . 'woocommercehelper/class-checkview-blocks-payment-gateway.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new Checkview_Blocks_Payment_Gateway() );
				}
			);
		}
	}


	/**
	 * Directly deletes orders.
	 *
	 * @return void
	 */
	public function delete_orders_from_backend() {

		// don't run on ajax calls.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		return $this->checkview_delete_orders();
	}

	/**
	 * Deletes Woocommerce orders.
	 *
	 * @param integer $order_id Woocommerce Order Id.
	 * @return bool
	 */
	public function checkview_delete_orders( $order_id = '' ) {

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
				'limit'          => -1,
				'payment_method' => 'checkview',
				'meta_query'     => array(
					array(
						'relation' => 'AND', // Use 'AND' for both conditions to apply.
						array(
							'key'     => 'payment_made_by', // Meta key for payment method.
							'value'   => 'checkview', // Replace with your actual payment gateway ID.
							'compare' => '=', // Use '=' for exact match.
						),
					),
				),
			);
			if ( function_exists( 'wc_get_orders' ) ) {
				$orders = wc_get_orders( $args );
			}
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

	/**
	 * Adds custom fields after order status changes.
	 *
	 * @param int    $order_id order id.
	 * @param string $old_status order old status.
	 * @param string $new_status order new status.
	 * @return void
	 */
	public function checkview_add_custom_fields_after_purchase( $order_id, $old_status, $new_status ) {
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

	/**
	 * Verifies if stripe is properly configured or not.
	 *
	 * @return bool/keys/string
	 */
	public function checkview_is_stripe_test_mode_configured() {
		$stripe_settings = get_option( 'woocommerce_stripe_settings' );

		// Check if test publishable and secret keys are set.
		$test_publishable_key = isset( $stripe_settings['test_publishable_key'] ) ? $stripe_settings['test_publishable_key'] : '';
		$test_secret_key      = isset( $stripe_settings['test_secret_key'] ) ? $stripe_settings['test_secret_key'] : '';

		// Check if both test keys are set.
		$test_keys_set = ! empty( $test_publishable_key ) && ! empty( $test_secret_key );

		return $test_keys_set;
	}

	/**
	 * Make sure we don't reduce the stock levels of products for test orders.
	 *
	 * @since 1.5.2
	 * @param bool     $reduce_stock true/false.
	 * @param WP_Order $order wc order.
	 * @return bool
	 */
	public static function checkview_maybe_not_reduce_stock( $reduce_stock, $order ) {
		if ( $reduce_stock && is_object( $order ) && $order->get_billing_email() ) {
			$billing_email = $order->get_billing_email();

			if ( preg_match( '/store[\+]guest[\-](\d+)[\@]checkview.io/', $billing_email ) || preg_match( '/store[\+](\d+)[\@]checkview.io/', $billing_email ) ) {
				$reduce_stock = false;
			}

			$payment_method  = ( \is_object( $order ) && \method_exists( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : false;
			$payment_made_by = $order->get_meta( 'payment_made_by' );
			if ( ( $payment_method && 'checkview' === $payment_method ) || ( 'checkview' === $payment_made_by ) ) {
				$reduce_stock = false;
			}
		}

		return $reduce_stock;
	}

	/**
	 * Prevent adjust line item
	 *
	 * @param [bool]  $prevent bool true/false.
	 * @param wc_itme $item item in order.
	 * @param int     $quantity quaniity if item.
	 */
	public function checkview_woocommerce_prevent_adjust_line_item_product_stock( $prevent, $item, $quantity ) {
		// Get order.
		$order         = $item->get_order();
		$billing_email = $order->get_billing_email();

		if ( preg_match( '/store[\+]guest[\-](\d+)[\@]checkview.io/', $billing_email ) || preg_match( '/store[\+](\d+)[\@]checkview.io/', $billing_email ) ) {
			$prevent = true;
		}

		$payment_method  = ( \is_object( $order ) && \method_exists( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : false;
		$payment_made_by = $order->get_meta( 'payment_made_by' );
		if ( ( $payment_method && 'checkview' === $payment_method ) || ( 'checkview' === $payment_made_by ) ) {
			$prevent = true;
		}

		return $prevent;
	}
}
