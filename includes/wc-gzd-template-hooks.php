<?php
/**
 * Action/filter hooks used for functions/templates
 *
 * @author 		Vendidero
 * @version     1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Product Summary Box
 */
if ( get_option( 'woocommerce_gzd_display_product_detail_unit_price' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_price_unit', 10 );
if ( get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) == 'yes' || get_option( 'woocommerce_gzd_display_product_detail_shipping_costs' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_legal_info', 11 );
if ( get_option( 'woocommerce_gzd_display_product_detail_delivery_time' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_delivery_time_info', 27 );
if ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ) {
	add_action( 'woocommerce_after_cart_totals', 'woocommerce_gzd_template_small_business_info' );
	add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_small_business_info', 25 );
}
add_filter( 'woocommerce_available_variation', 'woocommerce_gzd_add_variation_options', 0, 3 );

/**
 * Product Loop Items
 */
if ( get_option( 'woocommerce_gzd_display_listings_unit_price' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_gzd_template_single_price_unit', 10 );
if ( get_option( 'woocommerce_gzd_display_listings_shipping_costs' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_shipping_costs_info', 8 );
if ( get_option( 'woocommerce_gzd_display_listings_delivery_time' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_delivery_time_info', 7 );
if ( get_option( 'woocommerce_gzd_display_listings_tax_info' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_tax_info', 6 );
if ( get_option( 'woocommerce_gzd_display_listings_add_to_cart' ) == 'no' )
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
if ( get_option( 'woocommerce_gzd_display_listings_link_details' ) == 'yes' )
	add_filter( 'woocommerce_loop_add_to_cart_link', 'woocommerce_gzd_template_loop_add_to_cart', 99, 2 );

/**
 * Cart
 */
add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
add_filter( 'woocommerce_cart_item_name', 'woocommerce_gzd_template_cart_product_delivery_time', 0, 3 );
add_filter( 'woocommerce_cart_item_name', 'wc_gzd_product_item_desc', 0, 2 );

/**
 * Checkout
 */
if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) == 'yes' )
	add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );
add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_template_checkout_legal' );
add_filter( 'woocommerce_checkout_show_terms', 'woocommerce_gzd_remove_term_checkbox' );
add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_template_checkout_set_terms_manually' );
if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) == 'yes' )
	add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_digital_checkbox', 1 );
if ( get_option( 'woocommerce_gzd_trusted_shops_id' ) )
	add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_checkout_thankyou_trusted_shops', 10, 1 );
add_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', 50 );
remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
add_action( 'woocommerce_thankyou_order_received_text', 'woocommerce_gzd_template_order_success_text', 0, 1 );
if ( get_option( 'woocommerce_gzd_hide_order_success_details' ) == 'yes' )
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

/**
 * Checkout Validation
*/
add_action( 'woocommerce_after_checkout_validation', 'woocommerce_gzd_checkout_validation', 1, 1 );

/**
 * Footer
 */
if ( get_option( 'woocommerce_gzd_display_footer_vat_notice' ) == 'yes' ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_vat_info', 0 );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', 5 );
}
if ( get_option( 'woocommerce_gzd_display_footer_sale_price_notice' ) == 'yes' ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_sale_info', 0 );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', 5 );
}
?>