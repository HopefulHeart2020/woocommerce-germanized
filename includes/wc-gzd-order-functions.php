<?php
/**
 * Order Functions
 *
 * WC_GZD order functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @param $total_rows
 * @param WC_Order $order
 *
 * @return mixed
 */
function wc_gzd_cart_forwarding_fee_notice_filter( $total_rows, $order ) {
	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	$method   = $order->get_payment_method();
	$gateway  = isset( $gateways[ $method ] ) ? $gateways[ $method ] : null;

	if ( $gateway && $gateway->get_option( 'forwarding_fee' ) ) {
		$total_rows['order_total_forwarding_fee'] = array(
			'label' => '',
			'value'	=> sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ),
		);
	}

	return $total_rows;
}

add_filter( 'woocommerce_get_order_item_totals', 'wc_gzd_cart_forwarding_fee_notice_filter', 1500, 2 );

function wc_gzd_order_supports_parcel_delivery_reminder( $order_id ) {
	$order = wc_get_order( $order_id );
	
	if ( 'yes'  === $order->get_meta( '_parcel_delivery_opted_in', true ) ) {
		return true;
	}
	
	return false;
}

function wc_gzd_get_order_min_age( $order_id ) {
	$min_age = false;

	if ( $order = wc_get_order( $order_id ) ) {
		$min_age = $order->get_meta( '_min_age', true );

		if ( '' === $min_age || ! is_numeric( $min_age ) ) {
			$min_age = false;
		}
	}

	return apply_filters( 'woocommerce_gzd_order_min_age', $min_age, $order_id );
}

function wc_gzd_order_has_age_verification( $order_id ) {
	$age                = wc_gzd_get_order_min_age( $order_id );
	$needs_verification = false;

	if ( $age ) {
		$needs_verification = true;
	}

	return apply_filters( 'woocommerce_gzd_order_needs_age_verification', $needs_verification, $order_id );
}

function wc_gzd_order_is_anonymized( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	$is_anyomized = $order->get_meta('_anonymized' );

	return 'yes' === $is_anyomized;
}

function wc_gzd_get_order_date( $order, $format = '' ) {
	return wc_format_datetime( $order->get_date_created(), $format );
}

/**
 * @param WC_Order $order
 * @param string $type
 */
function wc_gzd_get_order_customer_title( $order, $type = 'billing' ) {
	$title_formatted = '';

	if ( $title = $order->get_meta( "_{$type}_title", true ) ) {
		$title_formatted = wc_gzd_get_customer_title( $title );
	}

	return $title_formatted;
}