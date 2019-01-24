<?php

class WC_GZD_Legal_Checkbox_Manager {

	protected $checkboxes = array();

	protected static $_instance = null;

	protected $options = null;

	protected $core_checkboxes = array(
		'terms',
		'download',
		'service',
		'parcel_delivery',
		'privacy',
		'sepa',
		'review_reminder',
	);

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->checkboxes = array();

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 1, 1 );
		add_filter( 'woocommerce_process_registration_errors', array( $this, 'validate_register' ), 10, 1 );
		add_action( 'woocommerce_before_pay_action', array( $this, 'validate_pay_for_order' ), 10, 1 );
		add_filter( 'pre_comment_approved', array( $this, 'validate_reviews' ), 10, 2 );

		// Cannot use after_setup_theme here because language packs are not yet loaded
		add_action( 'init', array( $this, 'do_register_action' ), 50 );

		add_action( 'woocommerce_gzd_run_legal_checkboxes_checkout', array( $this, 'show_conditionally_checkout' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_fragments_checkout' ), 10, 1 );
	}

	public function refresh_fragments_checkout( $fragments ) {
		$this->maybe_do_hooks( 'checkout' );

		foreach( $this->get_checkboxes( array( 'locations' => 'checkout', 'do_refresh_fragments' => true ) ) as $id => $checkbox ) {
			ob_start();
			$checkbox->render();
			$html = ob_get_clean();

			$fragments[ '.wc-gzd-checkbox-placeholder-' . esc_attr( $checkbox->get_html_id() ) ] = $html;
		}

		return $fragments;
	}

	public function get_core_checkbox_ids() {
		return apply_filters( 'woocommerce_gzd_legal_checkbox_core_ids', $this->core_checkboxes );
	}

	protected function get_legal_label_args() {
		return array(
			'{term_link}'           => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'terms' ) ) . '" target="_blank">',
			'{/term_link}'          => '</a>',
			'{revocation_link}'     =>'<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
			'{/revocation_link}'    => '</a>',
			'{data_security_link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'data_security' ) ) . '" target="_blank">',
			'{/data_security_link}' => '</a>',
		);
	}

	public function register_core_checkboxes() {

		wc_gzd_register_legal_checkbox( 'terms', array(
			'html_id'              => 'legal',
			'html_name'            => 'legal',
			'html_wrapper_classes' => array( 'legal' ),
			'hide_input'           => false,
			'label_args'           => $this->get_legal_label_args(),
			'label'                => __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} your {revocation_link}Right of Recission{/revocation_link} and our {data_security_link}Privacy Policy{/data_security_link}.', 'woocommerce-germanized' ),
			'error_message'        => __( 'To finish the order you have to accept to our {term_link}Terms and Conditions{/term_link}, {revocation_link}Right of Recission{/revocation_link} and our {data_security_link}Privacy Policy{/data_security_link}.', 'woocommerce-germanized' ),
			'is_mandatory'         => true,
			'priority'             => 0,
			'template_name'        => 'checkout/terms.php',
			'template_args'        => array( 'gzd_checkbox' => true ),
			'is_core'              => true,
			'admin_name'           => __( 'Legal', 'woocommerce-germanized' ),
			'admin_desc'           => __( 'General legal checkbox which shall include terms, revocation and privacy notice.', 'woocommerce-germanized' ),
			'locations'            => array( 'checkout' ),
		) );

		wc_gzd_register_legal_checkbox( 'download', array(
			'html_id'              => 'data-download',
			'html_name'            => 'download-revocate',
			'html_wrapper_classes' => array( 'legal' ),
			'label'                =>  __( 'For digital products: I strongly agree that the execution of the agreement starts before the revocation period has expired. I am aware that my right of withdrawal ceases with the beginning of the agreement.', 'woocommerce-germanized' ),
			'label_args'           => $this->get_legal_label_args(),
			'error_message'        =>  __( 'To retrieve direct access to digital content you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
			'is_mandatory'         => true,
			'priority'             => 1,
			'is_enabled'           => true,
			'is_core'              => true,
			'is_shown'             => false,
			'admin_name'           => __( 'Digital', 'woocommerce-germanized' ),
			'admin_desc'           => __( 'Asks the customer to skip revocation period for digital products.', 'woocommerce-germanized' ),
			'locations'            => array( 'checkout' ),
			'types'                => array( 'downloadable' ),
		) );

		wc_gzd_register_legal_checkbox( 'service', array(
			'html_id'              => 'data-service',
			'html_name'            => 'service-revocate',
			'html_wrapper_classes' => array( 'legal' ),
			'label'                => __( 'For services: I demand and acknowledge the immediate performance of the service before the expiration of the withdrawal period. I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ),
			'label_args'           => $this->get_legal_label_args(),
			'error_message'        =>  __( 'To allow the immediate performance of the services you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
			'is_mandatory'         => true,
			'priority'             => 2,
			'is_enabled'           => true,
			'is_core'              => true,
			'is_shown'             => false,
			'admin_name'           => __( 'Service', 'woocommerce-germanized' ),
			'admin_desc'           => __( 'Asks the customer to skip revocation period for services.', 'woocommerce-germanized' ),
			'locations'            => array( 'checkout' )
		) );

		wc_gzd_register_legal_checkbox( 'parcel_delivery', array(
			'html_id'              => 'parcel-delivery-checkbox',
			'html_name'            => 'parcel_delivery_checkbox',
			'html_wrapper_classes' => array( 'legal' ),
			'label'                => __( 'Yes, I would like to be reminded via E-mail about parcel delivery ({shipping_method_title}). Your E-mail Address will only be transferred to our parcel service provider for that particular reason.', 'woocommerce-germanized' ),
			'label_args'           => array( '{shipping_method_title}' => '' ),
			'is_mandatory'         => false,
			'priority'             => 3,
			'is_enabled'           => false,
			'error_message'        => __( 'Please accept our parcel delivery agreement', 'woocommerce-germanized' ),
			'is_core'              => true,
			'refresh_fragments'    => true,
			'is_shown'             => false,
			'supporting_locations' => array( 'checkout' ),
			'admin_name'           => __( 'Parcel Delivery', 'woocommerce-germanized' ),
			'admin_desc'           => __( 'Asks the customer to hand over data to the parcel delivery service provider.', 'woocommerce-germanized' ),
			'locations'            => array( 'checkout' )
		) );

		// Privacy Policy
		wc_gzd_register_legal_checkbox( 'privacy', array(
			'html_id'              => 'reg_data_privacy',
			'html_name'            => 'privacy',
			'html_wrapper_classes' => array( 'legal', 'form-row-wide', 'terms-privacy-policy' ),
			'label'                =>  __( 'Yes, I’d like create a new account and have read and understood the {data_security_link}data privacy statement{/data_security_link}.', 'woocommerce-germanized' ),
			'label_args'           => $this->get_legal_label_args(),
			'is_mandatory'         => true,
			'is_enabled'           => true,
			'error_message'        => __( 'Please accept our privacy policy to create a new customer account', 'woocommerce-germanized' ),
			'is_core'              => true,
			'priority'             => 4,
			'admin_name'           => __( 'Privacy Policy', 'woocommerce-germanized' ),
			'admin_desc'           => __( 'Let customers accept your privacy policy before registering.', 'woocommerce-germanized' ),
			'locations'            => array( 'register' ),
		) );

		$direct_debit_settings = get_option( 'woocommerce_direct-debit_settings' );

		// For validation, refresh and adjustments see WC_GZD_Gateway_Direct_Debit
		if ( is_array( $direct_debit_settings ) && 'yes' === $direct_debit_settings['enabled'] ) {
			$ajax_url = wp_nonce_url( add_query_arg( array( 'action' => 'show_direct_debit' ), admin_url( 'admin-ajax.php' ) ), 'show_direct_debit' );

			wc_gzd_register_legal_checkbox( 'sepa', array(
				'html_id'              => 'direct-debit-checkbox',
				'html_name'            => 'direct_debit_legal',
				'html_wrapper_classes' => array( 'legal', 'direct-debit-checkbox' ),
				'label'                => __( 'I hereby agree to the {link}direct debit mandate{/link}.', 'woocommerce-germanized' ),
				'label_args'           => array( '{link}' => '<a href="' . $ajax_url . '" id="show-direct-debit-trigger" rel="prettyPhoto">', '{/link}' => '</a>' ),
				'is_mandatory'         => true,
				'error_message'        => __( 'Please accept the direct debit mandate.', 'woocommerce-germanized' ),
				'priority'             => 5,
				'template_name'        => 'checkout/terms-sepa.php',
				'is_enabled'           => true,
				'is_core'              => true,
				'admin_name'           => __( 'SEPA', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Asks the customer to issue the SEPA mandate.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout', 'pay_for_order' )
			) );
		}

		do_action( 'woocommerce_gzd_register_legal_core_checkboxes', $this );
	}

	public function show_conditionally_checkout() {

		if ( $checkbox = $this->get_checkbox( 'download' ) ) {
			if ( $checkbox->is_enabled() ) {

				$items = WC()->cart->get_cart();
				$is_downloadable = false;

				if ( ! empty( $items ) ) {
					foreach ( $items as $cart_item_key => $values ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
						if ( wc_gzd_is_revocation_exempt( $_product ) ) {
							$is_downloadable = true;
						}
					}
				}

				if ( $is_downloadable ) {
					wc_gzd_update_legal_checkbox( 'download', array(
						'is_shown' => true,
					) );
				}
			}
		}

		// Service checkbox
		if ( $checkbox = $this->get_checkbox( 'service' ) ) {
			if ( $checkbox->is_enabled() ) {

				$items      = WC()->cart->get_cart();
				$is_service = false;

				if ( ! empty( $items ) ) {
					foreach ( $items as $cart_item_key => $values ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );
						if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) ) {
							$is_service = true;
						}
					}
				}

				if ( $is_service ) {
					wc_gzd_update_legal_checkbox( 'service', array(
						'is_shown' => true,
					) );
				}
			}
		}

		// Service checkbox
		if ( $checkbox = $this->get_checkbox( 'parcel_delivery' ) ) {
			if ( $checkbox->is_enabled() ) {

				$rates  = wc_gzd_get_chosen_shipping_rates();
				$ids    = array();
				$titles = array();

				foreach ( $rates as $rate ) {
					array_push( $ids, $rate->id );
					if ( method_exists( $rate, 'get_label' ) ) {
						array_push( $titles, $rate->get_label() );
					} else {
						array_push( $titles, $rate->label );
					}
				}

				$is_enabled = wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( $ids );

				if ( $is_enabled ) {
					wc_gzd_update_legal_checkbox( 'parcel_delivery', array(
						'label_args'   => array( '{shipping_method_title}' => implode( ', ', $titles ) ),
						'is_shown'     => true,
					) );
				}
			}
		}
	}

	public function get_options( $force_refresh = false ) {
		if ( is_null( $this->options ) || ! is_array( $this->options ) || $force_refresh ) {
			wp_cache_delete( 'woocommerce_gzd_legal_checkboxes_settings', 'options' );
			$this->options = get_option( 'woocommerce_gzd_legal_checkboxes_settings', array() );
		}

		return $this->options;
	}

	public function update_options( $options ) {
		$result = update_option( 'woocommerce_gzd_legal_checkboxes_settings', $options, false );
		$this->options = $options;

		return $result;
	}

	public function do_register_action() {
		// Reload checkboxes
		$this->checkboxes = array();
		$this->register_core_checkboxes();

		do_action( 'woocommerce_gzd_register_legal_checkboxes', $this );

		// Make sure we are not registering core checkboxes again
		foreach( $this->get_options() as $id => $checkbox_args ) {
			if ( isset( $checkbox_args['id'] ) ) {
				unset( $checkbox_args['id'] );
			}

			if ( $checkbox = $this->get_checkbox( $id ) ) {
				$checkbox->update( $checkbox_args );
			} elseif ( ! in_array( $id, $this->get_core_checkbox_ids() ) ) {
				$this->register( $id, $checkbox_args );
			}
		}

		do_action( 'woocommerce_gzd_registered_legal_checkboxes', $this );
	}

	public function validate_pay_for_order( $order ) {
		$this->maybe_do_hooks( 'pay_for_order' );

		foreach( $this->get_checkboxes( array( 'locations' => 'pay_for_order' ) ) as $id => $checkbox ) {
			$value = isset( $_POST[ $checkbox->get_html_name() ] ) ? $_POST[ $checkbox->get_html_name() ] : '';

			if( ! $checkbox->validate( $value, 'pay_for_order' ) ) {
				wc_add_notice( $checkbox->get_error_message(), 'error' );
			}
		}
	}

	public function validate_checkout( $data ) {
		if ( isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) )
			return;

		$this->maybe_do_hooks( 'checkout' );

		foreach( $this->get_checkboxes( array( 'locations' => 'checkout' ) ) as $id => $checkbox ) {
			$value = isset( $_POST[ $checkbox->get_html_name() ] ) ? $_POST[ $checkbox->get_html_name() ] : '';

			if( ! $checkbox->validate( $value, 'checkout' ) ) {
				wc_add_notice( $checkbox->get_error_message(), 'error' );
			}
		}
	}

	public function validate_reviews( $approved, $comment_data ) {

		if ( 'product' !== get_post_type( $comment_data['comment_post_ID'] ) ) {
			return $approved;
		}

		$this->maybe_do_hooks( 'reviews' );

		foreach( $this->get_checkboxes( array( 'locations' => 'reviews' ) ) as $id => $checkbox ) {

			$value = isset( $_POST[ $checkbox->get_html_name() ] ) ? $_POST[ $checkbox->get_html_name() ] : '';

			if( ! $checkbox->validate( $value, 'reviews' ) ) {
				return new WP_Error( $checkbox->get_html_name(), $checkbox->get_error_message(), 409 );
			}
		}

		return $approved;
	}

	public function validate_register( $validation_error ) {
		$this->maybe_do_hooks( 'register' );

		foreach( $this->get_checkboxes( array( 'locations' => 'register' ) ) as $id => $checkbox ) {

			$value = isset( $_POST[ $checkbox->get_html_name() ] ) ? $_POST[ $checkbox->get_html_name() ] : '';

			if( ! $checkbox->validate( $value, 'register' ) ) {
				return new WP_Error( $checkbox->get_html_name(), $checkbox->get_error_message() );
			}
		}

		return $validation_error;
	}

	public function get_locations() {
		return apply_filters( 'woocommerce_gzd_legal_checkbox_locations', array(
			'checkout'      => __( 'Checkout', 'woocommerce-germanized' ),
			'register'      => __( 'Register form', 'woocommerce-germanized' ),
			'pay_for_order' => __( 'Pay for order', 'woocommerce-germanized' ),
			'reviews'       => __( 'Reviews', 'woocommerce-germanized' )
		) );
	}

	public function update( $id, $args ) {

		if ( $this->get_checkbox( $id ) ) {
			$this->checkboxes[ $id ]->update( $args );
			return true;
		}

		return false;
	}

	public function delete( $id ) {
		if ( $checkbox = $this->get_checkbox( $id ) ) {
			unset( $this->checkboxes[ $id ] );
			return true;
		}

		return false;
	}

	public function register( $id, $args ) {

		$args = wp_parse_args( $args, array(
			'html_name'            => '',
			'html_id'              => '',
			'is_mandatory'         => false,
			'locations'            => array(),
			'supporting_locations' => array(),
			'html_wrapper_classes' => array(),
			'html_classes'         => array(),
			'hide_input'           => false,
			'error_message'        => '',
			'admin_name'           => '',
		) );

		$bools = array(
			'is_mandatory',
			'hide_input'
		);

		// Make sure we do understand yes and no as bools
		foreach( $bools as $bool ) {
			$args[ $bool ] = wc_gzd_string_to_bool( $args[ $bool ] );
		}

		if ( empty( $args['html_name'] ) ) {
			$args['html_name'] = $id;
		}

		if ( empty( $args['html_id'] ) ) {
			$args['html_id'] = $args['html_name'];
		}

		if ( ! is_array( $args['locations'] ) ) {
			$args['locations'] = array( $args['locations'] );
		}

		foreach( $args['locations'] as $location ) {
			if ( ! in_array( $location, array_keys( $this->get_locations() ) ) ) {
				return new WP_Error( 'checkbox_location_inexistent', sprintf( __( 'Checkbox location %s does not exist.', 'woocommerce-germanized' ), $location ) );
			}
		}

		if ( empty( $args['supporting_locations'] ) ) {
			$args['supporting_locations'] = array_keys( $this->get_locations() );
		}

		$args['html_wrapper_classes'] = array_merge( $args['html_wrapper_classes'], array( 'form-row', 'checkbox-' . $args['html_id'] ) );
		$args['html_classes']         = array_merge( $args['html_classes'], array( 'woocommerce-form__input', 'woocommerce-form__input-checkbox', 'input-checkbox' ) );

		if ( $args['hide_input'] ) {
			$args['is_mandatory'] = false;
		}

		if ( $args['is_mandatory'] ) {
			$args['html_wrapper_classes'] = array_merge( $args['html_wrapper_classes'], array( 'validate-required' ) );

			if ( empty( $args['error_message'] ) ) {
				$args['error_message'] = sprintf( __( 'Please make sure to check %s checkbox.', 'woocommerce-germanized' ), esc_attr( $args['admin_name'] ) );
			}
		}

		if ( isset( $this->checkboxes[ $id ] ) ) {
			return new WP_Error( 'checkbox_exists', sprintf( __( 'Checkbox with name %s does already exist.', 'woocommerce-germanized' ), $id ) );
		}

		// Allow third parties to filter checkbox args
		$args      = apply_filters( 'woocommerce_gzd_register_legal_checkbox_args', $args, $id );
		$classname = apply_filters( 'woocommerce_gzd_legal_checkbox_classname', 'WC_GZD_Legal_Checkbox' );

		$this->checkboxes[ $id ] = new $classname( $id, $args );

		return true;
	}

	public function remove( $id ) {
		if ( isset( $this->checkboxes[ $id ] ) ) {
			unset( $this->checkboxes[ $id ] );
		}
	}

	public function get_checkbox( $id ) {
		if ( isset( $this->checkboxes[ $id ] ) ) {
			return $this->checkboxes[ $id ];
		}

		return false;
	}

	public function get_checkboxes( $args = array(), $context = '' ) {
		$checkboxes = $this->filter( $args, 'AND' );

		if ( ! empty( $context ) && 'json' === $context ) {
			foreach( $checkboxes as $id => $checkbox ) {
				$checkboxes[ $id ] = $checkbox->get_data();
			}
		}

		return $checkboxes;
	}

	protected function filter( $args = array(), $operator = 'AND' ) {
		$filtered = array();
		$count    = count( $args );

		foreach ( $this->checkboxes as $key => $obj ) {
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {

				$getter_bool = $m_key;
				$getter = 'get_' . $m_key;
				$obj_value = null;

				if ( is_callable( array( $obj, $getter_bool ) ) ) {
					$obj_value = $obj->$getter_bool();
				} elseif ( is_callable( array( $obj, $getter ) ) ) {
					$obj_value = $obj->$getter();
				} else {
					$obj_value = $obj->$m_key;
				}

				if ( ! is_null( $obj_value ) ) {
					if ( is_array( $obj_value ) && ! is_array( $m_value ) ) {
						if ( in_array( $m_value, $obj_value ) ) {
							$matched++;
						}
					} else {
						if ( $m_value == $obj_value ) {
							$matched++;
						}
					}
				}
			}

			if (
				( 'AND' == $operator && $matched == $count ) ||
				( 'OR' == $operator && $matched > 0 ) ||
				( 'NOT' == $operator && 0 == $matched )
			) {
				$filtered[ $key ] = $obj;
			}
		}

		return $filtered;
	}

	public function render( $location = 'checkout' ) {
		$this->maybe_do_hooks( $location );

		$checkboxes = $this->get_checkboxes( array( 'locations' => $location ) );

		if ( ! empty( $checkboxes ) ) {
			$checkboxes = $this->sort( $checkboxes );

			foreach( $checkboxes as $id => $checkbox ) {
				$checkbox->render();
			}
		}
	}

	protected function sort( $checkboxes = array() ) {
		uasort( $checkboxes, array( $this, '_uasort_callback' ) );
		return $checkboxes;
	}

	public function _uasort_callback( $checkbox1, $checkbox2 ) {
		if ( $checkbox1->get_priority() == $checkbox2->get_priority() ) return 0;
		return ( $checkbox1->get_priority() < $checkbox2->get_priority() ) ? -1 : 1;
	}

	private function maybe_do_hooks( $location = 'checkout' ) {
		if ( ! did_action( 'woocommerce_gzd_run_legal_checkboxes' ) ) {
			do_action( 'woocommerce_gzd_run_legal_checkboxes', $this );
		}

		if ( ! did_action( 'woocommerce_gzd_run_legal_checkboxes_' . $location ) ) {
			do_action( 'woocommerce_gzd_run_legal_checkboxes_' . $location, $this );
		}
	}
}

WC_GZD_Legal_Checkbox_Manager::instance();