<?php

class WC_GZD_Checkout {

	public $custom_fields       = array();
	public $custom_fields_admin = array();

	protected static $force_free_shipping_filter = false;

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {

		add_action( 'init', array( $this, 'init_fields' ), 30 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'set_custom_fields' ), 0, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'set_custom_fields_shipping' ), 0, 1 );

		// Add Fields to Order Edit Page
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_custom_fields_admin_billing' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_custom_fields_admin_shipping' ), 0, 1 );

		// Save Fields on order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_fields' ) );

		// Add Title to billing address format
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address' ), 0, 2 );

		// Support Checkout Field Managers (which are unable to map options to values)
		add_filter( 'woocommerce_gzd_custom_title_field_value', array( $this, 'set_title_field_mapping_editors' ), 10, 1 );

		// Add item desc to order
		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'set_order_item_meta_crud' ), 0, 4 );
		} else {
			add_action( 'woocommerce_order_add_product', array( $this, 'set_order_meta' ), 0, 5 );
		}

		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_order_meta_hidden' ), 0 );

		// Deactivate checkout shipping selection
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'remove_shipping_rates' ), 0 );

		// Add better fee taxation
		add_action( 'woocommerce_calculate_totals', array( $this, 'do_fee_tax_calculation' ), PHP_INT_MAX, 1 );
		// Pre WC 3.2
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'do_fee_tax_calculation_legacy' ), PHP_INT_MAX, 1 );

		// Disallow user order cancellation
		if ( get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) == 'yes' ) {
			add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'cancel_order_url' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_get_cancel_order_url_raw', array( $this, 'cancel_order_url' ), PHP_INT_MAX, 1 );
			add_filter( 'user_has_cap', array( $this, 'disallow_user_order_cancellation' ), 15, 3 );

			// Remove order stock right after confirmation is sent
			add_action( 'woocommerce_germanized_order_confirmation_sent', array( $this, 'maybe_reduce_order_stock' ), 5, 1 );
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_cancel_button' ), 10, 2 );

			// Woo 3.0 stock reducing checks - mark order as stock-reduced so that stock reducing fails upon second attempt
			add_action( 'woocommerce_reduce_order_stock', array( $this, 'set_order_stock_reduced_meta' ), 10, 1 );
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'maybe_disallow_order_stock_reducing' ), 10, 2 );
		}

		// Free Shipping auto select
		if ( get_option( 'woocommerce_gzd_display_checkout_free_shipping_select' ) == 'yes' ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'free_shipping_auto_select' ) );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_free_shipping_filter' ) );
		}

		// Pay for order
		add_action( 'wp', array( $this, 'force_pay_order_redirect' ), 15 );

		if ( get_option( 'woocommerce_gzd_checkout_disallow_belated_payment_method_selection' ) === 'yes' ) {
			add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'set_payment_url_to_force_payment' ), 10, 2 );
		}

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_parcel_delivery_data_transfer' ), 10, 2 );

		// Make sure that, just like in Woo core, the order submit button gets refreshed
		// Use a high priority to let other plugins do their adjustments beforehand
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_order_submit' ), 150, 1 );
	}

	public function refresh_order_submit( $fragments ) {

		$args = array(
			'include_nonce' => false,
		);

		if ( ! isset( $fragments['.woocommerce-checkout-payment'] ) ) {
			$args['include_nonce'] = true;
		}

		// Get checkout order submit fragment
		ob_start();
		woocommerce_gzd_template_order_submit( $args );
		$wc_gzd_order_submit = ob_get_clean();

		$fragments['.wc-gzd-order-submit'] = $wc_gzd_order_submit;

		return $fragments;
	}

	public function remove_cancel_button( $actions, $order ) {

		if ( isset( $actions['cancel'] ) )
			unset( $actions['cancel'] );

		return $actions;
	}

	public function order_parcel_delivery_data_transfer( $order_id, $posted ) {
		if ( $checkbox = wc_gzd_get_legal_checkbox( 'parcel_delivery' ) ) {
			if ( ! $checkbox->is_enabled() ) {
				return;
			}

			if ( ! wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( wc_gzd_get_chosen_shipping_rates( array( 'value' => 'id' ) ) ) ) {
				return;
			}

			$selected = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
				$selected = true;
			}

			update_post_meta( $order_id, '_parcel_delivery_opted_in', $selected ? 'yes' : 'no' );

			do_action( 'woocommerce_gzd_parcel_delivery_order_opted_in', $order_id, $selected );
		}
	}

	public function set_payment_url_to_force_payment( $url, $order ) {
		if ( strpos( $url, 'pay_for_order' ) !== false && apply_filters( 'woocommerce_gzd_enable_force_pay_order', true, $order ) ) {
			$url = add_query_arg( array( 'force_pay_order' => true ), $url );
		}

		return $url;
	}

	public function force_pay_order_redirect() {
		global $wp;

		if ( is_wc_endpoint_url( 'order-pay' ) && isset( $_GET[ 'force_pay_order' ] ) ) {

			// Manipulate $_POST
			$order_key = $_GET['key'];
			$order_id = absint( $wp->query_vars[ 'order-pay' ] );
			$order = wc_get_order( $order_id );

			if ( ! $order )
				return;

			if ( wc_gzd_get_crud_data( $order, 'order_key' ) != $order_key )
				return;

			// Check if gateway is available - otherwise don't force redirect - would lead to errors in pay_action
			$gateways = WC()->payment_gateways->get_available_payment_gateways();

			if ( ! isset( $gateways[ wc_gzd_get_crud_data( $order, 'payment_method' ) ] ) )
				return;

			if ( apply_filters( 'woocommerce_gzd_enable_force_pay_order', true, $order ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_force_pay_script' ), 20 );
				add_action( 'woocommerce_after_pay_action', array( $this, 'maybe_disable_force_pay_script' ), 20 );
			}
		}
	}

	public function maybe_disable_force_pay_script() {
		// Make sure we are not retrying to redirect if an error ocurred
		if ( wc_notice_count( 'error' ) > 0 ) {
			wp_safe_redirect( remove_query_arg( 'force_pay_order' ) );
			exit;
		}
	}

	public function enqueue_force_pay_script() {
		wp_enqueue_script( 'wc-gzd-force-pay-order' );
	}

	public function disable_terms_order_pay( $show ) {
		return false;
	}

	public function set_free_shipping_filter( $cart ) {
		self::$force_free_shipping_filter = true;
	}

	public function free_shipping_auto_select( $rates ) {
		$do_check = is_checkout() || is_cart() || self::$force_free_shipping_filter;

		if ( ! $do_check ) {
			return $rates;
        }

		$keep = array();
		$hide = false;

		// Legacy Support
		if ( isset( $rates['free_shipping'] ) ) {
			$keep[] = 'free_shipping';
			$hide = true;
		}

		// Check for cost-free shipping
		foreach ( $rates as $key => $rate ) {

		    if ( is_a( $rate, 'WC_Shipping_Rate' ) ) {
		        if ( 'free_shipping' === $rate->method_id ) {
		            $keep[] = $key;
		            $hide   = true;
                } elseif ( 'local_pickup' === $rate->method_id ) {
		            $keep[] = $key;
                } elseif( $rate->cost == 0 ) {
		            $keep[] = $key;
                }
            } else {
                // Do only hide if free_shipping exists
                if ( strpos( $key, 'free_shipping' ) !== false ) {
                    $hide = true;
                }

                // Always show local pickup
                if ( $rate->cost == 0 || strpos( $key, 'local_pickup' ) !== false ) {
                    $keep[] = $key;
                }
            }
		}

		// Unset all other rates
		if ( ! empty( $keep ) && $hide ) {

			// Unset chosen shipping method to avoid key errors
			if ( isset( WC()->session ) && ! is_null( WC()->session ) ) {
				unset( WC()->session->chosen_shipping_methods );
			}

			foreach ( $rates as $key => $rate ) {
				if ( ! in_array( $key, $keep ) ) {
                    unset( $rates[ $key ] );
                }
			}
		}

		return $rates;
	}

	public function add_payment_link( $order_id ) {

		$enabled = true;

		if ( get_option( 'woocommerce_gzd_order_pay_now_button' ) === 'no' ) {
			$enabled = false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order->needs_payment() ) {
			$enabled = false;
		}

		$disabled_methods = get_option( 'woocommerce_gzd_order_pay_now_button_disabled_methods', array() );

		if ( is_array( $disabled_methods ) && in_array( wc_gzd_get_crud_data( $order, 'payment_method' ), $disabled_methods ) ) {
			$enabled = false;
		}

		if ( apply_filters( 'woocommerce_gzd_show_order_pay_now_button', $enabled, $order_id ) ) {
			$url = $order->get_checkout_payment_url();

			if ( apply_filters( 'woocommerce_gzd_add_force_pay_order_parameter', true, $order_id ) ) {
				$url = add_query_arg( array( 'force_pay_order' => true ), $url );
			}

			wc_get_template( 'order/order-pay-now-button.php', array( 'url' => $url, 'order_id' => $order_id ) );
		}
	}

	public function maybe_reduce_order_stock( $order_id ) {
		if ( function_exists( 'wc_maybe_reduce_stock_levels' ) ) {
			wc_maybe_reduce_stock_levels( $order_id );
		} else {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				// Reduce order stock for non-cancellable orders
				if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! get_post_meta( wc_gzd_get_crud_data( $order, 'id' ), '_order_stock_reduced', true ), wc_gzd_get_crud_data( $order, 'id' ) ) ) {
					$order->reduce_order_stock();
				}
			}
		}
	}

	public function set_order_stock_reduced_meta( $order ) {
		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			$order = wc_gzd_set_crud_meta_data( $order, '_order_stock_reduced', 'yes' );
			$order->save();
		}
	}

	public function maybe_disallow_order_stock_reducing( $reduce_stock, $order ) {
		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			if ( wc_gzd_get_crud_data( $order, '_order_stock_reduced' ) ) {

				// Delete the meta so that third party plugins may reduce/change order stock later
				$order = wc_gzd_unset_crud_meta_data( $order, '_order_stock_reduced' );
				$order->save();

				return false;
			}
		}
		return $reduce_stock;
	}

	public function disallow_user_order_cancellation( $allcaps, $caps, $args ) {
		if ( isset( $caps[0] ) ) {
			switch ( $caps[0] ) {
				case 'cancel_order' :
					$allcaps['cancel_order'] = false;
					break;
			}
		}
		return $allcaps;
	}

	public function cancel_order_url( $url ) {

		// Default to home url
		$return = get_permalink( wc_get_page_id( 'shop' ) );

		// Extract order id and use order success page as return url
		$search = preg_match( '/order_id=([0-9]+)/', $url, $matches );

		if ( $search && isset( $matches[1] ) ) {
			$order_id = absint( $matches[1] );
			$order    = wc_get_order( $order_id );
			$return   = apply_filters( 'woocommerce_gzd_attempt_order_cancellation_url', add_query_arg( array( 'retry' => true ), $order->get_checkout_order_received_url(), $order ) );
		}

		return $return;
	}

	public function init_fields() {
		if ( get_option( 'woocommerce_gzd_checkout_address_field' ) == 'yes' ) {
			$this->custom_fields['title'] = array(
				'type' 	   => 'select',
				'required' => false,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'options'  => apply_filters( 'woocommerce_gzd_title_options', array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ) ),
				'before'   => 'first_name',
				'group'    => array( 'billing', 'shipping' ),
				'priority' => 0,
			);

			$this->custom_fields_admin['title'] = array(
				'before'   => 'first_name',
				'type'     => 'select',
				'options'  => apply_filters( 'woocommerce_gzd_title_options', array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ) ),
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'show'     => false,
				'priority' => 0,
			);
		}

		if ( get_option( 'woocommerce_gzd_checkout_phone_required' ) == 'no' ) {
			$this->custom_fields['phone'] = array(
				'before'   => '',
				'override' => true,
				'required' => false,
				'group'    => array( 'billing' )
			);
		}

		$this->custom_fields_admin = apply_filters( 'woocommerce_gzd_custom_checkout_admin_fields', $this->custom_fields_admin, $this );
		$this->custom_fields       = apply_filters( 'woocommerce_gzd_custom_checkout_fields', $this->custom_fields, $this );
	}

	public function set_title_field_mapping_editors( $val ) {

		$values = array(
			__( 'Mr.', 'woocommerce-germanized' ) => 1,
			__( 'Ms.', 'woocommerce-germanized' ) => 2,
		);

		if ( isset( $values[ $val ] ) )
			return $values[ $val ];

		return $val;
	}

	/**
	 * Recalculate fee taxes to split tax based on different tax rates contained within cart
	 *
	 * @param  WC_Cart $cart
	 */
	public function do_fee_tax_calculation( $cart ) {

		if ( 'yes' !== get_option( 'woocommerce_gzd_fee_tax' ) )
			return;

		if ( ! method_exists( $cart, 'set_fee_taxes' ) )
			return;

		$calculate_taxes = wc_tax_enabled() && ! WC()->customer->is_vat_exempt();

		// Do not calculate tax shares if tax calculation is disabled
		if ( ! $calculate_taxes )
			return;

		$fees = $cart->get_fees();

		if ( ! empty( $fees ) ) {

			$tax_shares    = wc_gzd_get_cart_tax_share( 'fee' );
			$fee_tax_total = 0;
			$fee_tax_data  = array();
			$new_fees      = array();

			foreach ( $cart->get_fees() as $key => $fee ) {

				if ( ! $fee->taxable && 'yes' !== get_option( 'woocommerce_gzd_fee_tax_force' ) )
					continue;

				// Calculate gross price if necessary
				if ( $fee->taxable ) {
					$fee_tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_tax       = WC_Tax::calc_tax( $fee->amount, $fee_tax_rates, false );
					$fee->amount += array_sum( $fee_tax );
				}

				// Set fee to nontaxable to avoid WooCommerce default tax calculation
				$fee->taxable = false;

				// Calculate tax class share
				if ( ! empty( $tax_shares ) ) {
					$fee_taxes = array();

					foreach ( $tax_shares as $rate => $class ) {
						$tax_rates                            = WC_Tax::get_rates( $rate );
						$tax_shares[ $rate ]['fee_tax_share'] = $fee->amount * $class['share'];
						$tax_shares[ $rate ]['fee_tax']       = WC_Tax::calc_tax( ( $fee->amount * $class['share'] ), $tax_rates, true );

						$fee_taxes += $tax_shares[ $rate ]['fee_tax'];
					}

					foreach ( $tax_shares as $rate => $class ) {
						foreach ( $class['fee_tax'] as $rate_id => $tax ) {
							if ( ! array_key_exists( $rate_id, $fee_tax_data ) ) {
								$fee_tax_data[ $rate_id ] = 0;
							}

							$fee_tax_data[ $rate_id ] += $tax;
						}

						$fee_tax_total += array_sum( $class['fee_tax'] );
					}

					$fee->tax_data = $fee_taxes;
					$fee->tax      = wc_round_tax_total( $fee_tax_total );
					$fee->amount   = ( $fee->amount - $fee->tax );
					$fee->total    = $fee->amount;

					$new_fees[ $key ] = $fee;
				}
			}

			$cart->fees_api()->set_fees( $new_fees );
			$cart->set_fee_tax( array_sum( $fee_tax_data ) );
			$cart->set_fee_taxes( $fee_tax_data );

			$fee_total = array_sum( wp_list_pluck( $new_fees, 'total' ) );

			$cart->set_fee_total( wc_format_decimal( $fee_total, wc_get_price_decimals() ) );
		}
	}

	public function do_fee_tax_calculation_legacy( $cart ) {

		if ( get_option( 'woocommerce_gzd_fee_tax' ) != 'yes' )
			return;

		if ( method_exists( $cart, 'set_fee_taxes' ) )
			return;

		$calculate_taxes = wc_tax_enabled() && ! WC()->customer->is_vat_exempt();

		// Do not calculate tax shares if tax calculation is disabled
		if ( ! $calculate_taxes )
			return;

		if ( ! empty( $cart->fees ) ) {
			$tax_shares = wc_gzd_get_cart_tax_share( 'fee' );
			foreach ( $cart->fees as $key => $fee ) {

				if ( ! $fee->taxable && get_option( 'woocommerce_gzd_fee_tax_force' ) != 'yes' )
					continue;

				// Calculate gross price if necessary
				if ( $fee->taxable ) {
					$fee_tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_tax       = WC_Tax::calc_tax( $fee->amount, $fee_tax_rates, false );

					$fee->amount += array_sum( $fee_tax );
				}

				// Set fee to nontaxable to avoid WooCommerce default tax calculation
				$fee->taxable = false;

				// Calculate tax class share
				if ( ! empty( $tax_shares ) ) {
					$fee_taxes = array();

					foreach ( $tax_shares as $rate => $class ) {
						$tax_rates                            = WC_Tax::get_rates( $rate );
						$tax_shares[ $rate ]['fee_tax_share'] = $fee->amount * $class['share'];
						$tax_shares[ $rate ]['fee_tax']       = WC_Tax::calc_tax( ( $fee->amount * $class['share'] ), $tax_rates, true );

						$fee_taxes += $tax_shares[ $rate ]['fee_tax'];
					}

					foreach ( $tax_shares as $rate => $class ) {
						$cart->fees[ $key ]->tax_data = $cart->fees[ $key ]->tax_data + $class['fee_tax'];
					}

					// Add fee taxes to cart taxes
					foreach ( array_keys( $cart->taxes + $fee_taxes ) as $sub ) {
						$cart->taxes[ $sub ] = ( isset( $fee_taxes[ $sub ] ) ? $fee_taxes[ $sub ] : 0 ) + ( isset( $cart->taxes[ $sub ] ) ? $cart->taxes[ $sub ] : 0 );
					}

					// Update fee
					$cart->fees[ $key ]->tax    = wc_round_tax_total( array_sum( $cart->fees[ $key ]->tax_data ) );
					$cart->fees[ $key ]->amount = wc_format_decimal( $cart->fees[ $key ]->amount - $cart->fees[ $key ]->tax, wc_get_price_decimals() );
				}
			}
		}
	}

	/**
	 * Temporarily removes all shipping rates (except chosen one) from packages to only show chosen package within checkout.
	 */
	public function remove_shipping_rates() {
		if ( 'no' === get_option( 'woocommerce_gzd_display_checkout_shipping_rate_select' ) )
			return;

		$packages = WC()->shipping->get_packages();

		foreach ( $packages as $i => $package ) {

			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			if ( ! empty( $package['rates'] ) ) {
				foreach ( $package['rates'] as $key => $rate ) {
					if ( $key != $chosen_method )
						unset( WC()->shipping->packages[ $i ]['rates'][ $key ] );
				}
			}
		}
	}

	/**
	 * Adds product description to order meta
	 *
	 * @param int $order_id
	 * @param int $item_id
	 * @param object $product
	 * @param int $qty
	 * @param array $args
	 */
	public function set_order_meta( $order_id, $item_id, $product, $qty, $args ) {
		wc_add_order_item_meta( $item_id, '_units', wc_gzd_get_gzd_product( $product )->get_product_units_html() );
		wc_add_order_item_meta( $item_id, '_delivery_time', wc_gzd_get_gzd_product( $product )->get_delivery_time_html() );
		wc_add_order_item_meta( $item_id, '_item_desc', wc_gzd_get_gzd_product( $product )->get_mini_desc() );
		wc_add_order_item_meta( $item_id, '_unit_price', wc_gzd_get_gzd_product( $product )->get_unit_html( false ) );
	}

	public function set_order_item_meta_crud( $item, $cart_item_key, $values, $order ) {
		if ( is_a( $item, 'WC_Order_Item' ) && $item->get_product() ) {

			$product = $item->get_product();
			$gzd_product = wc_gzd_get_gzd_product( $product );

			do_action( 'woocommerce_gzd_add_order_item_meta', $item, $order, $gzd_product );

			$item = wc_gzd_set_crud_meta_data( $item, '_units', $gzd_product->get_product_units_html() );
			$item = wc_gzd_set_crud_meta_data( $item, '_delivery_time', $gzd_product->get_delivery_time_html() );
			$item = wc_gzd_set_crud_meta_data( $item, '_item_desc', $gzd_product->get_mini_desc() );
			$item = wc_gzd_set_crud_meta_data( $item, '_unit_price', apply_filters( 'woocommerce_gzd_order_item_unit_price', $gzd_product->get_unit_html( false ), $gzd_product, $item, $order ) );
		}
	}

	/**
	 * Hide product description from order meta default output
	 *
	 * @param array $metas
	 */
	public function set_order_meta_hidden( $metas ) {
		array_push( $metas, '_item_desc' );
		array_push( $metas, '_units' );
		array_push( $metas, '_delivery_time' );
		array_push( $metas, '_unit_price' );

		return $metas;
	}

	public function set_formatted_billing_address( $fields, $order ) {

		if ( 'yes' !== get_option( 'woocommerce_gzd_checkout_address_field' ) ) {
			return $fields;
        }

		if ( wc_gzd_get_crud_data( $order, 'billing_title' ) ) {
			$fields['title'] = wc_gzd_get_customer_title( wc_gzd_get_crud_data( $order, 'billing_title' ) );
        }

		return $fields;
	}

	public function set_formatted_shipping_address( $fields, $order ) {

		if ( 'yes' !== get_option( 'woocommerce_gzd_checkout_address_field' ) ) {
			return $fields;
        }

		if ( wc_gzd_get_crud_data( $order, 'shipping_title' ) ) {
			$fields['title'] = wc_gzd_get_customer_title( wc_gzd_get_crud_data( $order, 'shipping_title' ) );
        }

		return $fields;
	}

	public function set_formatted_address( $placeholder, $args ) {
		if ( isset( $args['title'] ) ) {
			$placeholder['{title}']       = $args['title'];
			$placeholder['{title_upper}'] = strtoupper( $args['title'] );
			$placeholder['{name}']        = $placeholder['{title}'] . ' ' . $placeholder['{name}'];
			$placeholder['{name_upper}']  = $placeholder['{title_upper}'] . ' ' . $placeholder['{name_upper}'];
		}
		return $placeholder;
	}

	public function set_custom_fields( $fields = array(), $type = 'billing' ) {

		if ( ! empty( $this->custom_fields ) ) {

			foreach ( $this->custom_fields as $key => $custom_field ) {

				$new = array();

				if ( in_array( $type, $custom_field[ 'group' ] ) ) {

					if ( ! empty( $fields ) ) {

						foreach ( $fields as $name => $field ) {

							if ( $name == $type . '_' . $custom_field[ 'before' ] && ! isset( $custom_field[ 'override' ] ) ) {
								$new[ $type . '_' . $key ] = $custom_field;
							}

							$new[ $name ] = $field;

							if ( $name == $type . '_' . $key && isset( $custom_field[ 'override' ] ) ) {
								$new[ $name ] = array_merge( $field, $custom_field );
							}
						}
					}
				}

				if ( ! empty( $new ) ) {
					$fields = $new;
				}
			}
		}

		return $fields;
	}

	public function set_custom_fields_shipping( $fields ) {
		return $this->set_custom_fields( $fields, 'shipping' );
	}

	public function set_custom_fields_admin( $fields = array(), $type = 'billing' ) {
		$new = array();

		if ( ! empty( $this->custom_fields_admin ) ) {

			foreach ( $this->custom_fields_admin as $key => $custom_field ) {

				$new = array();

				if ( isset( $custom_field['address_type'] ) && $custom_field['address_type'] !== $type )
					continue;

				if ( ! empty( $fields ) ) {

					foreach ( $fields as $name => $field ) {
						if ( $name == $custom_field['before'] && ! isset( $custom_field['override'] ) )
							$new[ $key ] = $custom_field;

						$new[ $name ] = $field;
					}
				}

				if ( ! empty( $new ) ) {
					$fields = $new;
				}
			}
		}

		return $fields;
	}

	public function set_custom_fields_admin_billing( $fields = array() ) {
		return $this->set_custom_fields_admin( $fields, 'billing' );
	}

	public function set_custom_fields_admin_shipping( $fields = array() ) {
		return $this->set_custom_fields_admin( $fields, 'shipping' );
	}

	public function save_fields( $order_id ) {
		$checkout = WC()->checkout();

		if ( ! empty( $this->custom_fields ) ) {

			foreach ( $this->custom_fields as $key => $custom_field ) {

				if ( ! empty( $custom_field['group'] ) && ! isset( $custom_field['override'] ) ) {

					foreach ( $custom_field['group'] as $group ) {

						$val = '';

						if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
							$val = $checkout->get_posted_address_data( $key, $group );
						} else {
							$val = ( isset( $checkout->posted[ $group . '_' . $key ] ) ? $checkout->posted[ $group . '_' . $key ] : '' );
						}

						if ( ! empty( $val ) ) {
							update_post_meta( $order_id, '_' . $group . '_' . $key, apply_filters( 'woocommerce_gzd_custom_' . $key . '_field_value', sanitize_text_field( $val ) ) );
						}
					}
				}
			}
		}
	}

}

WC_GZD_Checkout::instance();