<?php
/**
 * CheckView core: Checkview class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/includes
 */

/**
 * Loads plugin dependencies and files, runs hooks.
 *
 * @since 1.0.0
 * @package Checkview
 * @subpackage Checkview/includes
 * @author Check View <support@checkview.io>
 */
class Checkview {

	/**
	 * Hook loader.
	 *
	 * @since 1.0.0
	 * @access protected
	 * 
	 * @var Checkview_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Plugin name.
	 *
	 * @since 1.0.0
	 * @access protected
	 * 
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @access protected
	 * 
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Class singleton.
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @var class $instance The instance of the class.
	 */

	private static $instance = null;

	/**
	 * Constructor.
	 * 
	 * Sets up class properties, loads dependencies, and hooks up functions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'CHECKVIEW_VERSION' ) ) {
			$this->version = CHECKVIEW_VERSION;
		} else {
			$this->version = '2.0.1';
		}
		$this->plugin_name = 'checkview';

		$this->load_dependencies();
		$this->define_public_hooks();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Gets the instance of this class.
	 * 
	 * Creates an instance of itself if there was not one found before returning.
	 *
	 * @since 1.0.0
	 * 
	 * @return self Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Checkview();
		}

		return self::$instance;
	}

	/**
	 * Loads plugin dependencies.
	 * 
	 * Loads WordPress Core dependenceis, vendor files, and CheckView classes.
	 * Additionally sets up more class properties, conditionally loads WooCommerce
	 * helper, adds admin plugin list action links, and initializes the API class.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// WordPress Core
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// WordPress Core
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		// Vendor
		require_once plugin_dir_path( __DIR__ ) . 'includes/vendor/autoload.php';

		// CheckView
		require_once plugin_dir_path( __DIR__ ) . 'includes/checkview-functions.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-checkview-loader.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-checkview-i18n.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-checkview-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-checkview-admin-logs.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/settings/class-checkview-admin-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/class-checkview-public.php';

		$this->loader = new Checkview_Loader();

		// Current Vsitor IP.
		$visitor_ip = checkview_get_visitor_ip();

		// Check view Bot IP.
		$cv_bot_ip = checkview_get_api_ip();

		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'checkview_cf7_helper' ) && ( 'checkview-saas' === get_option( $visitor_ip ) || isset( $_REQUEST['checkview_test_id'] ) || ( is_array( $cv_bot_ip ) && in_array( $visitor_ip, $cv_bot_ip ) ) ) ) {
			$send_to               = CHECKVIEW_EMAIL;
			return;
			// if clean talk plugin active whitelist check form API IP. .
			if ( is_plugin_active( 'cleantalk-spam-protect/cleantalk.php' ) ) {
				checkview_whitelist_api_ip();
			}

			$cv_test_id = isset( $_REQUEST['checkview_test_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkview_test_id'] ) ) : '';

			$referrer_url = sanitize_url( wp_get_raw_referer(), array( 'http', 'https' ) );
				// If not Ajax submission and found test_id.
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'admin-ajax.php' ) === false && '' !== $cv_test_id ) {
				// Create session for later use when form submit VIA AJAX.
				checkview_create_cv_session( $visitor_ip, $cv_test_id );
				update_option( $visitor_ip, 'checkview-saas', true );
			}
			if ( $cv_test_id && '' !== $cv_test_id ) {
				setcookie( 'checkview_test_id' . $cv_test_id, $cv_test_id, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
			}
			// If submit VIA AJAX.
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'admin-ajax.php' ) !== false ) {
				$referer_url_query = wp_parse_url( $referrer_url, PHP_URL_QUERY );
				$qry_str           = array();
				parse_str( $referer_url_query, $qry_str );
				if ( ! empty( $qry_str['checkview_test_id'] ) ) {
					$cv_test_id = $qry_str['checkview_test_id'];
				}
			}

			$cv_session = checkview_get_cv_session( $visitor_ip, $cv_test_id );

			// stop if session not found.
			if ( ! empty( $cv_session ) ) {

				$test_key = $cv_session[0]['test_key'];

				$test_form = get_option( $test_key, '' );

				if ( ! empty( $test_form ) ) {
					$test_form = json_decode( $test_form, true );
				}

				if ( isset( $test_form['send_to'] ) && '' !== $test_form['send_to'] ) {
					$send_to = $test_form['send_to'];
				}

				if ( ! defined( 'TEST_EMAIL' ) ) {
					define( 'TEST_EMAIL', $send_to );
				}

				if ( ! defined( 'CV_TEST_ID' ) ) {
					define( 'CV_TEST_ID', $cv_test_id );
				}
				delete_transient( 'checkview_forms_test_transient' );
			}
			if ( ! defined( 'TEST_EMAIL' ) ) {
				define( 'TEST_EMAIL', $send_to );
			}
			if ( ! defined( 'CV_DISABLE_EMAIL_RECEIPT' ) && $disable_email_receipt ) {
				define( 'CV_DISABLE_EMAIL_RECEIPT', 'true' );
			}
			require_once CHECKVIEW_INC_DIR . 'formhelpers/class-checkview-cf7-helper.php';
		}
		$woo_helper = '';
		if ( class_exists( 'WooCommerce' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/woocommercehelper/class-checkview-woo-automated-testing.php';
			$woo_helper = new Checkview_Woo_Automated_Testing( $this->get_plugin_name(), $this->get_version(), $this->loader );
		}
		$this->loader->add_filter(
			'plugin_action_links_' . CHECKVIEW_BASE_DIR,
			$this,
			'checkview_settings_link'
		);

		// Require API class.
		require_once plugin_dir_path( __DIR__ ) . 'includes/API/class-checkview-api.php';

		// Initialize the plugin's API.
		$plugin_api = new CheckView_Api( $this->get_plugin_name(), $this->get_version(), $woo_helper );

		// Hook our routes into WordPress.
		$this->loader->add_action(
			'rest_api_init',
			$plugin_api,
			'checkview_register_rest_route'
		);
	}

	/**
	 * Sets up i18n.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new Checkview_i18n();
		$plugin_i18n->load_plugin_textdomain();
		add_action(
			'plugins_loaded',
			array( $plugin_i18n, 'load_plugin_textdomain' )
		);
	}

	/**
	 * Adds a "Settings" link to admin plugin list page.
	 *
	 * @since 1.0.0
	 * 
	 * @param array $links The `href` value for settings pages.
	 * @return array Modified array of plugin action links with the "Settings" link included.
	 */
	public function checkview_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=checkview-options">' . esc_html__( 'Settings', 'checkview' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	/**
	 * Sets up admin classes and hooks.
	 * 
	 * Initializes various admin classes and hooks up methods from those classes.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		$plugin_admin    = new Checkview_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings = new Checkview_Admin_Settings( $this->get_plugin_name(), $this->get_version() );
		$plugin_logs     = new Checkview_Admin_Logs();
		if ( is_admin() ) {
			// load backend hooks.
			$this->loader->add_action(
				'admin_post_checkview_admin_logs_settings',
				$plugin_logs,
				'checkview_admin_logs_settings_save'
			);
			$this->loader->add_action(
				'admin_footer_text',
				$plugin_settings,
				'checkview_add_footer_admin'
			);
			$this->loader->add_action(
				'admin_post_checkview_admin_advance_settings',
				$plugin_settings,
				'checkview_admin_advance_settings_save'
			);

			$this->loader->add_action(
				'wp_ajax_checkview_update_cache',
				$plugin_settings,
				'checkview_update_cache'
			);

			$this->loader->add_action(
				'admin_enqueue_scripts',
				$plugin_admin,
				'enqueue_styles'
			);

			$this->loader->add_action(
				'admin_enqueue_scripts',
				$plugin_admin,
				'enqueue_scripts'
			);

			$this->loader->add_action(
				'admin_menu',
				$plugin_settings,
				'checkview_menu',
				220
			);

			$this->loader->add_action(
				'admin_notices',
				$plugin_settings,
				'checkview_admin_notices'
			);

			$this->loader->add_action(
				'save_post',
				$plugin_settings,
				'checkview_update_cache_non_ajax',
				11,
				3
			);
		}
		$this->loader->add_action(
			'init',
			$plugin_admin,
			'checkview_init_current_test'
		);
		$this->loader->add_action(
			'upgrader_process_complete',
			$this,
			'checkview_track_updates_notification',
			10,
			2,
		);
	}

	/**
	 * Sets up public classes and hooks.
	 * 
	 * Initializes various public classes and hooks up methods from those classes.
	 * 
	 * @since 1.0.0
	 * @access private
	 */
	private function define_public_hooks() {

		$plugin_public = new Checkview_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action(
			'wp_enqueue_scripts',
			$plugin_public,
			'enqueue_styles'
		);

		$this->loader->add_action(
			'wp_enqueue_scripts',
			$plugin_public,
			'enqueue_scripts'
		);

		// Current Vsitor IP.
		$visitor_ip = checkview_get_visitor_ip();
		// Check view Bot IP.
		$cv_bot_ip = checkview_get_api_ip();

		// Proceed if visitor IP is in SaaS IPs.
		if ( is_array( $cv_bot_ip ) && in_array( $visitor_ip, $cv_bot_ip ) ) {
			$this->loader->add_action(
				'pre_option_require_name_email',
				'',
				'checkview_whitelist_saas_ip_addresses'
			);
		}
	}
	/**
	 * Resets plugin cache if being updated.
	 *
	 * @param object $upgrader_object Class upgrader.
	 * @param array $options Options.
	 * @return void
	 */
	public function checkview_track_updates_notification( $upgrader_object, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( CHECKVIEW_BASE_DIR === $plugin ) {
					checkview_reset_cache( true );
				}
			}
		}
	}
	/**
	 * Hooks up the actions and filters stored in the loader.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Gets the plugin name.
	 *
	 * @since 1.0.0
	 * 
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Gets the loader.
	 *
	 * @since 1.0.0
	 * 
	 * @return Checkview_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Gets the plugin version.
	 *
	 * @since 1.0.0
	 * 
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
