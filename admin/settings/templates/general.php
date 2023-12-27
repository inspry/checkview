<?php
/**
 * General Options
 *
 * @package General options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$checkview_options = get_option( 'checkview_options', array() );
$current_site_url  = trim( get_option( 'checkview_current_url', '' ) );
$remote_site_name  = ! empty( $checkview_options['checkview_remote_name'] ) ? $checkview_options['checkview_remote_name'] : '';

$network_wide = 'no';
$make_live    = 'no';
global $wpdb;


$args = array(
	'public' => true,
);

$post_types   = get_post_types( $args, 'objects' );
$network_wide = ! empty( get_site_option( 'checkview_networkwide' ) ) ? get_site_option( 'checkview_networkwide' ) : 'no';
?>
<div id="checkview-general-options" class="card">

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="checkview_admin_settings">
		<?php wp_nonce_field( 'checkview_admin_settings_action', 'checkview_admin_settings_field' ); ?>
		<?php if ( ! isset( $libraries[0] ) ) { ?>
		<div id="activation-code" class="activation-code">
			<div class="d-flex justify-content-center">
				<div class="col-md-7 text-center">
					<h2>
						<?php esc_html_e( 'Add Activation Code To Get Started', 'checkview' ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'Add valid Activation Code and activate library by clicking on Create Library. You can delete or deactivate library later from plugin if needed.', 'checkview' ); ?>
					</p>
					<input type="text" class="form-control input-field" name="checkview_activation_code" id="checkview_activation_code" placeholder=<?php esc_html_e( 'Enter your activation code', 'checkview' ); ?>>
					<?php
						submit_button( esc_html__( 'Create Library', 'checkview' ), 'primary', 'checkview_settings_submit' );
					?>
				</div>
			</div>
		</div>
		<?php } else { ?>
		<div id="activation-code" class="d-flex align-items-center activation-code mini">
			<div class="col-6 p-0">
				<input type="text" class="form-control input-field" name="checkview_activation_code" id="checkview_activation_code" placeholder=<?php esc_html_e( 'Enter your activation code', 'checkview' ); ?>>
			</div>
			<?php
					submit_button( esc_html__( 'Create Another Library', 'checkview' ), 'primary', 'checkview_settings_submit' );
			?>
		</div>
		<?php } ?>
	</form>
</div>

<?php