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
							<?php esc_html_e( 'Admin menu title', 'th-checkview-server-custom-template-tab' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Customize the admin menu title using this field.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-cache-box">
					<label  for="checkview_admin_menu_title">
						<input type="text" name="checkview_admin_menu_title" placeholder="<?php echo esc_html( $admin_menu_title ); ?>" value="<?php echo esc_html( $admin_menu_title ); ?>" class="th-del-lib" id="checkview_admin_menu_title"/>
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_delete_data">
							<?php esc_html_e( 'Delete data on uninstall', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'When selected, this option will remove all data associated with the plugin from WordPress upon uninstallation, without affecting the CheckView platform.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-cache-box">
					<label class="switch" for="checkview_delete_data">
						<input type="checkbox" name="checkview_delete_data" class="checkview-del-lib" id="checkview_delete_data"
						<?php
						if ( 'on' === $delete_all ) {
							?>
							checked="checked"<?php } ?> />
						<div class="slider round"></div>
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_update_cache">
							<?php esc_html_e( 'Update Cache', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'The CheckView Cache refreshes daily automatically. To update it manually, simply click the "Update Cache" button.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-cache-box">
						<label class="" for="checkview_update_cache">
							<button onclick="checkview_update_cache()" type="button" id="checkview_update_cache" name="checkview_update_cache" data-nonce="<?php echo esc_attr( wp_create_nonce( 'checkview_reset_cache' ) ); ?>" class="button checkview-button-spinner btn-outline-secondary"><?php esc_html_e( 'Update Cache', 'checkview' ); ?></button>
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