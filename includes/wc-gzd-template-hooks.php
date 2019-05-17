<?php
/**
 * Action/filter hooks used for functions/templates
 *
 * @author 		Vendidero
 * @version     1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Hide certain HTML output if activated via options
 */
add_filter( 'woocommerce_germanized_hide_delivery_time_text', 'woocommerce_gzd_template_maybe_hide_delivery_time', 10, 2 );
add_filter( 'woocommerce_germanized_hide_shipping_costs_text', 'woocommerce_gzd_template_maybe_hide_shipping_costs', 10, 2 );

if ( get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ) !== '' )
	add_filter( 'woocommerce_germanized_empty_delivery_time_text', 'woocommerce_gzd_template_digital_delivery_time_text', 10, 2 );

add_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_sale_price_label_html', 50, 2 );

// WC pre 2.7
add_filter( 'woocommerce_get_variation_price_html', 'woocommerce_gzd_template_sale_price_label_html', 50, 2 );

/**
 * Single Product
 */
foreach( wc_gzd_get_legal_product_notice_types_by_location( 'single' ) as $type => $notice ) {
    if ( $notice['is_action'] ) {
        add_action( $notice['filter'], $notice['callback'], $notice['priority'], $notice['params'] );
    } else {
        add_filter( $notice['filter'], $notice['callback'], $notice['priority'], $notice['params'] );
    }
}

// Make sure to add a global product object to allow getting the grouped parent product within child display
add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_gzd_template_single_setup_global_product' );

add_filter( 'woocommerce_available_variation', 'woocommerce_gzd_add_variation_options', 0, 3 );

/**
 * Product Loop Items
 */
foreach( wc_gzd_get_legal_product_notice_types_by_location( 'loop' ) as $type => $notice ) {
    if ( $notice['is_action'] ) {
        add_action( $notice['filter'], $notice['callback'], $notice['priority'], $notice['params'] );
    } else {
        add_filter( $notice['filter'], $notice['callback'], $notice['priority'], $notice['params'] );
    }
}

if ( get_option( 'woocommerce_gzd_display_listings_add_to_cart' ) == 'no' ) {
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
}

if ( get_option( 'woocommerce_gzd_display_listings_link_details' ) == 'yes' ) {
	add_filter( 'woocommerce_loop_add_to_cart_link', 'woocommerce_gzd_template_loop_add_to_cart', 99, 2 );
}

/**
 * Widgets
 */
add_action( 'woocommerce_widget_product_item_start', 'woocommerce_gzd_template_product_widget_filters_start', 10, 1 );
add_action( 'woocommerce_widget_product_item_end', 'woocommerce_gzd_template_product_widget_filters_end', 10, 1 );

/**
 * Cart
 */

add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );

// Remove cart item name filter within checkout
add_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_remove_cart_name_filter' );

// Add cart product info
foreach( wc_gzd_get_legal_cart_notice_types_by_location( 'cart' ) as $type => $notice ) {
    add_filter( $notice['filter'], $notice['callback'], $notice['priority'], 3 );
}

// Small enterprises
if ( get_option( 'woocommerce_gzd_small_enterprise' ) === 'yes' ) {

	add_action( 'woocommerce_after_cart_totals', 'woocommerce_gzd_template_small_business_info', wc_gzd_get_hook_priority( 'cart_small_business_info' ) );
	add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_small_business_info', wc_gzd_get_hook_priority( 'checkout_small_business_info' ) );

	// Maybe show Small Business incl. VAT notice for total sums
	if ( apply_filters( 'woocommerce_gzd_small_business_show_total_vat_notice', false ) ) {
		add_filter( 'woocommerce_get_formatted_order_total', 'woocommerce_gzd_template_small_business_total_vat_notice', 10, 1 );
		add_filter( 'woocommerce_cart_totals_order_total_html', 'woocommerce_gzd_template_small_business_total_vat_notice', 10, 1 );
	}
}

// Differential Taxation
if ( get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) === 'yes' ) {
	add_action( 'woocommerce_after_cart_totals', 'woocommerce_gzd_template_differential_taxation_notice_cart', wc_gzd_get_hook_priority( 'cart_small_business_info' ) );
	add_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_differential_taxation_mark', wc_gzd_get_hook_priority( 'cart_product_differential_taxation' ), 3 );
}

/**
 * Mini Cart
 */
add_action( 'woocommerce_before_mini_cart_contents', 'woocommerce_gzd_template_mini_cart_remove_hooks', 5 );
add_action( 'woocommerce_before_mini_cart_contents', 'woocommerce_gzd_template_mini_cart_add_hooks', 10 );
add_action( 'woocommerce_after_mini_cart', 'woocommerce_gzd_template_mini_cart_maybe_remove_hooks', 10 );

add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'woocommerce_gzd_template_mini_cart_taxes', 10 );

/**
 * Checkout
 */
add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
add_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement' );
add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' );

// Add checkout product info
foreach( wc_gzd_get_legal_cart_notice_types_by_location( 'checkout' ) as $type => $notice ) {
    add_filter( $notice['filter'], $notice['callback'], $notice['priority'], 3 );
}

if ( get_option( 'woocommerce_gzd_display_checkout_edit_data_notice' ) == 'yes' )
	add_action( 'woocommerce_before_order_notes', 'woocommerce_gzd_template_checkout_edit_data_notice', wc_gzd_get_hook_priority( 'checkout_edit_data_notice' ), 1 );

// Remove default priorities
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

// Make sure payment form goes before order review
WC_GZD_Hook_Priorities::instance()->change_priority( 'woocommerce_checkout_order_review', 'woocommerce_order_review', wc_gzd_get_hook_priority( 'checkout_order_review' ) );
WC_GZD_Hook_Priorities::instance()->change_priority( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', wc_gzd_get_hook_priority( 'checkout_payment' ) );

// Load ajax relevant hooks
add_action( 'init', 'woocommerce_gzd_checkout_load_ajax_relevant_hooks' );

// Remove WooCommerce Terms checkbox
add_filter( 'woocommerce_checkout_show_terms', 'woocommerce_gzd_template_set_wc_terms_hide', 100 );

// Temporarily remove order button from payment.php - then add again to show after product table
add_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', PHP_INT_MAX );
add_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', PHP_INT_MAX );
add_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', PHP_INT_MAX );

/**
 * Render Checkboxes (except checkout)
 */
add_action( 'woocommerce_pay_order_before_submit', 'woocommerce_gzd_template_render_pay_for_order_checkboxes', 10 );
add_action( 'woocommerce_register_form', 'woocommerce_gzd_template_render_register_checkboxes', 19 );
add_filter( 'comment_form_submit_button', 'woocommerce_gzd_template_render_review_checkboxes', 10, 2 );

function woocommerce_gzd_checkout_load_ajax_relevant_hooks() {

	if ( is_ajax() )
		return;

	add_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );

	// Render checkout checkboxes
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );

	// Add payment title heading
	add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );

	if ( get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) === 'yes' ) {
		add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_differential_taxation_notice_cart', wc_gzd_get_hook_priority( 'checkout_small_business_info' ) );
	}
}

// Display back to cart button
if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) === 'yes' )
	add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );

// Force order button text
add_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', 9999 );

// Forwarding fee
add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_forwarding_fee_notice' );

/**
 * Order details & Thankyou
 */
add_action( 'woocommerce_thankyou_order_received_text', 'woocommerce_gzd_template_order_success_text', 0, 1 );
add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_order_pay_now_button', wc_gzd_get_hook_priority( 'order_pay_now_button' ), 1 );

// Set Hooks before order details table
add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_order_item_hooks', 0 );
// Add Hooks to pay form
add_action( 'before_woocommerce_pay', 'woocommerce_gzd_template_order_item_hooks', 10 );

add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'order_product_unit_price' ), 3 );

if ( get_option( 'woocommerce_gzd_hide_order_success_details' ) == 'yes' ) {
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_thankyou', 'woocommerce_order_details_table' ) );
}

/**
 * Remove Woo data privacy notices
 */
if ( apply_filters( 'woocommerce_gzd_disable_wc_privacy_policy_checkbox', true ) ) {
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
	remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );

	// If other plugins or themes use that function, make sure we are emptying the text.
	add_filter( 'woocommerce_get_privacy_policy_text', 'wc_gzd_template_empty_wc_privacy_policy_text', 999, 2 );
}

/**
 * Footer
 */
if ( 'yes' === get_option( 'woocommerce_gzd_display_footer_vat_notice' ) ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'gzd_footer_vat_info' ) );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
}
if ( 'yes' === get_option( 'woocommerce_gzd_display_footer_sale_price_notice' ) ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'gzd_footer_sale_info' ) );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );
}
?>