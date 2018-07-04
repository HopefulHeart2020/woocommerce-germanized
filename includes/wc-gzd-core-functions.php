<?php
/**
 * Core Functions
 *
 * WC_GZD core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-product-functions.php';

function wc_gzd_get_dependencies( $instance = null ) {
	return apply_filters( 'woocommerce_gzd_dependencies_instance', WC_GZD_Dependencies::instance( $instance ) );
}

function wc_gzd_send_instant_order_confirmation() {
    return ( apply_filters( 'woocommerce_gzd_instant_order_confirmation', true ) && ( 'yes' !== get_option( 'woocommerce_gzd_disable_instant_order_confirmation' ) ) );
}

/**
 * Format tax rate percentage for output in frontend
 *  
 * @param  float  $rate   
 * @param  boolean $percent show percentage after number
 * @return string
 */
function wc_gzd_format_tax_rate_percentage( $rate, $percent = false ) {
	return str_replace( '.', ',', wc_format_decimal( str_replace( '%', '', $rate ), true, true ) ) . ( $percent ? '%' : '' );
}

function wc_gzd_is_customer_activated( $user_id = '' ) {
	
	if ( is_user_logged_in() && empty( $user_id ) )
		$user_id = get_current_user_id();

	if ( empty( $user_id ) || ! $user_id )
		return false;

	return ( get_user_meta( $user_id, '_woocommerce_activation', true ) ? false : true );
}

function wc_gzd_get_hook_priority( $hook ) {
	return WC_GZD_Hook_Priorities::instance()->get_hook_priority( $hook );
}

function wc_gzd_get_email_attachment_order() {
	$order = explode( ',', get_option( 'woocommerce_gzd_mail_attach_order', 'terms,revocation,data_security,imprint' ) );
	$items = array();

	foreach ( $order as $key => $item ) {
		$title = '';
		switch( $item ) {
			case "terms":
				$title = __( 'Terms & Conditions', 'woocommerce-germanized' );
			break;
			case "revocation":
				$title = __( 'Right of Recission', 'woocommerce-germanized' );
			break;
			case "imprint":
				$title = __( 'Imprint', 'woocommerce-germanized' );
			break;
			case "data_security":
				$title = __( 'Data Security', 'woocommerce-germanized' );
			break;
		}

		$items[ $item ] = $title;
	}
	
	return $items;	
}

function wc_gzd_get_page_permalink( $type ) {
	$page_id = wc_get_page_id( $type );
	$link = $page_id ? get_permalink( $page_id ) : '';
	return apply_filters( 'woocommerce_gzd_legal_page_permalink', $link, $type );
}

if ( ! function_exists( 'is_payment_methods' ) ) {

	/**
	 * is_checkout - Returns true when viewing the checkout page.
	 * @return bool
	 */
	function is_payment_methods() {
		return is_page( wc_get_page_id( 'payment_methods' ) ) || apply_filters( 'woocommerce_gzd_is_payment_methods', false ) ? true : false;
	}
}

function wc_gzd_get_small_business_notice() {
	return apply_filters( 'woocommerce_gzd_small_business_notice', get_option( 'woocommerce_gzd_small_enterprise_text', __( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'woocommerce-germanized' ) ) );
}

function wc_gzd_help_tip( $tip, $allow_html = false ) {
	
	if ( function_exists( 'wc_help_tip' ) )
		return wc_help_tip( $tip, $allow_html );

	return '<a class="tips" data-tip="' . ( $allow_html ? esc_html( $tip ) : $tip ) . '" href="#">[?]</a>';
}

function wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( $rate_ids = array() ) {
	$return = false;

	if ( $checkbox = wc_gzd_get_legal_checkbox( 'parcel_delivery' ) ) {

		if ( $checkbox->is_enabled() ) {
			$show = $checkbox->show_special;

			if ( 'always' === $show ) {
				$return = true;
			} else {
				$supported = $checkbox->show_shipping_methods ? $checkbox->show_shipping_methods : array();

				if ( ! is_array( $supported ) )
					$supported = array();

				$return = false;
				$rate_is_supported = true;

				if ( ! empty( $rate_ids ) ) {

					foreach ( $rate_ids as $rate_id ) {
						if ( ! in_array( $rate_id, $supported ) )
							$rate_is_supported = false;
					}

					if ( $rate_is_supported ) {
						$return = true;
					}
				}
			}
		}
	}

	return apply_filters( 'woocommerce_gzd_enable_parcel_delivery_data_transfer_checkbox', $return, $rate_ids );
}

function wc_gzd_get_dispute_resolution_text() {
	$type = get_option( 'woocommerce_gzd_dispute_resolution_type', 'none' );
	return get_option( 'woocommerce_gzd_alternative_complaints_text_' . $type );
}

function wc_gzd_get_tax_rate_label( $rate_percentage ) {
	return ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $rate_percentage ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) );
}

function wc_gzd_get_shipping_costs_text( $product = false ) {
	$find = array(
		'{link}',
		'{/link}'
	);

	$replace = array(
		'<a href="' . esc_url( get_permalink( wc_get_page_id( 'shipping_costs' ) ) ) . '" target="_blank">',
		'</a>'
	);

	if ( $product ) {
		return apply_filters( 'woocommerce_gzd_shipping_costs_text', str_replace( $find, $replace, ( $product->has_free_shipping() ? get_option( 'woocommerce_gzd_free_shipping_text' ) : get_option( 'woocommerce_gzd_shipping_costs_text' ) ) ), $product );
	} else {
		return apply_filters( 'woocommerce_gzd_shipping_costs_cart_text', str_replace( $find, $replace, get_option( 'woocommerce_gzd_shipping_costs_text' ) ) );
	}
}

function wc_gzd_sanitize_html_text_field( $value ) {
	return wp_kses_post( esc_html( wp_unslash( $value ) ) );
}

function wc_gzd_convert_coupon_to_voucher( $coupon ) {
	$coupon = new WC_Coupon( $coupon );
	WC_GZD_Coupon_Helper::instance()->convert_coupon_to_voucher( $coupon );
}

function wc_gzd_get_differential_taxation_notice_text() {
	return apply_filters( 'woocommerce_gzd_differential_taxation_notice_text', get_option( 'woocommerce_gzd_differential_taxation_notice_text' ) );
}

function wc_gzd_get_privacy_policy_page_id() {
	return apply_filters( 'woocommerce_gzd_privacy_policy_page_id', wc_get_page_id( 'data_security' ) );
}

function wc_gzd_get_privacy_policy_url() {
	return get_permalink( wc_gzd_get_privacy_policy_page_id() );
}

function wc_gzd_get_customer_title( $option ) {
	$options = apply_filters( 'woocommerce_gzd_title_options', array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ) );
	return ( array_key_exists( $option, $options ) ? $options[ $option ] : $option );
}

function wc_gzd_register_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->register( $id, $args );
}

function wc_gzd_update_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->update( $id, $args );
}

function wc_gzd_get_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	return $manager->get_checkbox( $id );
}

function wc_gzd_remove_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	$manager->remove( $id );
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}