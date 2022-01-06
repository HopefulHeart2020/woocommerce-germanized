<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term_options = array(
	'woocommerce_gzd_default_delivery_time',
	'woocommerce_gzd_default_delivery_time_eu',
	'woocommerce_gzd_default_delivery_time_third_countries'
);

/**
 * Convert term id options to slug
 */
foreach( $term_options as $term_option ) {
	if ( get_option( $term_option ) ) {
		$term = get_term_by( 'id', get_option( $term_option ), 'product_delivery_time' );

		if ( is_array( $term ) ) {
			$term = $term[0];
		}

		if ( $term && ! is_wp_error( $term ) ) {
			update_option( $term_option, $term->slug );
		}
	}
}