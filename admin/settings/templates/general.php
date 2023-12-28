<?php
/**
 * General Options
 *
 * @package settings
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}
$checkview_options = get_option( 'checkview_advance_options', array() );
$delete_all        = ! empty( $checkview_options['checkview_delete_data'] ) ? $checkview_options['checkview_delete_data'] : '';
$allow_dev         = ! empty( $checkview_options['checkview_allowed_extensions'] ) ? $checkview_options['checkview_allowed_extensions'] : '';
$admin_menu_title  = ! empty( get_site_option( 'checkview_admin_menu_title', 'CheckView' ) ) ? get_site_option( 'checkview_admin_menu_title', 'CheckView' ) : 'CheckView';

?>
<div id="checkview-general-options" class="card">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="checkview_admin_advance_settings">
		<?php wp_nonce_field( 'checkview_admin_advance_settings_action', 'checkview_admin_advance_settings_action' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_admin_menu_title">
							<?php esc_html_e( 'Admin menu title', 'th-elementor-server-custom-template-tab' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this field to white label admin menu title.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_admin_menu_title">
						<input type="text" name="checkview_admin_menu_title" placeholder="<?php esc_html_e( $admin_menu_title, 'checkview' ); ?>" value="<?php esc_html_e( $admin_menu_title, 'checkview' ); ?>" class="th-del-lib" id="checkview_admin_menu_title"/>
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_delete_data">
							<?php esc_html_e( 'Delete data on uninstall', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'If checked It Will Delete All The Data On Uninstall.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label class="switch" for="checkview_delete_data">
						<input type="checkbox" name="checkview_delete_data" class="checkview-del-lib" id="checkview_delete_data"
						<?php
						if ( $delete_all == 'on' ) {
							?>
							checked="checked"<?php } ?> />
						<div class="slider round"></div>
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_sync_library">
							<?php esc_html_e( 'Sync library', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Elementor Library automatically updates on a daily basis. You can also manually update it by clicking on the sync button.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
						<label class="" for="checkview_sync_library">
							<button type="button" id="reset-library" data-nonce="<?php echo wp_create_nonce( 'elementor_reset_library' ); ?>" class="button elementor-button-spinner btn-outline-secondary"><?php esc_html_e( 'Sync Library', 'checkview' ); ?></button>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" >
						<label for="checkview_allowed_extensions">
							<?php esc_html_e( 'Allowed 3rd party integration', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Warning! If checked it will allow 3rd parties to access Core functions.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label class="switch" for="checkview_allowed_extensions">
						<input type="checkbox" name="checkview_allowed_extensions" class="checkview-allow-extension" id="checkview_allowed_extensions"
						<?php
						if ( 'on' === $allow_dev ) {
							?>
							checked="checked" <?php } ?> />
						<div class="slider round"></div>
					</label>
					</td>
				</tr>
				<?php do_action( 'checkview_advance_settings', $checkview_options ); ?>
			</tbody>
		</table>
		<?php
			submit_button( esc_html__( 'Save Settings', 'checkview' ), 'primary', 'checkview_advance_settings_submit' );
		?>
	</form>
</div>

<?php