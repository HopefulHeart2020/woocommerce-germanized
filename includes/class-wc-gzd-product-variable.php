<?php

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

/**
 * WooCommerce Germanized Product Variable
 *
 * The WC_GZD_Product_Variable Class is used to offer additional functionality for every variable product.
 *
 * @class 		WC_GZD_Product
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product_Variable extends WC_GZD_Product {

    protected $unit_prices_array = array();

	/**
	 * Get the min or max variation unit regular price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_unit_regular_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['regular_price'] ) : end( $prices['regular_price'] );

		return apply_filters( 'woocommerce_gzd_get_variation_unit_regular_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation unit sale price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_unit_sale_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['sale_price'] ) : end( $prices['sale_price'] );

		return apply_filters( 'woocommerce_gzd_get_variation_unit_sale_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation (active) unit price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_unit_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['price'] ) : end( $prices['price'] );

		return apply_filters( 'woocommerce_gzd_get_variation_unit_price', $price, $this, $min_or_max, $display );
	}

	public function is_on_unit_sale() {
		$is_on_sale = false;
		$prices     = $this->get_variation_unit_prices();

		if ( $prices['regular_price'] !== $prices['sale_price'] && $prices['sale_price'] === $prices['price'] ) {
			$is_on_sale = true;
		}

		return apply_filters( 'woocommerce_gzd_product_is_on_unit_sale', $is_on_sale, $this );
	}

	public function has_unit() {
		$prices = $this->get_variation_unit_prices();

		if ( $this->unit && $prices['regular_price'] && $this->unit_base ) {
            return true;
        }

		return false;
	}

	public function has_unit_fields() {
		if ( $this->unit && $this->unit_base )
			return true;
		return false;
	}

	public function get_price_html_from_to( $from, $to, $show_labels = true ) {

		$sale_label         = ( $show_labels ? $this->get_sale_price_label() : '' );
		$sale_regular_label = ( $show_labels ? $this->get_sale_price_regular_label() : '' );

		$price = ( ! empty( $sale_label ) ? '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span>' : '' ) . ' <del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> ' . ( ! empty( $sale_regular_label ) ? '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' : '' ) . '<ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

		return apply_filters( 'woocommerce_germanized_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_unit_html( $price = '' ) {

		if ( get_option( 'woocommerce_gzd_unit_price_enable_variable' ) === 'no' )
			return '';
		
		$prices = $this->get_variation_unit_prices( true );

		$text = get_option( 'woocommerce_gzd_unit_price_text' );

		if ( $this->has_unit() ) {

			$min_price     = current( $prices['price'] );
			$max_price     = end( $prices['price'] );
            $min_reg_price = current( $prices['regular_price'] );
            $max_reg_price = end( $prices['regular_price'] );

			if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {

                if ( $min_price !== $max_price ) {
                    $price = wc_format_price_range( $min_price, $max_price );
                } elseif ( $this->is_on_sale() && $min_reg_price === $max_reg_price ) {
                    $price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
                } else {
                    $price = wc_price( $min_price );
                }

                $price = apply_filters( 'woocommerce_gzd_variable_unit_price_html', $price, $this );

            } else {

                $price = $min_price !== $max_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce-germanized' ), wc_price( $min_price ), wc_price( $max_price ) ) : wc_price( $min_price );

                if ( $this->is_on_sale() ) {
                    $min_regular_price = current( $prices['regular_price'] );
                    $max_regular_price = end( $prices['regular_price'] );
                    $regular_price     = $min_regular_price !== $max_regular_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce-germanized' ), wc_price( $min_regular_price ), wc_price( $max_regular_price ) ) : wc_price( $min_regular_price );
                    $price        	   = apply_filters( 'woocommerce_gzd_variable_unit_sale_price_html', $this->get_price_html_from_to( $regular_price, $price, false ), $this );
                } else {
                    $price 	   		   = apply_filters( 'woocommerce_gzd_variable_unit_price_html', $price, $this );
                }
            }

            if ( strpos( $text, '{price}' ) !== false ) {
                $replacements = array(
                    '{price}' => $price . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base(),
                );
            } else {
                $replacements = array(
                    '{base_price}' => $price,
                    '{unit}'       => '<span class="unit">' . $this->get_unit() . '</span>',
                    '{base}'       => $this->get_unit_base(),
                );
            }

            $price = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

		return apply_filters( 'woocommerce_gzd_unit_price_html', $price, $this );
	}

	/**
	 * Get an array of all sale and regular unit prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * Can be filtered by plugins which modify costs, but otherwise will include the raw meta costs unlike get_price() which runs costs through the woocommerce_get_price filter.
	 * This is to ensure modified prices are not cached, unless intended.
	 *
	 * @param  bool $display Are prices for display? If so, taxes will be calculated.
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_unit_prices( $display = false ) {

		if ( ! $this->is_type( 'variable' ) )
			return false;

		// Product doesn't apply for unit pricing
		if ( ! $this->has_unit_fields() )
			return false;

		global $wp_filter;

		$transient_name = 'wc_gzd_var_unit_prices_' . wc_gzd_get_crud_data( $this, 'id' );

		/**
		 * Create unique cache key based on the tax location (affects displayed/cached prices), product version and active price filters.
		 * DEVELOPERS should filter this hash if offering conditonal pricing to keep it unique.
		 * @var string
		 */
		if ( $display ) {
			$price_hash = array( get_option( 'woocommerce_tax_display_shop', 'excl' ), WC_Tax::get_rates() );
		} else {
			$price_hash = array( false );
		}

		$filter_names = array( 'woocommerce_gzd_variation_unit_prices_price', 'woocommerce_gzd_variation_unit_prices_regular_price', 'woocommerce_gzd_variation_unit_prices_sale_price' );

		foreach ( $filter_names as $filter_name ) {
			if ( ! empty( $wp_filter[ $filter_name ] ) ) {
				$price_hash[ $filter_name ] = array();

				foreach ( $wp_filter[ $filter_name ] as $priority => $callbacks ) {
					$price_hash[ $filter_name ][] = array_values( wp_list_pluck( $callbacks, 'function' ) );
				}
			}
		}

		$price_hash = md5( json_encode( apply_filters( 'woocommerce_gzd_get_variation_unit_prices_hash', $price_hash, $this, $display ) ) );

		// If the value has already been generated, we don't need to grab the values again.
		if ( empty( $this->unit_prices_array[ $price_hash ] ) ) {

			// Get value of transient
			$this->unit_prices_array = array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) );

			// If the product version has changed, reset cache
			if ( empty( $this->unit_prices_array['version'] ) || $this->unit_prices_array['version'] !== WC_Cache_Helper::get_transient_version( 'product' ) ) {
				$this->unit_prices_array = array( 'version' => WC_Cache_Helper::get_transient_version( 'product' ) );
			}

			// If the prices are not stored for this hash, generate them
			if ( empty( $this->unit_prices_array[ $price_hash ] ) ) {

				$prices           = array();
				$regular_prices   = array();
				$sale_prices      = array();
				$variation_ids    = wc_gzd_get_variable_visible_children( $this->child );

				foreach ( $variation_ids as $variation_id ) {
					
					if ( $variation = wc_gzd_get_variation( $this->child, $variation_id ) ) {

						$gzd_variation = wc_gzd_get_gzd_product( $variation );

						// E.g. recalculate unit price for dynamic pricing plugins
						do_action( 'woocommerce_gzd_before_get_variable_variation_unit_price', $gzd_variation );
					
						$price         = apply_filters( 'woocommerce_gzd_variation_unit_prices_price', $gzd_variation->get_unit_price_raw(), $variation, $this );
						$regular_price = apply_filters( 'woocommerce_gzd_variation_unit_prices_regular_price', $gzd_variation->get_unit_regular_price(), $variation, $this );
						$sale_price    = apply_filters( 'woocommerce_gzd_variation_unit_prices_sale_price', $gzd_variation->get_unit_sale_price(), $variation, $this );

						// If sale price does not equal price, the product is not yet on sale
						if ( $sale_price === $regular_price || $sale_price !== $price ) {
							$sale_price = $regular_price;
						}

						// If we are getting prices for display, we need to account for taxes
						if ( $display ) {
							if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
								$price         = '' === $price ? ''         : wc_gzd_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $price ) );
								$regular_price = '' === $regular_price ? '' : wc_gzd_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $regular_price ) );
								$sale_price    = '' === $sale_price ? ''    : wc_gzd_get_price_including_tax( $variation, array( 'qty' => 1, 'price' => $sale_price ) );
							} else {
								$price         = '' === $price ? ''         : wc_gzd_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $price ) );
								$regular_price = '' === $regular_price ? '' : wc_gzd_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $regular_price ) );
								$sale_price    = '' === $sale_price ? ''    : wc_gzd_get_price_excluding_tax( $variation, array( 'qty' => 1, 'price' => $sale_price ) );
							}
						}

						$prices[ $variation_id ]         = $price;
						$regular_prices[ $variation_id ] = $regular_price;
						$sale_prices[ $variation_id ]    = $sale_price;
					}
				}

				asort( $prices );
				asort( $regular_prices );
				asort( $sale_prices );

				$this->unit_prices_array[ $price_hash ] = array(
					'price'         => $prices,
					'regular_price' => $regular_prices,
					'sale_price'    => $sale_prices
				);

				set_transient( $transient_name, json_encode( $this->unit_prices_array ), DAY_IN_SECONDS * 30 );
			}

			$this->unit_prices_array[ $price_hash ] = apply_filters( 'woocommerce_gzd_variation_unit_prices', $this->unit_prices_array[ $price_hash ], $this, $display );
		}

		return $this->unit_prices_array[ $price_hash ];
	}

}