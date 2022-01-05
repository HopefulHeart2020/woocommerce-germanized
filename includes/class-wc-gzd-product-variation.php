<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Product Variation
 *
 * @class        WC_GZD_Product_Variation
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Product_Variation extends WC_GZD_Product {

	/**
	 * @var WC_GZD_Product
	 */
	protected $parent = null;

	protected $gzd_variation_level_meta = array(
		'unit_price'         => '',
		'unit_price_regular' => '',
		'unit_price_sale'    => '',
		'unit_price_auto'    => '',
		'service'            => '',
		'used_good'          => '',
		'defective_copy'     => '',
		'mini_desc'          => '',
		'defect_description' => '',
	);

	protected $gzd_variation_inherited_meta_data = array(
		'unit',
		'unit_base',
		'unit_product',
		'sale_price_label',
		'sale_price_regular_label',
		'free_shipping',
		'differential_taxation',
		'min_age',
		'default_delivery_time',
		'delivery_time_countries',
		'warranty_attachment_id'
	);

	protected $gzd_variation_forced_inherited_meta_data = array(
		'unit',
		'unit_base',
		'free_shipping',
		'differential_taxation',
	);

	public function get_gzd_parent() {
		if ( is_null( $this->parent ) ) {
			$this->parent = wc_gzd_get_product( $this->child->get_parent_id() );
		}

		return $this->parent;
	}

	public function get_forced_inherited_props() {
		return $this->gzd_variation_forced_inherited_meta_data;
	}

	public function get_prop( $prop, $context = 'view' ) {
		$meta_key = substr( $prop, 0, 1 ) !== '_' ? '_' . $prop : $prop;

		if ( in_array( $prop, array_keys( $this->gzd_variation_level_meta ) ) ) {
			$value = $this->child->get_meta( $meta_key, true, $context );

			if ( '' === $value ) {
				$value = $this->gzd_variation_level_meta[ $prop ];
			}

		} elseif ( in_array( $prop, $this->gzd_variation_inherited_meta_data ) ) {
			$value = $this->child->get_meta( $meta_key, true, $context ) ? $this->child->get_meta( $meta_key, true, $context ) : '';

			// Make sure forced inherited meta data (e.g. not choosable from admin view) is rejected if available
			if ( in_array( $prop, $this->gzd_variation_forced_inherited_meta_data ) ) {
				$value = '';
			}

			// Handle meta data keys which can be empty at variation level to cause inheritance
			if ( ! $value || '' === $value ) {
				if ( in_array( $prop, $this->gzd_variation_forced_inherited_meta_data ) || 'view' === $context ) {
					if ( $parent = $this->get_gzd_parent() ) {
						$value = $parent->get_wc_product()->get_meta( $meta_key, true, $context );
					}
				}
			}
		} else {
			$value = parent::get_prop( $prop, $context );
		}

		/**
		 * Filter to adjust a certain product variation property e.g. unit_price.
		 *
		 * The dynamic portion of the hook name, `$prop` refers to the product property e.g. unit_price.
		 *
		 * @param mixed $value The property value.
		 * @param WC_GZD_Product_Variation $gzd_product The GZD product instance.
		 * @param WC_Product_Variation $product The product instance.
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( "woocommerce_gzd_get_product_variation_{$prop}", $value, $this, $this->child );
	}

	public function get_unit( $context = 'view' ) {
		$unit = '';

		if ( $parent = $this->get_gzd_parent() ) {
			$unit = $parent->get_unit();
		}

		/** This filter is documented in includes/class-wc-gzd-product-variation.php */
		return apply_filters( "woocommerce_gzd_get_product_variation_unit", $unit, $this, $this->child );
	}

	/**
	 * Data is being handled by parent product.
	 *
	 * @param $unit
	 */
	public function set_unit( $unit ) {
		parent::set_unit( '' );
	}

	/**
	 * Data is being handled by parent product.
	 *
	 * @param $base
	 */
	public function set_unit_base( $base ) {
		parent::set_unit_base( '' );
	}

	/**
	 * Data is being handled by parent product.
	 *
	 * @param $free_shipping
	 */
	public function set_free_shipping( $free_shipping ) {
		$this->set_prop( 'free_shipping', '' );
	}

	/**
	 * Data is being handled by parent product.
	 *
	 * @param $diff_taxation
	 */
	public function set_differential_taxation( $diff_taxation ) {
		$this->set_prop( 'differential_taxation', '' );
	}

	public function get_delivery_time_slugs( $context = 'view' ) {
		$slugs = parent::get_delivery_time_slugs( $context );

		if ( 'save' !== $context && ! $this->delivery_times_need_update() ) {
			if ( $parent = $this->get_gzd_parent() ) {
				$object_id = $parent->get_id();
				$terms     = get_the_terms( $object_id, 'product_delivery_time' );

				/**
				 * Merge available delivery time slugs with parent slugs to make sure
				 * to allow parent delivery time as fallback.
				 */
				if ( false !== $terms && ! is_wp_error( $terms ) ) {
					$slugs = array_unique( array_merge( $slugs, wp_list_pluck( $terms, 'slug' ) ) );
				}
			}
		}

		return $slugs;
	}

	protected function is_valid_country_specific_delivery_time( $slug, $country ) {
		$delivery_times_parent = array();
		$default_parent        = false;

		if ( $parent = $this->get_gzd_parent() ) {
			$delivery_times_parent = $parent->get_country_specific_delivery_times();
			$default_parent        = $parent->get_default_delivery_time_slug();
		}

		$is_valid = parent::is_valid_country_specific_delivery_time( $slug, $country );

		/**
		 * Do now allow storing duplicate country specific delivery times
		 */
		if ( $is_valid && array_key_exists( $country, $delivery_times_parent ) && $delivery_times_parent[ $country ] == $slug ) {
			$is_valid = false;
		}

		/**
		 * Do not allow a variation to include country-specific delivery times matching the parent's default time
		 */
		if ( $is_valid && $default_parent && $slug == $default_parent ) {
			$is_valid = false;
		}

		return $is_valid;
	}
}