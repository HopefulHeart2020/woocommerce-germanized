<?php

class WC_GZD_Shipping_Rate extends WC_Shipping_Rate {

	public $tax_shares = array();

	public function __construct( WC_Shipping_Rate $rate ) {
		$num = 5;

		try {
			$method = new ReflectionMethod('WC_Shipping_Rate', '__construct' );
			$num = $method->getNumberOfParameters();
		} catch( Exception $e ) {}

		if ( $num === 6 ) {
			parent::__construct( $rate->id, $rate->label, $rate->cost, $rate->taxes, $rate->method_id, $rate->instance_id );
		} else {
			parent::__construct( $rate->id, $rate->label, $rate->cost, $rate->taxes, $rate->method_id );
		}

		if ( get_option( 'woocommerce_gzd_shipping_tax' ) === 'yes' && ( ! empty( $rate->taxes ) || get_option( 'woocommerce_gzd_shipping_tax_force' ) === 'yes' ) ) {
			if ( $this->get_shipping_tax() > 0 ) {
				$this->set_shared_taxes();
			}
		}

		$this->set_costs();
	}

	public function set_shared_taxes() {
		$cart             = WC()->cart;
		$this->tax_shares = wc_gzd_get_cart_tax_share();

		// Calculate tax class share
		if ( ! empty( $this->tax_shares ) ) {
		
			foreach ( $this->tax_shares as $rate => $class ) {
				$tax_rates                                       = WC_Tax::get_rates( $rate );
				$this->tax_shares[ $rate ]['shipping_tax_share'] = $this->cost * $class[ 'share' ];
				$this->tax_shares[ $rate ]['shipping_tax']       = WC_Tax::calc_tax( ( $this->cost * $class[ 'share' ] ), $tax_rates, ( WC()->cart->tax_display_cart === 'incl' ) );
			}
		
			$taxes = array();
		
			foreach ( $this->tax_shares as $rate => $class ) {
				$taxes = array_map( 'wc_round_tax_total', $taxes + $class['shipping_tax'] );
			}

			if ( is_callable( array( $this, 'set_taxes' ) ) ) {
				$this->set_taxes( $taxes );
			} else {
				$this->taxes = $taxes;
			}
		}
	}

	public function set_costs() {
		if ( WC()->cart->tax_display_cart === 'incl' || WC()->customer->is_vat_exempt() ) {
			$cost = $this->cost - array_sum( $this->taxes );

			if ( WC()->customer->is_vat_exempt() ) {
				$shipping_rates = WC_Tax::get_shipping_tax_rates();
				$shipping_taxes = WC_Tax::calc_inclusive_tax( $this->cost, $shipping_rates );
				$cost           = ( $cost - array_sum( $shipping_taxes ) );
			}

			if ( is_callable( array( $this, 'set_cost' ) ) ) {
				$this->set_cost( wc_format_decimal( $cost, wc_get_price_decimals() ) );
			} else {
				$this->cost = wc_format_decimal( $cost, wc_get_price_decimals() );
			}
		}
	}

	public function get_shared_taxes() {
		return $this->taxes;
	}

}

?>