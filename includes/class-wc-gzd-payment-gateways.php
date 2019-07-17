<?php
/**
 * WooCommerce Payment Gateways class
 *
 * Loads hooks for payment gateways
 *
 * @class 		WC_GZD_Payment_Gateways
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Payment_Gateways {

	protected static $_instance = null;

	private $gateway_data = array();

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		// Make sure fields are inited before being saved
		add_action( 'woocommerce_settings_save_checkout', array( $this, 'save_fields' ), 5 );

		// Init gateway fields
		add_action( 'woocommerce_settings_checkout', array( $this, 'init_fields' ), 0 );
		add_action( 'woocommerce_calculate_totals', array( $this, 'checkout' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'init_fee' ), 0 );

		// Gateway admin export
		add_action( 'current_screen', array( $this, 'gateway_admin_init' ), 20 );
		// AJAX
		add_action( 'init', array( $this, 'gateway_ajax_init' ), 30 );
		// Init upon Pay action
		add_action( 'woocommerce_before_pay_action', array( $this, 'gateway_pay_init' ), 5 );
	}

	public function gateway_admin_init() {
		$allowed = array( 'edit-shop_order', 'export' );
		$screen  = get_current_screen();

		if ( $screen && in_array( $screen->id, $allowed ) ) {
			$direct_debit = new WC_GZD_Gateway_Direct_Debit();
		}
	}

	public function gateway_pay_init() {
		$direct_debit = new WC_GZD_Gateway_Direct_Debit();
	}

	public function gateway_ajax_init() {
		if ( is_ajax() ) {
			$direct_debit = new WC_GZD_Gateway_Direct_Debit();
		}
	}

	public function save_fields() {
		$this->init_fields();
	}

	/**
	 * Set default order button text instead of the button text defined by each payment gateway.
	 * Can be overriden by setting force_order_button_text within payment gateway class
	 * Manipulate payment gateway description if has a fee and init gateway title filter
	 */
	public function checkout() {

		if ( is_admin() )
			return;

		$this->manipulate_gateways();
	}

	public function gateway_supports_fees( $id ) {
        /**
         * Filter to adjust gateways supporting fees.
         *
         * By default only the Cash on delivery gateway supports the Germanized payment gateway fee feature.
         *
         * @since 2.0.0
         *
         * @param array[string] $gateway Array of gateway ids.
         */
		return in_array( $id, apply_filters( 'woocommerce_gzd_fee_supporting_gateways', array( 'cod' ) ) ) ? true : false;
	}

	public function manipulate_gateways() {

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		
		foreach( $gateways as $gateway ) {

			$this->maybe_set_gateway_data( $gateway );

			if ( ! isset( $gateway->force_order_button_text ) || $gateway->force_order_button_text ) {

                /**
                 * Filter to adjust the forced order submit button text per gateway.
                 * By default Woo allows gateways to adjust the submit button text.
                 * This behaviour does not comply with the button solution - that is why Germanized adds the
                 * option-based static text by default.
                 *
                 * @since 1.0.0
                 *
                 * @param string $button_text The static button text from within the options.
                 * @param string $gateway_id The gateway id.
                 */
				$gateway->order_button_text = apply_filters( 'woocommerce_gzd_order_button_payment_gateway_text', __( get_option( 'woocommerce_gzd_order_submit_btn_text' ), 'woocommerce-germanized' ), $gateway->id );
            }
			
			if ( $this->gateway_supports_fees( $gateway->id ) && $gateway->get_option( 'fee' ) ) {

				$gateway_description = $this->gateway_data[ $gateway->id ][ 'description' ];

				$desc = sprintf( __( '%s payment charge', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'fee' ) ) ) . '.';
				
				if ( $gateway->get_option( 'forwarding_fee' ) ) {
				    $desc .= ' ' . sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ) . '.';
                }

                /**
                 * Filters the gateway description in case gateway fees have been added.
                 *
                 * @since 1.0.0
                 *
                 * @param string             $html The description.
                 * @param WC_Payment_Gateway $gateway The gateway instance.
                 */
				$gateway_description .= apply_filters( 'woocommerce_gzd_payment_gateway_description', ' ' . $desc, $gateway );

				$gateway->description = $gateway_description;
			}
		}
	}

	private function maybe_set_gateway_data( $gateway ) {
		if ( ! isset( $this->gateway_data[ $gateway->id ] ) ) {
			$this->gateway_data[ $gateway->id ] = array(
				'title' => $gateway->title,
				'description' => $gateway->description,
			);
		}
	}

	/**
	 * Manipualte payment gateway title
	 *  
	 * @param string $title 
	 * @param string $id    gateway id
	 */
	public function set_title( $title, $id ) {
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		foreach ( $gateways as $gateway ) {

			if ( $gateway->id != $id )
				continue;

			if ( !  $this->gateway_supports_fees( $gateway->id ) ) {
				return $title;
			}

			$this->maybe_set_gateway_data( $gateway );

			$title = $this->gateway_data[ $gateway->id ][ 'title' ];

			if ( $gateway->get_option( 'fee' ) && ( is_payment_methods() || ( is_checkout() || ( defined( 'DOING_AJAX' ) && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woocommerce_update_order_review' ) ) ) )
				$title = $title . ' <span class="small">(' . sprintf( __( '%s payment charge', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'fee' ) ) ) . ')</span>';

			return $title;
		}
	}

	/**
	 * Dynamically set filter to show additional fields
	 */
	public function init_fields() {
		$gateways = WC()->payment_gateways->payment_gateways;

		if ( ! empty( $gateways ) ) {
				foreach ( $gateways as $key => $gateway ) {

					if ( !  $this->gateway_supports_fees( $gateway->id ) ) {
						continue;
					}

					add_filter( 'woocommerce_settings_api_form_fields_' . $gateway->id, array( $this, "set_fields" ) );
			}
		}
	}

	/**
	 * Set additional payment gateway admin fields
	 *  
	 * @param array $fields 
	 */
	public function set_fields( $fields ) {

		$gateway = isset( $_GET[ 'section' ] ) ? wc_clean( $_GET[ 'section' ] ) : '';

		$fields[ 'fee' ] = array(
			'title'       => __( 'Fee', 'woocommerce-germanized' ),
			'type'        => 'decimal',
			'description' => __( 'This fee is being added if customer selects payment method within checkout.', 'woocommerce-germanized' ),
			'default'     => 0,
			'desc_tip'    => true,
		);
		$fields[ 'fee_is_taxable' ] = array(
			'title'       => __( 'Fee is taxable?', 'woocommerce-germanized' ),
			'type'        => 'checkbox',
			'label' 	  => __( 'Check if fee is taxable.', 'woocommerce-germanized' ),
			'default'     => 'no',
		);

		if ( 'wc_gateway_cod' === $gateway || 'cod' === $gateway ) {

			$fields[ 'forwarding_fee' ] = array(
				'title'       => __( 'Forwarding Fee', 'woocommerce-germanized' ),
				'type'        => 'decimal',
				'desc_tip' 	  => true,
				'description' => __( 'Forwarding fee will be charged by the transport agent in addition to the cash of delivery fee e.g. DHL - tax free.', 'woocommerce-germanized' ),
				'default'     => 0,
			);

		}

		return $fields;
	}

	/**
	 * Update fee for cart if feeable gateway has been selected as payment method
	 */
	public function init_fee() {
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( ! ( $key = WC()->session->get('chosen_payment_method') ) || ! isset( $gateways[ $key ] ) )
			return;

		$gateway = $gateways[ $key ];

		if ( !  $this->gateway_supports_fees( $gateway->id ) ) {
			return;
		}

		if ( $gateway->get_option( 'fee' ) )
			$this->set_fee( $gateway );
	}

	/**
	 * Sets fee for a specific gateway
	 *  
	 * @param object $gateway 
	 */
	public function set_fee( $gateway ) {

		$is_taxable = ( ( 'no' === $gateway->get_option( 'fee_is_taxable', 'no' ) || get_option( 'woocommerce_calc_taxes' ) !== 'yes' ) ? false : true );
		$fee        = $gateway->get_option( 'fee' );

		if ( $is_taxable ) {
			$tax_rates = WC_Tax::get_rates();
			$fee_taxes = WC_Tax::calc_tax( $fee, $tax_rates, true );
			$fee       = $fee - array_sum( $fee_taxes );
		}

		WC()->cart->add_fee( __( 'Payment charge', 'woocommerce-germanized' ), $fee, $is_taxable );
	}

}

return WC_GZD_Payment_Gateways::instance();
