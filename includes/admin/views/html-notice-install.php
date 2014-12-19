<?php
/**
 * Admin View: Notice - Install
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="message" class="updated woocommerce-message woocommerce-gzd-message wc-connect">
	<h3><?php _e( '<strong>Welcome to WooCommerce Germanized</strong>', 'woocommerce-germanized' ); ?></h3>
	<p><?php echo _e( 'Just a few more steps and your Online-Shop will become legally compliant:', 'woocommerce-germanized' ); ?></p>
	<form name="" method="get">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Germanize WooCommerce', 'woocommerce-germanized' );?></th>
					<td>
						<label for="install_woocommerce_gzd_settings">
							<input id="install_woocommerce_gzd_settings" type="checkbox" value="true" name="install_woocommerce_gzd_settings">
							<?php _e( 'We will adjust WooCommerce Settings for you e.g.: EUR, German Price Format etc.', 'woocommerce-germanized' );?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Generate Legal Pages', 'woocommerce-germanized' );?></th>
					<td>
						<label for="install_woocommerce_gzd_pages">
							<input id="install_woocommerce_gzd_pages" type="checkbox" value="true" name="install_woocommerce_gzd_pages">
							<?php _e( 'We will automatically add legal pages such as Data Privacy Statement, Power of Revocation, Terms & Conditions etc.', 'woocommerce-germanized' );?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Generate EU VAT Rates', 'woocommerce-germanized' );?></th>
					<td>
						<label for="install_woocommerce_gzd_tax_rates">
							<input id="install_woocommerce_gzd_tax_rates" type="checkbox" value="true" name="install_woocommerce_gzd_tax_rates">
							<?php _e( 'We will automatically insert EU VAT Rates for selling virtual products.', 'woocommerce-germanized' );?>
						</label>
					</td>
				</tr>
			</tbody>
			<input type="hidden" name="install_woocommerce_gzd" value="true" />
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Install WooCommerce Germanized', 'woocommerce-germanized' );?>" /> <a class="wc-gzd-skip button-primary" href="<?php echo add_query_arg( 'skip_install_woocommerce_gzd', 'true', admin_url( 'admin.php?page=wc-settings' ) ); ?>"><?php _e( 'Skip setup', 'woocommerce-germanized' ); ?></a></p>
	</form>
</div>