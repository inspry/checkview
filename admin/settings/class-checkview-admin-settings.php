<?php
/**
 * CheckView Options
 *
 * Displays the CheckView Options.
 *
 * @author   CheckView
 * @category Admin
 * @package  Checkview/admin/settings/
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkview_Admin_Settings
 *
 * @since 1.0.0
 */
class Checkview_Admin_Settings {
	/**
	 * Contains the tab name for current settings tab.
	 *
	 * @var string
	 */
	public $page_tab;
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
	 * Hook in tabs.
	 *
	 * @param string $plugin_name name of the plugin.
	 * @param string $version version number of the plugin.
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->page_tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
	}

	/**
	 * Adds admin notices
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function checkview_admin_notices() {
		$nonce  = isset( $_POST['checkview_admin_advance_settings_action'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_admin_advance_settings_action'] ) ) : '';
		$action = 'checkview_admin_advance_settings_action';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( 'checkview-options' !== $screen->base && 'settings_page_checkview-options' !== $screen->base ) {
			return;
		}

		if ( isset( $_POST['checkview_settings_submit'] ) || ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) ) {
			$class   = 'notice notice-success is-dismissible';
			$message = esc_html__( 'Settings Saved', 'checkview' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		} elseif ( isset( $_POST['checkview_settings_submit'] ) || ( isset( $_GET['token-updated'] ) && 'true' === $_GET['token-updated'] ) ) {
			$class   = 'notice notice-success is-dismissible';
			$message = esc_html__( 'Settings Saved', 'checkview' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		} elseif ( isset( $_POST['checkview_advance_settings_submit'] ) || ( isset( $_GET['advance-settings-updated'] ) && 'true' === $_GET['advance-settings-updated'] ) ) {
			$class   = 'notice notice-success is-dismissible';
			$message = esc_html__( 'Settings Saved', 'checkview' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	/**
	 * Advance settings save
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function checkview_admin_advance_settings_save() {
		$uploads          = 'false';
		$checkview_option = get_option( 'checkview_advance_options', array() );

		$nonce  = isset( $_POST['checkview_admin_advance_settings_action'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_admin_advance_settings_action'] ) ) : '';
		$action = 'checkview_admin_advance_settings_action';
		if ( isset( $_POST['checkview_advance_settings_submit'] ) && wp_verify_nonce( $nonce, $action ) ) {
			$checkview_options = array();

			$del_data           = isset( $_POST['checkview_delete_data'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_delete_data'] ) ) : '';
			$allowed_extensions = isset( $_POST['checkview_allowed_extensions'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_allowed_extensions'] ) ) : '';

			$checkview_options['checkview_delete_data']        = sanitize_text_field( $del_data );
			$checkview_options['checkview_allowed_extensions'] = sanitize_text_field( $allowed_extensions );
			$checkview_options                                 = apply_filters( 'checkview_update_advance_options', $checkview_options );
			$ad_title = isset( $_POST['checkview_admin_menu_title'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_admin_menu_title'] ) ) : esc_html__( 'CheckView', 'checkview' );

			update_site_option( 'checkview_admin_menu_title', $ad_title );
			update_option( 'checkview_advance_options', $checkview_options );
			$uploads = 'true';
		} else {
			wp_safe_redirect( add_query_arg( 'nonce-verified', $uploads, isset( $_POST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : '' ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'advance-settings-updated', $uploads, isset( $_POST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : '' ) );
		exit;
	}

	/**
	 * Ajax function to sync cache.
	 *
	 * @since 1.0.0
	 */
	public function checkview_update_cache() {
		check_ajax_referer( 'checkview_reset_cache', '_nonce' );

		$data = checkview_reset_cache( true );
		if ( empty( $data ) || false === $data ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => esc_html__( 'Cache Could Not Be Updated.', 'checkview' ),
				)
			);
		} else {
			echo wp_json_encode(
				array(
					'success' => true,
					'message' => esc_html__( 'Cache updated Successfully.', 'checkview' ),
				)
			);
		}
		wp_die();
	}

	/**
	 * Ajax function to sync cache.
	 *
	 * @since 1.0.0
	 */
	public function checkview_update_cache_non_ajax() {
		checkview_reset_cache( true );
	}

	/**
	 * Add plugin's menu.
	 *
	 * @since 1.0.0
	 */
	public function checkview_menu() {
		$admin_menu_title = ! empty( get_site_option( 'checkview_admin_menu_title', 'CheckView' ) ) ? get_site_option( 'checkview_admin_menu_title', 'CheckView' ) : 'CheckView';
		add_options_page(
			esc_html( $admin_menu_title ),
			esc_html( $admin_menu_title ),
			'manage_options',
			'checkview-options',
			array( $this, 'checkview_options' )
		);
	}

	/**
	 * Setting page data
	 *
	 * @since 1.0.0
	 */
	public function checkview_options() {
		?>
		<div class="checkview-wrapper">
			<div class="inner-header">
				<div class="checkview-settings-wrapper d-flex align-items-center">
					<div class="d-flex align-items-center">
						<a href="https://checkview.io" target="_blank"><img src="<?php echo esc_html( CHECKVIEW_ADMIN_ASSETS ); ?>images/logo.svg" class="img-fluid main-logo" /></a>
					</div>
				</div>
			</div>

			<div class="checkview-settings-wrapper">
				<div id="icon-options-general" class="icon32"></div>
				<div class="checkview-tab-box">
					<div class="nav-tab-wrapper">
						<?php
						$checkview_sections = $this->checkview_get_setting_sections();
						foreach ( $checkview_sections as $key => $checkview_section ) {
							?>
							<a href="?page=checkview-options&tab=<?php echo esc_attr( $key ); ?>"
							class="nav-tab <?php echo $this->page_tab === $key ? 'nav-tab-active hero-active' : ''; ?>">
								<i class="fa <?php echo esc_html( $checkview_section['icon'] ); ?>" aria-hidden="true"></i>
								<?php echo esc_html( $checkview_section['title'] ); ?>
							</a>
							<?php
						}
						?>
					</div>
					<div class="checkview-tab-innerbox">

					<?php
					foreach ( $checkview_sections as $key => $checkview_section ) {
						if ( $this->page_tab === $key ) {

							$url = 'templates/' . $key . '.php';
							apply_filters( 'checkview_template_url', $url );
							include $url;
						}
					}
					?>
					</div>
				</div>
			</div>
		</div> 
		<?php
	}

	/**
	 * Template Hero Settings Sections
	 *
	 * @since 1.0.0
	 *
	 * @return mixed|void
	 */
	public function checkview_get_setting_sections() {

		$general = esc_html__( 'General', 'checkview' );
		$general = apply_filters( 'checkview_general_tab_title', $general );

		$logs = esc_html__( 'Logs & Error Files', 'checkview' );
		$logs = apply_filters( 'checkview_logs_tab_title', $logs );

		$api = esc_html__( 'API', 'checkview' );
		$api = apply_filters( 'checkview_logs_tab_title', $api );

		$checkview_settings_sections = array(
			'general' => array(
				'title' => $general,
				'icon'  => 'fa-hashtag',
			),
			'api'     => array(
				'title' => $api,
				'icon'  => 'fa-hashtag',
			),
			'logs'    => array(
				'title' => $logs,
				'icon'  => 'fa-hashtag',
			),

		);

		return apply_filters( 'checkview_settings_sections', $checkview_settings_sections );
	}

	/**
	 * Add footer branding
	 *
	 * @since 1.0.0
	 * @param string $footer_text footer text.
	 * @return mixed
	 */
	public function checkview_add_footer_admin( $footer_text ) {
		if ( isset( $_GET['page'] ) && ( 'checkview-options' === $_GET['page'] ) ) {
			return _e(
				'Powered by WordPress, Built & Supported by <a href="https://inspry.com" target="_blank">Inspry</a></p>',
				'checkview'
			);
		} else {
			return $footer_text;
		}
	}
}