<?php
/**
 * Core Functions
 *
 * WC_GZD core functions.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Vendidero\Germanized\Shopmark;
use Vendidero\Germanized\Shopmarks;

require WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-product-functions.php';

/**
 * Defect Description.
 */
if ( function_exists( 'do_blocks' ) ) {
	add_filter( 'woocommerce_gzd_defect_description', 'do_blocks', 9 );
}
add_filter( 'woocommerce_gzd_defect_description', 'wptexturize' );
add_filter( 'woocommerce_gzd_defect_description', 'convert_smilies' );
add_filter( 'woocommerce_gzd_defect_description', 'convert_chars' );
add_filter( 'woocommerce_gzd_defect_description', 'wpautop' );
add_filter( 'woocommerce_gzd_defect_description', 'shortcode_unautop' );
add_filter( 'woocommerce_gzd_defect_description', 'prepend_attachment' );
add_filter( 'woocommerce_gzd_defect_description', 'do_shortcode', 11 ); // After wpautop().
add_filter( 'woocommerce_gzd_defect_description', 'wc_format_product_short_description', 9999999 );
add_filter( 'woocommerce_gzd_defect_description', 'wc_do_oembeds' );
add_filter( 'woocommerce_gzd_defect_description', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 ); // Before wpautop().

/**
 * @param null $instance
 *
 * @return WC_GZD_Dependencies
 */
function wc_gzd_get_dependencies( $instance = null ) {
	/** This filter is documented in woocommerce-germanized.php */
	return apply_filters( 'woocommerce_gzd_dependencies_instance', WC_GZD_Dependencies::instance( $instance ) );
}

function wc_gzd_post_has_woocommerce_block( $post_content ) {

	if ( ! function_exists( 'has_blocks' ) ) {
		return false;
	}

	if ( false === has_blocks( $post_content ) ) {
		return false;
	}

	return false !== strpos( $post_content, '<!-- wp:woocommerce/' );
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_single_product_shopmarks() {
	return Shopmarks::get( 'single_product' );
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_single_product_grouped_shopmarks() {
	return Shopmarks::get( 'single_product_grouped' );
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_product_loop_shopmarks() {
	return Shopmarks::get( 'product_loop' );
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_cart_shopmarks() {

	$cart = Shopmarks::get( 'cart' );

	if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ) {
		if ( wc_gzd_cart_contains_differential_taxed_product() ) {
			$shopmark = _wc_gzd_get_differential_taxation_shopmark( 'cart' );
			$cart[]   = $shopmark;
		}
	}

	return $cart;
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_mini_cart_shopmarks() {
	$mini_cart = Shopmarks::get( 'mini_cart' );

	if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ) {
		if ( wc_gzd_cart_contains_differential_taxed_product() ) {
			$shopmark    = _wc_gzd_get_differential_taxation_shopmark( 'mini_cart' );
			$mini_cart[] = $shopmark;
		}
	}

	return $mini_cart;
}

function _wc_gzd_get_differential_taxation_shopmark( $location ) {
	$default_filter = 'order' === $location ? 'woocommerce_order_item_name' : 'woocommerce_cart_item_name';

	$shopmark = new Shopmark( array(
		'default_priority' => wc_gzd_get_hook_priority( 'cart_product_differential_taxation' ),
		'callback'         => 'wc_gzd_cart_product_differential_taxation_mark',
		'default_filter'   => $default_filter,
		'location'         => $location,
		'type'             => 'differential_taxation',
		'default_enabled'  => true,
	) );

	return $shopmark;
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_checkout_shopmarks() {
	$checkout = Shopmarks::get( 'checkout' );

	if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ) {
		if ( wc_gzd_cart_contains_differential_taxed_product() ) {
			$shopmark    = _wc_gzd_get_differential_taxation_shopmark( 'checkout' );
			$checkout[]  = $shopmark;
		}
	}

	return $checkout;
}

/**
 * @return Vendidero\Germanized\Shopmark[]
 */
function wc_gzd_get_order_shopmarks() {
	$order_shopmarks = Shopmarks::get( 'order' );

	if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ) {
		global $wp;
		$order_id = false;

		if ( is_wc_endpoint_url( 'order-received' ) ) {
			$order_id = $wp->query_vars['order-received'];
		} elseif ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = $wp->query_vars['order-pay'];
		}

		if ( $order_id && ( $order = wc_get_order( $order_id ) ) ) {
			if ( wc_gzd_cart_contains_differential_taxed_product( $order->get_items() ) ) {
				$shopmark          = _wc_gzd_get_differential_taxation_shopmark( 'order' );
				$order_shopmarks[] = $shopmark;
			}
		}
	}

	return $order_shopmarks;
}

/**
 * @param $location
 * @param $type
 *
 * @return bool|Vendidero\Germanized\Shopmark $shopmark
 */
function wc_gzd_get_shopmark( $location, $type ) {
	$shopmarks = Shopmarks::get( $location );

	foreach ( $shopmarks as $shopmark ) {
		if ( $type === $shopmark->get_type() ) {
			return $shopmark;
		}
	}

	return false;
}

function wc_gzd_shopmark_is_enabled( $location, $type ) {
	if ( $shopmark = wc_gzd_get_shopmark( $location, $type ) ) {
		return $shopmark->is_enabled();
	}

	return false;
}

function wc_gzd_send_instant_order_confirmation() {

	/**
	 * Filter to enable/disable instant order confirmation.
	 * This filter may be used to disable the instant order confirmation sent by Germanized
	 * to the customer right after submitting the order. Warning: You should check with your lawyer
	 * before disabling this option.
	 *
	 * ```php
	 * function ex_disable_instant_order_confirmation() {
	 *      return false;
	 * }
	 * add_filter( 'woocommerce_gzd_instant_order_confirmation', 'ex_disable_instant_order_confirmation', 10 );
	 * ```
	 *
	 * @param bool $enable Set to `false` to disable instant order confirmation.
	 *
	 * @since 1.0.0
	 *
	 */
	return ( apply_filters( 'woocommerce_gzd_instant_order_confirmation', true ) && ( 'yes' !== get_option( 'woocommerce_gzd_disable_instant_order_confirmation' ) ) );
}

function wc_gzd_get_legal_product_notice_types() {
	wc_deprecated_function( __FUNCTION__, '3.0' );
}

function wc_gzd_get_age_verification_min_ages() {
	/**
	 * Returns minimum age options.
	 *
	 * This filter might be used to adjust the minimum age options available to choose from
	 * e.g. on product level.
	 *
	 * ```php
	 * function ex_filter_add_min_ages( $ages ) {
	 *      $ages[14] = '>= 14 years';
	 *      return $ages;
	 * }
	 * add_filter( 'woocommerce_gzd_age_verification_min_ages', 'ex_filter_add_min_ages', 10, 1 );
	 * ```
	 *
	 * @param array $ages Array containing age => value elements.
	 *
	 * @since 2.3.5
	 *
	 */
	return apply_filters( 'woocommerce_gzd_age_verification_min_ages', array(
		12 => __( '>= 12 years', 'woocommerce-germanized' ),
		16 => __( '>= 16 years', 'woocommerce-germanized' ),
		18 => __( '>= 18 years', 'woocommerce-germanized' ),
		21 => __( '>= 21 years', 'woocommerce-germanized' ),
		25 => __( '>= 25 years', 'woocommerce-germanized' )
	) );
}

function wc_gzd_get_age_verification_min_ages_select() {
	$age_select = array( "-1" => _x( 'None', 'age', 'woocommerce-germanized' ) ) + wc_gzd_get_age_verification_min_ages();

	return $age_select;
}

/**
 * Format tax rate percentage for output in frontend
 *
 * @param float $rate
 * @param boolean $percent show percentage after number
 *
 * @return string
 */
function wc_gzd_format_tax_rate_percentage( $rate, $percent = false ) {
	return str_replace( '.', ',', wc_format_decimal( str_replace( '%', '', $rate ), true, true ) ) . ( $percent ? ' %' : '' );
}

function wc_gzd_is_customer_activated( $user_id = '' ) {

	if ( is_user_logged_in() && empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || ! $user_id ) {
		return false;
	}

	return ( get_user_meta( $user_id, '_woocommerce_activation', true ) ? false : true );
}

function wc_gzd_get_hook_priority( $hook ) {
	return WC_GZD_Hook_Priorities::instance()->get_hook_priority( $hook );
}

function wc_gzd_get_legal_pages( $email_attachable_only = false ) {
	$legal_pages = array(
		'terms'         => __( 'Terms & Conditions', 'woocommerce-germanized' ),
		'revocation'    => __( 'Cancellation Policy', 'woocommerce-germanized' ),
		'imprint'       => __( 'Imprint', 'woocommerce-germanized' ),
		'data_security' => __( 'Privacy Policy', 'woocommerce-germanized' ),
	);

	$secondary_pages = array(
		'payment_methods' => __( 'Payment Methods', 'woocommerce-germanized' ),
		'shipping_costs'  => __( 'Shipping Costs', 'woocommerce-germanized' ),
	);

	if ( ! $email_attachable_only ) {
		$legal_pages = $legal_pages + $secondary_pages;
	}

	/**
	 * Filters pages considered as legal pages.
	 *
	 * @param array $legal_pages Array containing key and title of legal pages.
	 * @param bool $email_attachable_only Whether to include those attachable to emails only or not.
	 *
	 * @since 1.0.0
	 *
	 */
	return apply_filters( 'woocommerce_gzd_legal_pages', $legal_pages, $email_attachable_only );
}

function wc_gzd_get_default_email_attachment_order() {
	return 'terms,revocation,data_security,imprint,warranties';
}

function wc_gzd_get_email_attachment_order( $legal_pages_only = false ) {
	$available = wc_gzd_get_legal_pages( true );

	if ( ! $legal_pages_only ) {
		$available += array( 'warranties' => _x( 'Product Warranties', 'woocommerce-germanized' ) );
	}

	$current_order = explode( ',', get_option( 'woocommerce_gzd_mail_attach_order', wc_gzd_get_default_email_attachment_order() ) );
	// Mare sure all default items exist within option order array
	$current_order = array_replace( array_keys( $available ), $current_order );
	$items         = array();

	foreach ( $current_order as $key => $item ) {
		$items[ $item ] = ( isset( $available[ $item ] ) ? $available[ $item ] : '' );
	}

	return $items;
}

function wc_gzd_get_page_permalink( $type ) {
	$page_id = wc_get_page_id( $type );

	if ( 'data_security' === $type ) {
		$page_id = wc_gzd_get_privacy_policy_page_id();
	}

	$link = $page_id ? get_permalink( $page_id ) : '';

	/**
	 * Filters the page permalink for a certain legal page.
	 *
	 * @param string $type Legal page identifier e.g. terms.
	 *
	 * @see wc_gzd_get_legal_pages
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'woocommerce_gzd_legal_page_permalink', $link, $type );
}

/**
 * @return bool
 *
 * @since 3.1.9
 */
function wc_gzd_is_small_business() {
	return 'yes' === get_option( 'woocommerce_gzd_small_enterprise' );
}

function wc_gzd_get_small_business_notice() {

	/**
	 * Filter the (global) small business notice.
	 *
	 * @param string $html The notice HTML.
	 *
	 * @since 1.0.0
	 *
	 */
	return apply_filters( 'woocommerce_gzd_small_business_notice', get_option( 'woocommerce_gzd_small_enterprise_text', __( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'woocommerce-germanized' ) ) );
}

function wc_gzd_get_differential_taxation_mark() {
	/**
	 * Filters the general differential taxation notice mark.
	 *
	 * @param string $notice The notice mark, e.g. `*`.
	 *
	 * @since 1.5.0
	 */
	return apply_filters( 'woocommerce_gzd_differential_taxation_notice_text_mark', '** ' );
}

function wc_gzd_get_differential_taxation_checkout_notice() {
	$mark = wc_gzd_get_differential_taxation_mark();

	/**
	 * Filter to adjust the differential taxation notice text during checkout.
	 *
	 * @param string $html The notice.
	 *
	 * @since 1.9.3
	 */
	$notice = apply_filters( 'woocommerce_gzd_differential_taxation_notice_text_checkout', $mark . wc_gzd_get_differential_taxation_notice_text() );

	return $notice;
}

function wc_gzd_shipping_method_id_matches_supported( $method_id, $supported = array() ) {
	if ( ! is_array( $supported ) ) {
		$supported = array( $supported );
	}

	$new_supported = $supported;
	$new_method_id = $method_id;

	/**
	 * E.g. Flexible shipping uses underscores. Add them to the search array.
	 */
	foreach( $supported as $supported_method ) {
		$supported_method = str_replace( ':', '_', $supported_method );
		$new_supported[]  = $supported_method;
	}

	/**
	 * Remove the last part of a compatible shipping method id. E.g.:
	 * flexible_shipping_4_1 - remove _1 from the string to compare it to our search array.
	 */
	$method_parts = explode( '_', $new_method_id );

	if ( ! empty( $method_parts ) ) {
		$last_part = $method_parts[ sizeof( $method_parts ) - 1 ];

		if ( is_numeric( $last_part ) ) {
			$method_parts = array_slice( $method_parts, 0, ( sizeof( $method_parts ) - 1 ) );

			if ( ! empty( $method_parts ) ) {
				$new_method_id = implode( '_', $method_parts );
			}
		}
	}

	$is_supported = in_array( $new_method_id, $new_supported ) ? true : false;

	/**
	 * Filter to check whether a certain shipping method id matches one of the
	 * shipping method expected (e.g. from the settings).
	 *
	 * @param bool   $return Whether the method id matches one of the expected methods or not.
	 * @param string $method_id The shipping method id.
	 * @param array  $supported The shipping method ids to search for.
	 *
	 * @since 3.2.2
	 */
	return apply_filters( 'woocommerce_gzd_shipping_method_id_matches_supported', $is_supported, $method_id, $supported );
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

				if ( ! is_array( $supported ) ) {
					$supported = array();
				}

				$return = false;

				if ( ! empty( $rate_ids ) ) {
					foreach ( $rate_ids as $rate_id ) {
						if ( wc_gzd_shipping_method_id_matches_supported( $rate_id, $supported ) ) {
							$return = true;
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Filter that allows adjusting whether to show the parcel delivery data transfer
	 * checkbox or not for rate ids.
	 *
	 * @param bool $return Whether to display the checkbox or not.
	 * @param array $rate_ids Shipping rate ids to check against.
	 *
	 * @since 1.9.7
	 *
	 */
	return apply_filters( 'woocommerce_gzd_enable_parcel_delivery_data_transfer_checkbox', $return, $rate_ids );
}

function wc_gzd_get_dispute_resolution_text() {
	$type = get_option( 'woocommerce_gzd_dispute_resolution_type', 'none' );

	return get_option( 'woocommerce_gzd_alternative_complaints_text_' . $type );
}

function wc_gzd_show_taxes_before_total( $location = 'checkout' ) {
	return apply_filters( 'woocommerce_gzd_show_taxes_before_total', 'before' === get_option( 'woocommerce_gzd_tax_totals_display' ), $location );
}

function wc_gzd_get_tax_rate_label( $rate_percentage, $type = 'incl' ) {
	if ( 'incl' === $type ) {
		$label = ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $rate_percentage ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) );
	} else {
		$label = ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ? sprintf( __( '%s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $rate_percentage ) ) : __( 'VAT', 'woocommerce-germanized' ) );
	}

	/**
	 * Allow adjusting the tax rate label e.g. "incl. 19% tax".
	 *
	 * @param string $label The label.
	 * @param int $rate_percentage The percentage e.g. 19.
	 *
	 * @since 2.3.3
	 *
	 */
	return apply_filters( 'woocommerce_gzd_tax_rate_label', $label, $rate_percentage, $type );
}

/**
 * @param $tax_rate_id
 * @param WC_Order $order
 *
 * @return mixed|void
 */
function wc_gzd_get_order_tax_rate_percentage( $tax_rate_id, $order ) {
	$taxes      = $order->get_taxes();
	$percentage = null;

	foreach( $taxes as $tax ) {
		if ( $tax->get_rate_id() == $tax_rate_id ) {
			if ( is_callable( array( $tax, 'get_rate_percent' ) ) ) {
				$percentage = $tax->get_rate_percent();
				break;
			}
		}
	}

	/**
	 * In case order does not contain tax rate percentage. Look for the global percentage instead.
	 */
	if ( is_null( $percentage ) || '' === $percentage ) {
		if ( is_callable( array( 'WC_Tax', 'get_rate_percent_value' ) ) ) {
			$percentage = WC_Tax::get_rate_percent_value( $tax_rate_id );
		} elseif ( is_callable( array( 'WC_Tax', 'get_rate_percent' ) ) ) {
			$percentage = filter_var( WC_Tax::get_rate_percent( $tax_rate_id ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		}
	}

	if ( ! is_numeric( $percentage ) ) {
		$percentage = 0;
	}

	/**
	 * Allow adjusting the order tax rate percentage for a certain tax rate id.
	 *
	 * @param int $percentage The percentage e.g. 19.
	 * @param int $tax_rate_id The tax rate id.
	 * @param WC_Order $order The order object
	 *
	 * @since 3.1.9
	 */
	return apply_filters( 'woocommerce_gzd_order_tax_rate_percentage', $percentage, $tax_rate_id, $order );
}

function wc_gzd_get_shipping_costs_text( $product = false ) {
	$replacements = array(
		'{link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'shipping_costs' ) ) . '" target="_blank">',
		'{/link}' => '</a>',
	);

	if ( $product ) {
		$html = $product->has_free_shipping() ? get_option( 'woocommerce_gzd_free_shipping_text' ) : get_option( 'woocommerce_gzd_shipping_costs_text' );

		/**
		 * Filter to adjust the shipping costs legal text for a certain product.
		 *
		 * @param string $html The notice output.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shipping_costs_text', wc_gzd_replace_label_shortcodes( $html, $replacements ), $product );
	} else {

		/**
		 * Filter to adjust the shipping costs legal text during cart, checkout and orders.
		 *
		 * @param string $html The notice output.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shipping_costs_cart_text', wc_gzd_replace_label_shortcodes( get_option( 'woocommerce_gzd_shipping_costs_text' ), $replacements ) );
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
	/**
	 * Filter to adjust the differential taxation notice text.
	 *
	 * @param string $html The notice.
	 *
	 * @since 1.9.1
	 *
	 */
	return apply_filters( 'woocommerce_gzd_differential_taxation_notice_text', get_option( 'woocommerce_gzd_differential_taxation_notice_text' ) );
}

function wc_gzd_get_privacy_policy_page_id() {
	/**
	 * Filter to adjust the Germanized privacy page id.
	 *
	 * @param int $page_id The page id.
	 *
	 * @since 1.9.10
	 *
	 */
	return apply_filters( 'woocommerce_gzd_privacy_policy_page_id', wc_get_page_id( 'data_security' ) );
}

function wc_gzd_get_privacy_policy_url() {
	return wc_gzd_get_page_permalink( 'data_security' );
}

function wc_gzd_get_customer_title_options() {

	/**
	 * Filter default customer title options e.g. Mr. or Ms.
	 *
	 * ```php
	 * function ex_adjust_title_options( $titles ) {
	 *      // Add a extra title option
	 *      $titles[3] = __( 'Neutral', 'my-text-domain' );
	 *
	 *      return $titles;
	 * }
	 * add_filter( 'woocommerce_gzd_title_options', 'ex_adjust_title_options', 10, 1 );
	 * ```
	 *
	 * @param array $titles Array containing title selection options.
	 *
	 * @since 1.0.0
	 *
	 */
	$titles = apply_filters( 'woocommerce_gzd_title_options', array(
		0 => _x( 'None', 'title-option', 'woocommerce-germanized' ),
		1  => __( 'Mr.', 'woocommerce-germanized' ),
		2  => __( 'Ms.', 'woocommerce-germanized' ),
		3  => __( 'Mx', 'woocommerce-germanized' )
	) );

	return $titles;
}

function wc_gzd_get_customer_title( $value ) {
	$option = is_numeric( $value ) ? absint( $value ) : $value;
	$titles = wc_gzd_get_customer_title_options();
	$title  = '';

	if ( '[deleted]' === $value ) {
		$title = $value;
	} else {
		if ( array_key_exists( $option, $titles ) ) {
			$title = $titles[ $option ];
		} elseif ( ! is_numeric( $title ) ) {
			$title = $option;
		} else {
			$title = __( 'Ms.', 'woocommerce-germanized' );
		}
	}

	/**
	 * In case the customer has chosen a gender-neutral title or no title at all - do not use a specific title as output.
	 */
	if ( __( 'Mx', 'woocommerce-germanized' ) === $title || _x( 'None', 'title-option', 'woocommerce-germanized' ) === $title ) {
		$title = '';
	}

	return apply_filters( 'woocommerce_gzd_customer_formatted_title', $title, $value );
}

function wc_gzd_register_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();

	return $manager->register( $id, $args );
}

function wc_gzd_update_legal_checkbox( $id, $args ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();

	return $manager->update( $id, $args );
}

/**
 * @param $id
 *
 * @return false|WC_GZD_Legal_Checkbox
 */
function wc_gzd_get_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();

	return $manager->get_checkbox( $id );
}

function wc_gzd_remove_legal_checkbox( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	$manager->remove( $id );
}

function wc_gzd_checkbox_is_enabled( $id ) {
	$manager = WC_GZD_Legal_Checkbox_Manager::instance();
	$enabled = false;

	if ( $checkbox = $manager->get_checkbox( $id ) ) {
		$enabled = $checkbox->is_enabled();
	}

	return $enabled;
}

/**
 * Remove Class Filter Without Access to Class Object
 *
 * In order to use the core WordPress remove_filter() on a filter added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove filters with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
 *
 * @param string $tag Filter to remove
 * @param string $class_name Class name for the filter's callback
 * @param string $method_name Method name for the filter's callback
 * @param int $priority Priority of the filter (default 10)
 *
 * @return bool Whether the function is removed.
 */
function wc_gzd_remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	global $wp_filter;

	// Check that filter actually exists first
	if ( ! isset( $wp_filter[ $tag ] ) ) {
		return false;
	}

	/**
	 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
	 * a simple array, rather it is an object that implements the ArrayAccess interface.
	 *
	 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
	 *
	 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
	 */
	if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
		// Create $fob object from filter tag, to use below
		$fob       = $wp_filter[ $tag ];
		$callbacks = &$wp_filter[ $tag ]->callbacks;
	} else {
		$callbacks = &$wp_filter[ $tag ];
	}

	// Exit if there aren't any callbacks for specified priority
	if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
		return false;
	}

	// Loop through each filter for the specified priority, looking for our class & method
	foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {

		// Filter should always be an array - array( $this, 'method' ), if not goto next
		if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
			continue;
		}

		// If first value in array is not an object, it can't be a class
		if ( ! is_object( $filter['function'][0] ) ) {
			continue;
		}

		// Method doesn't match the one we're looking for, goto next
		if ( $filter['function'][1] !== $method_name ) {
			continue;
		}

		// Method matched, now let's check the Class
		if ( get_class( $filter['function'][0] ) === $class_name ) {

			// WordPress 4.7+ use core remove_filter() since we found the class object
			if ( isset( $fob ) ) {
				// Handles removing filter, reseting callback priority keys mid-iteration, etc.
				$fob->remove_filter( $tag, $filter['function'], $priority );

			} else {
				// Use legacy removal process (pre 4.7)
				unset( $callbacks[ $priority ][ $filter_id ] );
				// and if it was the only filter in that priority, unset that priority
				if ( empty( $callbacks[ $priority ] ) ) {
					unset( $callbacks[ $priority ] );
				}
				// and if the only filter for that tag, set the tag to an empty array
				if ( empty( $callbacks ) ) {
					$callbacks = array();
				}
				// Remove this filter from merged_filters, which specifies if filters have been sorted
				unset( $GLOBALS['merged_filters'][ $tag ] );
			}

			return true;
		}
	}

	return false;
}

/**
 * Remove Class Action Without Access to Class Object
 *
 * In order to use the core WordPress remove_action() on an action added with the callback
 * to a class, you either have to have access to that class object, or it has to be a call
 * to a static method.  This method allows you to remove actions with a callback to a class
 * you don't have access to.
 *
 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
 *
 * @param string $tag Action to remove
 * @param string $class_name Class name for the action's callback
 * @param string $method_name Method name for the action's callback
 * @param int $priority Priority of the action (default 10)
 *
 * @return bool               Whether the function is removed.
 */
function wc_gzd_remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
	wc_gzd_remove_class_filter( $tag, $class_name, $method_name, $priority );
}

function wc_gzd_replace_label_shortcodes( $html, $replacements ) {
	$needs_closing = array();
	$original_html = $html;

	foreach ( $replacements as $search => $replace ) {
		if ( strstr( $search, '{/' ) ) {
			$opener = str_replace( '{/', '{', $search );

			// Check whether the closing tag is missing
			if ( ! strstr( $original_html, $search ) && strstr( $original_html, $opener ) ) {
				$needs_closing[ $search ] = $replace;
			// Check whether the closing tag exists but the opener is missing
			} elseif ( strstr( $original_html, $search ) && ! strstr( $original_html, $opener ) && array_key_exists( $opener, $replacements ) ) {
				$needs_closing[ $opener ] = $replacements[ $opener ];
			}
		}

		$html = str_replace( $search, $replace, $html );
	}

	/**
	 * Close missing opened/closed placeholders
	 */
	foreach( $needs_closing as $search => $replace ) {
		if ( strstr( $search, '{/' ) ) {
			$html = $html . $replace;
		} else {
			$html = $replace . $html;
		}
	}

	global $shortcode_tags;
	$original_shortcode_tags = $shortcode_tags;
	$shortcode_tags          = array();

	add_shortcode( 'page', '_wc_gzd_page_shortcode' );

	foreach ( wc_gzd_get_legal_pages() as $legal_page => $title ) {
		add_shortcode( $legal_page, '_wc_gzd_legal_page_shortcode' );
	}

	$html = do_shortcode( $html );

	$shortcode_tags = $original_shortcode_tags;

	return $html;
}

function _wc_gzd_page_shortcode( $atts, $content = '' ) {
	$atts = wp_parse_args( $atts, array(
		'id'     => 0,
		'target' => '_blank',
		'text'   => '',
		'url'    => '',
	) );

	if ( ( empty( $atts['id'] ) || ! get_post( $atts['id'] ) ) && empty( $atts['url'] ) ) {
		return false;
	}

	if ( empty( $content ) ) {
		if ( empty( $atts['text'] ) ) {
			$content = get_the_title( $atts['id'] );
		} else {
			$content = $atts['text'];
		}
	}

	$url = ( empty( $atts['url'] ) ? get_permalink( $atts['id'] ) : $atts['url'] );

	return '<a href="' . esc_url( $url ) . '" target="' . esc_attr( $atts['target'] ) . '">' . $content . '</a>';
}

function _wc_gzd_legal_page_shortcode( $atts, $content, $tag ) {
	$atts       = wp_parse_args( $atts, array() );
	$atts['id'] = wc_get_page_id( $tag );

	return _wc_gzd_page_shortcode( $atts, $content );
}

function woocommerce_gzd_show_add_more_variants_notice( $product ) {
	if ( 'variable' === $product->get_type() && apply_filters( "woocommerce_gzd_show_variable_more_variants_notice", false, $product ) ) {
		return true;
	}

	return false;
}

function woocommerce_gzd_get_more_variants_notice( $product ) {
	$text  = apply_filters( "woocommerce_gzd_variable_more_variants_notice_text", __( 'More variants available', 'woocommerce-germanized' ), $product );
	$html = '<span class="small smaller wc-gzd-additional-info more-variants-available-info">' . $text . '</span>';

	return $html;
}

/**
 * Variable Pricing
 */
add_filter( 'woocommerce_format_price_range', 'woocommmerce_gzd_price_range', 10, 3 );

function woocommmerce_gzd_price_range( $price_html, $from, $to ) {

	/**
	 * Filter to decide whether Germanized should adjust the price range format or not.
	 *
	 * @param bool $adjust Whether to adjust price range format or not.
	 *
	 * @since 2.2.6
	 *
	 */
	if ( ! apply_filters( 'woocommerce_gzd_adjust_price_range_format', true ) ) {
		return $price_html;
	}

	$format     = woocommerce_gzd_get_price_range_format();
	$price_html = str_replace( array(
		'{min_price}',
		'{max_price}'
	), array( is_numeric( $from ) ? wc_price( $from ) : $from, is_numeric( $to ) ? wc_price( $to ) : $to ), $format );

	return $price_html;
}

function woocommerce_gzd_price_range_format_is_min_price() {
	return strpos( woocommerce_gzd_get_price_range_format(), '{max_price}' ) === false;
}

function woocommerce_gzd_get_price_range_format() {
	return apply_filters( 'woocommerce_gzd_price_range_format', get_option( 'woocommerce_gzd_price_range_format_text', __( '{min_price} &ndash; {max_price}', 'woocommerce-germanized' ) ) );
}

function woocommerce_gzd_get_unit_price_range_format() {
	return apply_filters( 'woocommerce_gzd_unit_price_range_format', __( '{min_price} &ndash; {max_price}', 'woocommerce-germanized' ) );
}

function woocommerce_gzd_format_unit_price_range( $min_price, $max_price ) {
	add_filter( 'woocommerce_gzd_price_range_format', 'woocommerce_gzd_get_unit_price_range_format', 10 );
	$formatted = wc_format_price_range( $min_price, $max_price );
	remove_filter( 'woocommerce_gzd_price_range_format', 'woocommerce_gzd_get_unit_price_range_format', 10 );

	return $formatted;
}

function wc_gzd_get_default_revocation_address() {
	$countries = isset( WC()->countries ) && WC()->countries ? WC()->countries : false;
	$default   = '';

	if ( $countries ) {
		$default = $countries->get_formatted_address( array(
			'company'   => get_bloginfo( 'name' ),
			'city'      => $countries->get_base_city(),
			'country'   => $countries->get_base_country(),
			'address_1' => $countries->get_base_address(),
			'address_2' => $countries->get_base_address_2(),
			'postcode'  => $countries->get_base_postcode(),
		) );
	}

	$address = str_replace( "<br/>", "\n", $default );

	return $address;
}

function wc_gzd_get_formatted_revocation_address() {
	$legacy  = get_option( 'woocommerce_gzd_revocation_address' );
	$address = wc_gzd_get_default_revocation_address();

	if ( ! empty( $legacy ) ) {
		$address = $legacy;
	}

	return nl2br( $address );
}

/**
 * @param WP_Error $error
 */
function wc_gzd_wp_error_has_errors( $error ) {
	if ( is_callable( array( $error, 'has_errors' ) ) ) {
		return $error->has_errors();
	} else {
		$errors = $error->errors;

		return ( ! empty( $errors ) ? true : false );
	}
}

/**
 * @param WC_Email $email
 */
function wc_gzd_get_email_helper( $email ) {
	return new WC_GZD_Email_Helper( $email );
}

function wc_gzd_switch_to_email_locale( $email, $lang = false ) {
	do_action( 'woocommerce_gzd_switch_email_locale', $email, $lang );
}

function wc_gzd_restore_email_locale( $email ) {
	do_action( 'woocommerce_gzd_restore_email_locale', $email );
}

/**
 * Switch Germanized to site language.
 *
 * @since 3.1.0
 */
function wc_gzd_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init WCG locale.
		WC_germanized()->load_plugin_textdomain();

		if ( function_exists( 'WC_germanized_pro' ) ) {
			WC_germanized_pro()->load_plugin_textdomain();
		}
	}
}

/**
 * Switch Germanized language to original.
 *
 * @since 3.1.0
 */
function wc_gzd_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init WCG locale.
		WC_germanized()->load_plugin_textdomain();

		if ( function_exists( 'WC_germanized_pro' ) ) {
			WC_germanized_pro()->load_plugin_textdomain();
		}
	}
}

function wc_gzd_format_unit( $unit ) {
	$html = '';

	if ( ! empty( $unit ) ) {
		$html = '<span class="unit">' . $unit . '</span>';
	}

	return $html;
}

function wc_gzd_format_unit_base( $unit_base ) {
	/**
	 * Filter that allows changing the amount which is used to determine whether
	 * the base for the unit price should be skipped or not. Defaults to 1.
	 *
	 * @param int $amount The amount.
	 *
	 * @since 1.0.0
	 */
	$hide_amount = apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 );
	$html        = '';

	if ( '' !== $unit_base ) {
		$html = ( $unit_base != $hide_amount ? '<span class="unit-base">' . $unit_base . '</span>' : '' );
	}

	return $html;
}

function wc_gzd_format_product_units_decimal( $unit_product ) {
	return str_replace( '.', ',', $unit_product );
}

function wc_gzd_format_unit_price( $price, $unit, $unit_base, $product_units = '' ) {
	$text = get_option( 'woocommerce_gzd_unit_price_text' );

	/**
	 * Filter to adjust the unit price base separator.
	 *
	 * @param string $separator The separator.
	 *
	 * @since 1.0.0
	 */
	$separator = apply_filters( 'wc_gzd_unit_price_base_seperator', ' ' );

	$replacements = array(
		'{product_units}' => $product_units,
		'{unit_price}'    => $price,
		'{base_price}'    => $price,
		'{unit}'          => $unit,
		'{base}'          => $unit_base,
		'{price}'         => $price . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $unit_base . $separator . $unit,
	);

	if ( ! empty( $replacements['{product_units}'] ) ) {
		$replacements['{product_units}'] = '<span class="product-units">' . $replacements['{product_units}'] . '</span>';
	}

	$html = wc_gzd_replace_label_shortcodes( $text, $replacements );

	/**
	 * Filter to adjust the formatted unit price.
	 *
	 * @param string $html  The html output
	 * @param string $price The price html
	 * @param string $unit_base The unit base html
	 * @param string $unit The unit html
	 *
	 * @since 3.2.0
	 */
	return apply_filters( 'woocommerce_gzd_formatted_unit_price', $html, $price, $unit_base, $unit );
}

function wc_gzd_enable_additional_costs_split_tax_calculation() {
	return 'yes' === get_option( 'woocommerce_gzd_shipping_tax' );
}

function wc_gzd_additional_costs_include_tax() {
	/**
	 * Filter to adjust whether additional costs (e.g. shipping costs, fees) are treated including taxes or not.
	 * This filter will only be applied in case split tax calculation is enabled within the Germanized settings.
	 *
	 * @param boolean $include_tax Whether additional costs include taxes or not
	 *
	 * @since 3.3.4
	 */
	return ( wc_gzd_enable_additional_costs_split_tax_calculation() && apply_filters( 'woocommerce_gzd_additional_costs_include_tax', wc_prices_include_tax() ) );
}

function wc_gzd_base_country_is_eu() {
	$eu_countries = WC()->countries->get_european_union_countries();
	$base_country = WC()->countries->get_base_country();

	return in_array( $base_country, $eu_countries );
}

function wc_gzd_get_cart_defect_descriptions( $items = false ) {
	$items        = $items ? (array) $items : WC()->cart->get_cart();
	$descriptions = array();
	$is_cart      = true;

	if ( ! empty( $items ) ) {
		foreach ( $items as $cart_item_key => $values ) {
			if ( is_a( $values, 'WC_Order_Item_Product' ) ) {
				if ( $gzd_item = wc_gzd_get_order_item( $values ) ) {
					if ( $gzd_item->get_defect_description() ) {
						if ( ! empty( $values->get_product_id() ) && ! array_key_exists( $values->get_product_id(), $descriptions ) ) {
							$descriptions[ wp_kses_post( $values->get_name() ) ] = $gzd_item->get_defect_description();
						}
					}
				}

				$is_cart  = false;
			} elseif ( isset( $values['data'] ) ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

				if ( is_a( $_product, 'WC_Product' ) ) {
					$_gzd_product = wc_gzd_get_gzd_product( $_product );

					if ( $_gzd_product->is_defective_copy() && ! array_key_exists( $_product->get_id(), $descriptions ) ) {
						$descriptions[ wp_kses_post( $_product->get_name() ) ] = $_gzd_product->get_formatted_defect_description();
					}
				}
			}
		}
	}

	if ( ! $is_cart ) {
		/**
		 * Returns a list of defect descriptions on a per product base.
		 *
		 * @param string[] $descriptions The defect descriptions as key => value pairs.
		 * @param array $items The order items.
		 *
		 * @since 3.8.0
		 */
		return apply_filters( 'woocommerce_gzd_order_defect_descriptions', $descriptions, $items );
	} else {
		/**
		 * Returns a list of defect descriptions on a per product base.
		 *
		 * @param string[] $descriptions The defect descriptions as key => value pairs.
		 * @param array $items The cart items.
		 *
		 * @since 3.8.0
		 */
		return apply_filters( 'woocommerce_gzd_cart_defect_descriptions', $descriptions, $items );
	}
}

function wc_gzd_print_item_defect_descriptions( $descriptions, $echo = false ) {
	$strings = array();

	foreach( $descriptions as $name => $description ) {
		$strings[] = sprintf( _x( '%1$s (%2$s)', 'defect-descriptions', 'woocommerce-germanized' ), wp_kses( $description, array( 'strong' => array(), 'i' => array(), 'em' => array(), 'a' => array(), 'b' => array() ) ), $name );
	}

	$string = implode( apply_filters( 'woocommerce_gzd_item_defect_descriptions_separator', '; ' ), $strings );

	if ( $echo ) {
		echo $string;
	}

	return $string;
}

function wc_gzd_get_post_plain_content( $content_post, $shortcodes_allowed = array() ) {
	global $post;
	$reset_post = $post;

	if ( is_numeric( $content_post ) ) {
		$post = get_post( $content_post );
	} else {
		$post = $content_post;
	}

	$keep_active = implode( "|", $shortcodes_allowed );
	$content     = '';

	if ( is_a( $post, 'WP_Post' ) ) {
		setup_postdata( $post );

		do_action( 'woocommerce_gzd_before_get_post_plain_content', $post, $shortcodes_allowed );

		$content = $post->post_content;

		/**
		 * Remove non-exempted shortcodes from content
		 */
		if ( ! empty( $keep_active ) ) {
			$content = preg_replace("~(?:\[/?)(?!(?:$keep_active))[^/\]]+/?\]~s", '', $content );
		} else {
			$content = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $content );
		}

		$content = preg_replace( "/<p[^>]*>(?:\s|&nbsp;)*<\/p>/",  '', $content );
		$content = apply_filters( 'the_content', $content );

		$content = str_replace( ']]>', ']]&gt;', $content );

		do_action( 'woocommerce_gzd_after_get_post_plain_content', $post, $shortcodes_allowed );

		/**
		 * Reset post data to keep global loop valid.
		 */
		if ( $reset_post ) {
			setup_postdata( $reset_post );
		}
	}

	// Remove empty p tags
	$content = preg_replace( "/<p[^>]*>(?:\s|&nbsp;)*<\/p>/",  '', $content );
	$content = trim( $content );

	return apply_filters( 'woocommerce_gzd_post_plain_content', $content, $content_post );
}