<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * AJAX Handler
 *
 * @class        WC_GZD_AJAX
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_AJAX {

	/**
	 * Hook in methods
	 */
	public static function init() {

		$ajax_events = array(
			'gzd_revocation'                    => true,
			'gzd_refresh_unit_price'            => true,
			'gzd_json_search_delivery_time'     => false,
			'gzd_legal_checkboxes_save_changes' => false,
			'gzd_toggle_tab_enabled'            => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function gzd_toggle_tab_enabled() {
		check_ajax_referer( 'wc_gzd_tab_toggle_nonce', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['tab'] ) || ! isset( $_POST['enable'] ) ) {
			wp_die( - 1 );
		}

		$tab_id = wc_clean( $_POST['tab'] );
		$enable = wc_string_to_bool( $_POST['enable'] );

		$pages = WC_Admin_Settings::get_settings_pages();

		foreach ( $pages as $page ) {

			if ( is_a( $page, 'WC_GZD_Settings_Germanized' ) ) {
				if ( $tab = $page->get_tab_by_name( $tab_id ) ) {
					if ( $enable ) {
						$tab->enable();
						$data = array(
							'data'    => true,
							'message' => '',
						);

						if ( $tab->notice_on_activate() ) {
							$data['message'] = $tab->notice_on_activate();
						}

						wp_send_json( $data );
					} else {
						$tab->disable();

						wp_send_json( array(
							'data' => false,
						) );
					}
				}
			}
		}

		wp_send_json( array(
			'data' => false,
		) );
	}

	public static function gzd_legal_checkboxes_save_changes() {
		if ( ! isset( $_POST['wc_gzd_legal_checkbox_nonce'], $_POST['changes'] ) ) {
			wp_send_json_error( 'missing_fields' );
			wp_die();
		}

		if ( ! wp_verify_nonce( $_POST['wc_gzd_legal_checkbox_nonce'], 'wc_gzd_legal_checkbox_nonce' ) ) {
			wp_send_json_error( 'bad_nonce' );
			wp_die();
		}

		// Check User Caps
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			wp_die();
		}

		$changes = $_POST['changes'];
		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		$options = $manager->get_options();

		foreach ( $changes as $id => $data ) {

			$checkbox = $manager->get_checkbox( $id );

			if ( isset( $data['deleted'] ) ) {
				if ( isset( $data['newRow'] ) ) {
					// So the user added and deleted a new row.
					// That's fine, it's not in the database anyways. NEXT!
					continue;
				}
				// Delete
				if ( isset( $options[ $id ] ) ) {
					// Do not allow to delete core entries
					if ( $checkbox && $checkbox->is_core() ) {
						continue;
					}
					$manager->delete( $id );
					unset( $options[ $id ] );
				}
				continue;
			}

			/**
			 * Filters legal checkbox default option keys.
			 *
			 * @param array $args Option keys.
			 *
			 * @since 2.0.0
			 *
			 */
			$keys = apply_filters( 'woocommerce_gzd_legal_checkboxes_option_keys', array(
				'id'       => '',
				'priority' => 1,
			) );

			$checkbox_data = array_intersect_key( $data, $keys );

			if ( ! isset( $options[ $id ] ) || ! is_array( $options[ $id ] ) ) {
				$options[ $id ] = array();
			}

			$options[ $id ] = array_replace_recursive( $options[ $id ], $checkbox_data );
		}

		$manager->update_options( $options );
		$manager->do_register_action();

		$checkboxes = $manager->get_checkboxes( array(), 'json' );

		wp_send_json_success(
			array(
				'checkboxes' => $checkboxes,
			)
		);
	}

	public static function gzd_json_search_delivery_time() {
		ob_start();

		check_ajax_referer( 'search-products', 'security' );
		$term  = (string) wc_clean( stripslashes( $_GET['term'] ) );
		$terms = array();

		if ( empty( $term ) ) {
			die();
		}

		$args = array(
			'hide_empty' => false,
		);

		if ( is_numeric( $term ) ) {
			$args['include'] = array( absint( $term ) );
		} else {
			$args['name__like'] = (string) $term;
		}

		$query = get_terms( 'product_delivery_time', $args );
		if ( ! empty( $query ) ) {
			foreach ( $query as $term ) {
				$terms[ $term->term_id ] = rawurldecode( $term->name );
			}
		} else {
			$terms[ rawurldecode( $term ) ] = rawurldecode( sprintf( __( "%s [new]", "woocommerce-germanized" ), $term ) );
		}
		wp_send_json( $terms );
	}

	/**
	 * @param $price
	 * @param WC_Product $product
	 */
	protected static function get_price_excluding_tax( $price, $product ) {
		$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
		$remove_taxes   = WC_Tax::calc_tax( $price, $tax_rates, true );
		$price          = $price - array_sum( $remove_taxes ); // Unrounded since we're dealing with tax inclusive prices. Matches logic in cart-totals class. @see adjust_non_base_location_price.

		return $price;
	}

	public static function gzd_refresh_unit_price() {
		check_ajax_referer( 'wc-gzd-refresh-unit-price', 'security' );

		if ( ! isset( $_POST['product_id'], $_POST['price'] ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		$product_id = absint( $_POST['product_id'] );
		$price      = wc_clean( $_POST['price'] );
		$price_sale = isset( $_POST['price_sale'] ) ? wc_clean( $_POST['price_sale'] ) : '';

		if ( ! $product = wc_gzd_get_product( $product_id ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		/**
		 * In case net prices are used and prices are being shown including tax
		 * we will need to manually remove taxes from price before recalculating the unit price.
		 */
		if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			$price = self::get_price_excluding_tax( $price, $product->get_wc_product() );

			if ( ! empty( $price_sale ) ) {
				$price_sale = self::get_price_excluding_tax( $price_sale, $product->get_wc_product() );
			}
		}

		$args = array(
			'regular_price' => $price,
			'sale_price'    => ! empty( $price_sale ) ? $price_sale : $price,
			'price'         => ! empty( $price_sale ) ? $price_sale : $price
		);

		$product->recalculate_unit_price( $args );

		wp_send_json( array(
			'result'          => 'success',
			'unit_price_html' => $product->get_unit_price_html(),
			'product_id'      => $product_id
		) );
	}

	/**
	 * Checks revocation form and sends Email to customer and Admin
	 */
	public static function gzd_revocation() {

		check_ajax_referer( 'woocommerce-revocation', 'security' );

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-revocation' ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		$data   = array();
		$fields = WC_GZD_Revocation::get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $key => $field ) {

				if ( 'sep' !== $key ) {

					if ( isset( $field['required'] ) && true === $field['required'] ) {
						if ( $key == 'address_mail' ) {
							if ( ! is_email( $_POST[ $key ] ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not a valid email address.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
							}
						} elseif ( $key == 'address_postal' ) {
							if ( ! WC_Validation::is_postcode( $_POST[ $key ], $_POST['address_country'] ) || empty( $_POST[ $key ] ) ) {
								wc_add_notice( _x( 'Please enter a valid postcode/ZIP', 'revocation-form', 'woocommerce-germanized' ), 'error' );
							}
						} elseif ( $key === 'privacy_checkbox' ) {
							if ( isset( $field['required'] ) && empty( $_POST[ $key ] ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong>', 'error' );
							}
						} else {
							if ( isset( $field['required'] ) && empty( $_POST[ $key ] ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not valid.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
							}
						}
					}

					if ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ) {
						if ( $field['type'] == 'country' ) {
							$countries    = WC()->countries->get_countries();
							$country      = wc_clean( $_POST[ $key ] );
							$data[ $key ] = ( isset( $countries[ $country ] ) ? $countries[ $country ] : '' );
						} else {
							$data[ $key ] = wc_clean( $_POST[ $key ] );
						}
					}
				}
			}
		}

		$error = false;
		if ( wc_notice_count( 'error' ) == 0 ) {

			wc_add_notice( _x( 'Thank you. We have received your Revocation Request. You will receive a conformation email within a few minutes.', 'revocation-form', 'woocommerce-germanized' ), 'success' );

			// Send Mail
			if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_revocation' ) ) {

				// Send to customer
				$mail->trigger( $data );

				// Send to Admin
				$data['send_to_admin'] = true;
				$mail->trigger( $data );
			}

		} else {
			$error = true;
		}

		ob_start();
		wc_print_notices();
		$messages = ob_get_clean();

		$data = array(
			'messages' => isset( $messages ) ? $messages : '',
			'result'   => ( $error ? 'failure' : 'success' ),
		);

		wp_send_json( $data );
	}

}

WC_GZD_AJAX::init();
