<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce PayPal Payments Helper
 *
 * @see https://wordpress.org/plugins/woocommerce-paypal-payments/
 *
 * @class    WC_GZD_Compatibility_WooCommerce_PayPal_Payments
 * @category Class
 * @author   vendidero
 */
class WC_GZD_Compatibility_WooCommerce_PayPal_Payments extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce PayPal Payments';
	}

	public static function get_path() {
		return 'woocommerce-paypal-payments/woocommerce-paypal-payments.php';
	}

	public function load() {
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'move_paypal_payment_button' ), 10 );

		add_action( 'woocommerce_gzd_review_order_before_submit', function() {
			do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
		} );

		add_action( 'woocommerce_review_order_before_submit', function() {
			if ( wc_gzd_checkout_adjustments_disabled() ) {
				do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
			}
		} );
	}

	public function move_paypal_payment_button( $filter ) {
		return 'woocommerce_gzd_render_paypal_payments_smart_button';
	}
}