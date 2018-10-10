<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Adds Germanized Shortcodes
 *
 * @class 		WC_GZD_Shortcodes
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Shortcodes {
	
	/**
	 * Initializes Shortcodes
	 */
	public static function init() {

		// Rename the original WooCommerce Shortcode tag so that we can add our custom function to it
		add_filter( 'add_to_cart_shortcode_tag', __CLASS__ . '::replace_add_to_cart_shortcode', 10 );
		
		// Define shortcodes
		$shortcodes = array(
			'revocation_form'             => __CLASS__ . '::revocation_form',
			'payment_methods_info'		  => __CLASS__ . '::payment_methods_info',
			'ekomi_badge'				  => __CLASS__ . '::ekomi_badge',
			'ekomi_widget'				  => __CLASS__ . '::ekomi_widget',
			'add_to_cart'				  => __CLASS__ . '::gzd_add_to_cart',
			'gzd_feature'				  => __CLASS__ . '::gzd_feature',
			'gzd_vat_info'				  => __CLASS__ . '::gzd_vat_info',
			'gzd_sale_info'				  => __CLASS__ . '::gzd_sale_info',
			'gzd_complaints'			  => __CLASS__ . '::gzd_complaints',
			'gzd_product_unit_price'      => __CLASS__ . '::gzd_product_unit_price',
			'gzd_product_units'           => __CLASS__ . '::gzd_product_units',
			'gzd_product_delivery_time'   => __CLASS__ . '::gzd_product_delivery_time',
			'gzd_product_tax_notice'      => __CLASS__ . '::gzd_product_tax_notice',
			'gzd_product_shipping_notice' => __CLASS__ . '::gzd_product_shipping_notice',
			'gzd_product_cart_desc'       => __CLASS__ . '::gzd_product_cart_desc',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "gzd_{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

	}

	protected static function get_gzd_product_shortcode( $atts, $function_name = '' ) {
		if ( empty( $function_name ) || ! function_exists( $function_name ) ) {
			return;
		}

		global $product;

		$content = '';

		$atts = wp_parse_args( $atts, array(
			'product' => '',
		) );

		if ( ! empty( $atts['product'] ) ) {
			$product = wc_get_product( $atts['product'] );
		}

		if ( ! empty( $product ) && is_a( $product, 'WC_Product' ) ) {
			ob_start();
			call_user_func( $function_name );
			$content = ob_get_clean();
		}

		return $content;
	}

	public static function gzd_product_unit_price( $atts ) {
		return apply_filters( 'woocommerce_gzd_shortcode_product_unit_price_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_price_unit' ), $atts );
	}

	public static function gzd_product_units( $atts ) {
		return apply_filters( 'woocommerce_gzd_shortcode_product_units_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_product_units' ), $atts );
	}

	public static function gzd_product_delivery_time( $atts ) {
		return apply_filters( 'woocommerce_gzd_shortcode_product_delivery_time_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_delivery_time_info' ), $atts );
	}

	public static function gzd_product_tax_notice( $atts ) {
		return apply_filters( 'woocommerce_gzd_shortcode_product_tax_notice_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_tax_info' ), $atts );
	}

	public static function gzd_product_shipping_notice( $atts ) {
		return apply_filters( 'woocommerce_gzd_shortcode_product_shipping_notice_html', self::get_gzd_product_shortcode( $atts, 'woocommerce_gzd_template_single_shipping_costs_info' ), $atts );
	}

	public static function gzd_product_cart_desc( $atts ) {
		global $product;

		$content = '';

		$atts = wp_parse_args( $atts, array(
			'product' => '',
		) );

		if ( ! empty( $atts['product'] ) ) {
			$product = wc_get_product( $atts['product'] );
		}

		if ( ! empty( $product ) && is_a( $product, 'WC_Product' ) ) {
			$content = '<div class="wc-gzd-item-desc item-desc">' . do_shortcode( wc_gzd_get_gzd_product( $product )->get_mini_desc() ) . '</div>';
		}

		return $content;
	}

	public static function gzd_add_price_suffixes( $price, $org_product ) {
		global $product;
		$product = $org_product;

		ob_start();
		woocommerce_gzd_template_single_legal_info();
		$legal = ob_get_clean();

		ob_start();
		woocommerce_gzd_template_single_price_unit();
		$unit = ob_get_clean();

		return $price . strip_tags( $unit . $legal, '<span><a>' );
	}

	public static function gzd_add_to_cart( $atts ) {
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'gzd_add_price_suffixes' ), 10, 2 );
		$html = WC_Shortcodes::product_add_to_cart( $atts );
		remove_filter( 'woocommerce_get_price_html', array( __CLASS__, 'gzd_add_price_suffixes' ), 10 );

		return $html;
	}

	public static function replace_add_to_cart_shortcode( $shortcode ) {
		return 'add_to_cart_legacy';
	}

	public static function gzd_complaints( $atts ) {
		$texts = array(
			'dispute' =>  wc_gzd_get_dispute_resolution_text(),
		);

		foreach( $texts as $key => $text ) {
			$texts[ $key ] = wpautop( str_replace( array( 'https://ec.europa.eu/consumers/odr', 'http://ec.europa.eu/consumers/odr/' ), '<a href="https://ec.europa.eu/consumers/odr" target="_blank">https://ec.europa.eu/consumers/odr</a>', $text ) );
		}

		ob_start();
		wc_get_template( 'global/complaints.php', array( 'dispute_text' => $texts[ 'dispute' ] ) );
		$return = '<div class="woocommerce woocommerce-gzd woocommerce-gzd-complaints-shortcode">' . ob_get_clean() . '</div>';

		return $return;
	}

	/**
	 * Returns revocation_form template html
	 *  
	 * @param  array $atts 
	 * @return string revocation form html       
	 */
	public static function revocation_form( $atts ) {
		ob_start();
		wc_get_template( 'forms/revocation-form.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';
		return $return;
	}

	/**
	 * Returns payment methods info html
	 *  
	 * @param  array $atts
	 * @return string
	 */
	public static function payment_methods_info( $atts ) {
		
		WC_GZD_Payment_Gateways::instance()->manipulate_gateways();

		ob_start();
		wc_get_template( 'global/payment-methods.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';
		return $return;
	
	}

	/**
	 * Returns eKomi Badge html
	 *  
	 * @param  array $atts 
	 * @return string     
	 */
	public static function ekomi_badge( $atts ) {

		return WC_germanized()->ekomi->get_badge( $atts );
	
	}

	/**
	 * Returns eKomi Widget html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public static function ekomi_widget( $atts ) {

		return WC_germanized()->ekomi->get_widget( $atts );
	
	}

	/**
	 * Returns header feature shortcode
	 *  
	 * @param  array $atts    
	 * @param  string $content 
	 * @return string          
	 */
	public static function gzd_feature( $atts, $content = '' ) {

		extract( shortcode_atts( array('icon' => ''), $atts ) );
		return ( !empty( $icon ) ? '<i class="fa fa-' . $icon . '"></i> ' : '' ) . $content;
	
	}

	/**
	 * Returns VAT info
	 *  
	 * @param  array $atts    
	 * @param  string $content 
	 * @return string          
	 */
	public static function gzd_vat_info( $atts, $content = '' ) {
		ob_start();
		wc_get_template( 'footer/vat-info.php' );
		return ob_get_clean();
	}

	/**
	 * Returns Sale info
	 *  
	 * @param  array $atts    
	 * @param  string $content 
	 * @return string          
	 */
	public static function gzd_sale_info( $atts, $content = '' ) {
		ob_start();
		wc_get_template( 'footer/sale-info.php' );
		return ob_get_clean();
	}

}