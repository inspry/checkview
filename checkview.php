<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://checkview.io
 * @since             1.0.0
 * @package           CheckView
 *
 * @wordpress-plugin
 * Plugin Name:       CheckView
 * Plugin URI:        https://checkview.io
 * Description:       CheckView is the #1 fully automated solution to test your WordPress forms and detect form problems fast.  Automatically test your WordPress forms to ensure you never miss a lead again.
 * Version:           2.0.0
 * Author:            CheckView
 * Author URI:        https://checkview.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       checkview
 * WC requires at least: 7.0
 * WC tested up to: 8.3
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CHECKVIEW_VERSION', '2.0.0' );

/**
 * Define constant for plugin settings link
 */
if ( ! defined( 'CHECKVIEW_BASE_DIR' ) ) {
	define( 'CHECKVIEW_BASE_DIR', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'CHECKVIEW_PLUGIN_DIR' ) ) {
	define( 'CHECKVIEW_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'CHECKVIEW_INC_DIR' ) ) {
	define( 'CHECKVIEW_INC_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/' );
}

if ( ! defined( 'CHECKVIEW_PUBLIC_DIR' ) ) {
	define( 'CHECKVIEW_PUBLIC_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'public/' );
}

if ( ! defined( 'CHECKVIEW_ADMIN_DIR' ) ) {
	define( 'CHECKVIEW_ADMIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'admin/' );
}

if ( ! defined( 'CHECKVIEW_ADMIN_ASSETS' ) ) {
	define( 'CHECKVIEW_ADMIN_ASSETS', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'admin/assets/' );
}

if ( ! defined( 'CHECKVIEW_PUBLIC_ASSETS' ) ) {
	define( 'CHECKVIEW_PUBLIC_ASSETS', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'public/assets/' );
}

if ( ! defined( 'CHECKVIEW_EMAIL' ) ) {
	define( 'CHECKVIEW_EMAIL', 'verify@test-mail.checkview.io' );
}
if ( ! defined( 'CHECKVIEW_URI' ) ) {
	define( 'CHECKVIEW_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-checkview-activator.php
 */
function activate_checkview() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-checkview-activator.php';
	Checkview_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-checkview-deactivator.php
 */
function deactivate_checkview() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-checkview-deactivator.php';
	Checkview_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_checkview' );
register_deactivation_hook( __FILE__, 'deactivate_checkview' );

/**
 * Helper functions,
 */
require plugin_dir_path( __FILE__ ) . 'includes/checkview-helper-functions.php';
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-checkview.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_checkview() {
	$plugin = Checkview::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'run_checkview', '10' );
