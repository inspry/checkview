<?php
/**
 * General Options
 *
 * @package settings
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}
$checkview_options = get_option( 'checkview_api_options', array() );
$delete_all        = ! empty( $checkview_options['checkview_delete_data'] ) ? $checkview_options['checkview_delete_data'] : '';
$allow_dev         = ! empty( $checkview_options['checkview_allowed_extensions'] ) ? $checkview_options['checkview_allowed_extensions'] : '';
$admin_menu_title  = ! empty( get_site_option( 'checkview_admin_menu_title', 'CheckView' ) ) ? get_site_option( 'checkview_admin_menu_title', 'CheckView' ) : 'CheckView';

?>
<div id="checkview-general-options" class="card">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="checkview_admin_api_settings">
		<?php wp_nonce_field( 'checkview_admin_api_settings_action', 'checkview_admin_api_settings_action' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_get_forms">
							<?php esc_html_e( 'Get Forms', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this field to white label admin menu title.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_get_forms">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/forms/formslist' ); ?></p>
					</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" >
						<label for="checkview_register_forms_test">
							<?php esc_html_e( 'Registers forms test', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this field to register form test.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_register_forms_test">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/forms/registerformtest' ); ?></p>
					</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" >
						<label for="checkview_get_forms_test">
							<?php esc_html_e( 'Get forms test', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this field to get form test.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_get_forms_test">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/forms/formstestresults' ); ?></p>
					</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" >
						<label for="checkview_get_products">
							<?php esc_html_e( 'Retrieves products from store', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this endpoint to get all store products.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_get_products">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/store/products' ); ?></p>
					</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" >
						<label for="checkview_get_orders">
							<?php esc_html_e( 'Retrieves checkview orders from store', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this endpoint to get all store orders created by checkview.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_get_orders">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/store/orders' ); ?></p>
					</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" >
						<label for="checkview_delete_orders">
							<?php esc_html_e( 'Delete orders', 'checkview' ); ?>
						</label>
						<p class="make-lib-description"><?php esc_html_e( 'Use this field to delete orders created by checkview.', 'checkview' ); ?></p>
					</th>
					<td class="checkview-make-library-box">
					<label  for="checkview_delete_orders">
						<p class="make-lib-description"><?php echo esc_url_raw( get_rest_url() . 'checkview/v1/store/deleteorders' ); ?></p>
					</label>
					</td>
				</tr>
				
				<?php do_action( 'checkview_api_settings', $checkview_options ); ?>
			</tbody>
		</table>
	</form>
</div>

<?php