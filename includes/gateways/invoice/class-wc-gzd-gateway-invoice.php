<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Pay by Invoice Gateway
 *
 * Provides an Invoice Payment Gateway.
 *
 * @class 		WC_GZD_Gateway_Invoice
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @author 		Vendidero
 */
class WC_GZD_Gateway_Invoice extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
	public function __construct() {
		$this->id                 = 'invoice';
		$this->icon               = apply_filters( 'woocommerce_gzd_invoice_icon', '' );
		$this->has_fields         = true;
		$this->method_title       = __( 'Pay by Invoice', 'woocommerce-germanized' );
		$this->method_description = __( 'Customers will be able to pay by invoice.', 'woocommerce-germanized' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled          				= $this->get_option( 'enabled' );
		$this->title        					= $this->get_option( 'title' );
		$this->description  					= $this->get_option( 'description' );
		$this->instructions 					= $this->get_option( 'instructions', $this->description );
		$this->default_order_status 			= $this->get_option( 'default_order_status', 'on-hold' );
		$this->customers_only					= $this->get_option( 'customers_only', 'no' );
		$this->customers_completed				= $this->get_option( 'customers_completed', 'no' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_invoice', array( $this, 'thankyou_page' ) );

    }

    public function admin_options() { ?>

		<h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>

		<?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>

		<?php if ( ! WC_germanized()->is_pro() ) : ?>
		
			<div class="wc-gzd-premium-overlay notice notice-warning inline">
				<h3><?php _e( 'Automatically generate PDF invoices for this gateway?', 'woocommerce-germanized' );?></h3>
				<p><?php _e( 'By upgrading to the professional version you\'ll be able to automatically generate PDF invoices to this payment gateway. Furthermore you\'ll benefit from even more professional features such as a multistep checkout page, legal text generators, B2B VAT settings and premium support!', 'woocommerce-germanized' );?></p>
				<p>
					<a class="button button-primary" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php _e( 'Upgrade now', 'woocommerce-germanized' ); ?></a>
					<a class="button button-secondary" style="margin-left: 1em" href="https://vendidero.de/woocommerce-germanized#accounting" target="_blank"><?php _e( 'Learn more about PDF invoicing', 'woocommerce-germanized' ); ?></a>
				</p>
			</div>

		<?php endif; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><?php
	}

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pay by Invoice', 'woocommerce-germanized' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Pay by Invoice', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'You\'ll receive an invoice after your order. Please transfer the order amount to our bank account within 14 days.', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'default_order_status' => array(
				'title'       => __( 'Order Status', 'woocommerce-germanized' ),
				'type'        => 'select',
				'description' => __( 'Choose which order status should be applied after a customer has chosen to pay by invoice.', 'woocommerce-germanized' ),
				'default'     => 'wc-on-hold',
				'options'	  => wc_get_order_statuses(),
				'desc_tip'    => true,
			),
			'customers_only' => array(
				'title'       => __( 'Registered customers', 'woocommerce-germanized' ),
				'label'		  => __( 'Do only offer pay by invoice to registered/logged in customers.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'This will enable Pay by Invoice to logged in customers only', 'woocommerce-germanized' ),
				'desc_tip'	  => true,
				'default'     => 'no',
			),
			'customers_completed' => array(
				'title'       => __( 'Customer limitation', 'woocommerce-germanized' ),
				'label'		  => __( 'Do only offer pay by invoice to customers who have at least completed one order.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'default'     => 'no',
			),
		);
    }

    /**
     * Output for the order received page.
     */
	public function thankyou_page() {
		if ( $this->instructions )
        	echo wpautop( wptexturize( $this->instructions ) );
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( $this->instructions && ! $sent_to_admin && 'invoice' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	public function is_available() {

		if ( 'yes' != $this->enabled )
			return false;

		if ( is_checkout() ) {

			if ( $this->get_option( 'customers_only' ) == 'yes' && ! is_user_logged_in() )
				return false;

			if ( $this->get_option( 'customers_completed' ) == 'yes' && ( ! is_user_logged_in() || ! WC()->customer->is_paying_customer( get_current_user_id() ) ) )
				return false;

		}

		return true;
	}

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( $this->default_order_status );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}
}
