<?php

/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Subscriptions extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Subscriptions';
	}

	public static function get_path() {
		return 'woocommerce-subscriptions/woocommerce-subscriptions.php';
	}

	public static function get_version_data() {
		return static::parse_version_data( array(
			'version' => get_option( 'woocommerce_subscriptions_active_version', '1.0.0' ),
			'requires_at_least' => '2.0',
		) );
	}

	public function load() {
		add_filter( 'wcs_cart_totals_order_total_html', array( $this, 'set_tax_notice' ), 50, 2 );
		add_filter( 'woocommerce_gzd_product_classname', array( $this, 'product_classname' ), 10, 2 );
		add_filter( 'woocommerce_gzd_product_types_supporting_unit_prices', array(
			$this,
			'enable_unit_prices'
		), 10, 1 );

		/**
		 * Subscriptions recalculates the cart total amount by summing up
         * all total amounts (including rounded shipping amount). That may lead to
         * rounding issues when the split tax option is enabled.
		 */
		add_filter( 'woocommerce_subscriptions_calculated_total', array( $this, 'adjust_subscription_rounded_shipping' ), 100, 1 );

		/**
		 * Exclude certain keys from being copied to renewals
		 */
		add_filter( 'wcs_renewal_order_meta', array( $this, 'exclude_meta' ), 10, 3 );

		add_filter( 'woocommerce_gzd_enable_force_pay_order', array( $this, 'stop_forced_redirect' ), 10, 2 );
	}

	public function stop_forced_redirect( $redirect, $order ) {
		/**
		 * Woo Subscription specific payment method change flag.
         * Always allow changing payment method for subscriptions.
		 */
	    if ( isset( $_GET['change_payment_method'] ) ) {
	        $redirect = false;
        }

	    return $redirect;
    }

	public function exclude_meta( $meta ) {
		$excluded = array( '_dhl_services' );

		foreach ( $meta as $index => $meta_data ) {
			if ( ! empty( $meta_data['meta_key'] ) && in_array( $meta_data['meta_key'], $excluded ) ) {
				unset( $meta[ $index ] );
			}
		}

		return $meta;
	}

	public function adjust_subscription_rounded_shipping( $total ) {
		if ( ! wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			return $total;
		}

		$shipping_methods = WC()->cart->calculate_shipping();
		$shipping_total   = wc_format_decimal( array_sum( wp_list_pluck( $shipping_methods, 'cost' ) ) );
		$total            = max( 0, round( WC()->cart->cart_contents_total + WC()->cart->tax_total + WC()->cart->shipping_tax_total + $shipping_total + WC()->cart->fee_total, WC()->cart->dp ) );

		return $total;
	}

	public function enable_unit_prices( $types ) {
		$types[] = 'subscription';
		$types[] = 'variable-subscription';

		return $types;
	}

	public function product_classname( $classname, $type ) {
		if ( 'variable-subscription' === $type ) {
			return 'WC_GZD_Product_Variable';
		} elseif( 'subscription_variation' === $type ) {
		    return 'WC_GZD_Product_Variation';
        }

		return $classname;
	}

	public function set_tax_notice( $price, $cart ) {

		/**
		 * Filter that allows disabling tax notice for subscription cart prices.
		 *
		 * @param bool $disable Whether to disable tax notice for subscription price or not.
		 *
		 * @since 2.0.0
		 *
		 */
		if ( ! apply_filters( 'woocommerce_gzd_show_tax_for_cart_subscription_price', true ) ) {
			return $price;
		}

		// Tax for inclusive prices
		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && 'incl' === wc_gzd_get_cart_tax_display_mode( $cart ) ) {
			$tax_array = wc_gzd_get_cart_taxes( $cart );

			ob_start();

			echo $price;
			echo '</td></tr>';

			if ( ! empty( $tax_array ) ) {
				$count = 0;
				foreach ( $tax_array as $tax ) {
					$count ++;
					$label = wc_gzd_get_tax_rate_label( $tax['tax']->rate );
					?>

                    <tr class="order-tax">
                    <th><?php echo $label; ?></th>
                    <td data-title="<?php echo esc_attr( $label ); ?>"><?php echo wc_price( $tax['amount'] ); ?>

					<?php if ( sizeof( $tax_array ) != $count ) : ?>
                        </td></tr>
					<?php endif; ?>

					<?php
				}
			}

			$price = ob_get_clean();
		}

		return $price;
	}
}