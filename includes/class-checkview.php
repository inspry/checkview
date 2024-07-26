<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Checkview
 * @subpackage Checkview/includes
 * @author     Check View <support@checkview.io>
 */
class Checkview {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Checkview_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The single instance of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      class    $instance    The instance of the class.
	 */

	private static $instance = null;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CHECKVIEW_VERSION' ) ) {
			$this->version = CHECKVIEW_VERSION;
		} else {
			$this->version = '1.1.14';
		}
		$this->plugin_name = 'checkview';

		$this->load_dependencies();
		$this->define_public_hooks();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * The object is created from within the class itself.
	 * only if the class has no instance.
	 *
	 * @since    1.0.0
	 * @return   self   class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Checkview();
		}

		return self::$instance;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Checkview_Loader. Orchestrates the hooks of the plugin.
	 * - Checkview_i18n. Defines internationalization functionality.
	 * - Checkview_Admin. Defines all hooks for the admin area.
	 * - Checkview_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing JWT
		 * side of the site. Exposes the general functions.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/vendor/autoload.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site. Exposes the general functions.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/checkview-functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-checkview-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-checkview-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-checkview-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-checkview-admin-logs.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/settings/class-checkview-admin-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-checkview-public.php';
		$this->loader = new Checkview_Loader();
		// Current Vsitor IP.
		$visitor_ip = get_visitor_ip();
		// Check view Bot IP. Todo.
		$cv_bot_ip = get_api_ip();
		// $visitor_ip = $cv_bot_ip;
		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'checkview_cf7_helper' ) && ( 'checkview-saas' === get_option( $visitor_ip ) || isset( $_REQUEST['checkview_test_id'] ) || $visitor_ip === $cv_bot_ip ) ) {
			$send_to = 'verify@test-mail.checkview.io';
			// skip if visitor ip not equal to CV Bot IP.

			// if clean talk plugin active whitelist check form API IP. .
			if ( is_plugin_active( 'cleantalk-spam-protect/cleantalk.php' ) ) {
				whitelist_api_ip();
			}

			$cv_test_id = isset( $_REQUEST['checkview_test_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkview_test_id'] ) ) : '';

			$referrer_url = sanitize_url( wp_get_raw_referer(), array( 'http', 'https' ) );
				// If not Ajax submission and found test_id.
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'admin-ajax.php' ) === false && '' !== $cv_test_id ) {
				// Create session for later use when form submit VIA AJAX.
				create_cv_session( $visitor_ip, $cv_test_id );
				update_option( $visitor_ip, 'checkview-saas', true );
			}
				// If submit VIA AJAX.
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'admin-ajax.php' ) !== false ) {
				$referer_url_query = wp_parse_url( $referrer_url, PHP_URL_QUERY );
				$qry_str           = array();
				parse_str( $referer_url_query, $qry_str );
				$cv_test_id = $qry_str['checkview_test_id'];
			}

			$cv_session = get_cv_session( $visitor_ip, $cv_test_id );

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
			require_once CHECKVIEW_INC_DIR . 'formhelpers/class-checkview-cf7-helper.php';
		}
		$woo_helper = '';
		if ( class_exists( 'WooCommerce' ) ) {
			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site. Exposes the API end points.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'includes/woocommercehelper/class-checkview-woo-automated-testing.php';
			$woo_helper = new Checkview_Woo_Automated_Testing( $this->get_plugin_name(), $this->get_version(), $this->loader );
		}
		$this->loader->add_filter(
			'plugin_action_links_' . CHECKVIEW_BASE_DIR,
			$this,
			'checkview_settings_link'
		);
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site. Exposes the API end points.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/API/class-checkview-api.php';
		$plugin_api = new CheckView_Api( $this->get_plugin_name(), $this->get_version(), $woo_helper );
		$this->loader->add_action(
			'rest_api_init',
			$plugin_api,
			'checkview_register_rest_route'
		);
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Checkview_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
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
	 * Add settings link on plugin page.
	 *
	 * @since  1.0.0
	 * @param array $links href to settings pages.
	 * @return $links href to settings pages.
	 */
	public function checkview_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=checkview-options">' . esc_html__( 'Settings', 'checkview' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
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
		$this->loader->add_filter(
			'option_active_plugins',
			$plugin_admin,
			'checkview_disable_unwanted_plugins',
			99,
			1
		);
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
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
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
		$visitor_ip = get_visitor_ip();
		// Check view Bot IP. Todo.
		$cv_bot_ip = get_api_ip();
		// procceed if visitor ip is equal to cv bot ip. Todo.
		if ( $visitor_ip === $cv_bot_ip ) {
			$this->loader->add_action(
				'pre_option_require_name_email',
				'',
				'checkview_whitelist_saas_ip_addresses'
			);
		}
	}
	/**
	 * Tracks core version updates.
	 *
	 * @param [object] $upgrader_object class upgrader.
	 * @param [array]  $options array.
	 * @return void
	 */
	public function checkview_track_updates_notification( $upgrader_object, $options ) {

		// If an update has taken place and the updated type is plugins and the plugins element exists.
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			// Iterate through the plugins being updated and check if ours is there.
			foreach ( $options['plugins'] as $plugin ) {
				if ( CHECKVIEW_BASE_DIR === $plugin ) {
					// Your action if it is your plugin.
					checkview_reset_cache( true );
				}
			}
		}
	}
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Checkview_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
