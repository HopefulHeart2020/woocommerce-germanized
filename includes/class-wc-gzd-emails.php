<?php
/**
 * Attaches legal relevant Pages to WooCommerce Emails if has been set by WooCommerce Germanized Options
 *
 * @class 		WC_GZD_Emails
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Emails {

	/**
	 * contains options and page ids
	 * @var array
	 */
	private $footer_attachments;

	/**
	 * Adds legal page ids to different options and adds a hook to the email footer
	 */
	public function __construct() {

		// Order attachments
		$attachment_order = wc_gzd_get_email_attachment_order();
		$this->footer_attachments = array();

		foreach ( $attachment_order as $key => $order )
			$this->footer_attachments[ 'woocommerce_gzd_mail_attach_' . $key ] = woocommerce_get_page_id ( $key );

		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );
	}

	public function email_hooks( $mailer ) {

		// Add new customer activation
		if ( get_option( 'woocommerce_gzd_customer_activation' ) == 'yes' ) {
			
			remove_action( 'woocommerce_created_customer_notification', array( $mailer, 'customer_new_account' ), 10 );
			add_action( 'woocommerce_created_customer_notification', array( $this, 'customer_new_account_activation' ), 9, 3 );
		
		}

		// Hook before WooCommerce Footer is applied
		remove_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ) );
		add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), 0 );
		add_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ), 1 );

		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_plain' ), 0 );

		add_filter( 'woocommerce_email_styles', array( $this, 'styles' ) );

		$mails = $mailer->get_emails();

		if ( ! empty( $mails ) ) {

			foreach ( $mails as $mail )
				add_action( 'woocommerce_germanized_email_footer_' . $mail->id, array( $this, 'hook_mail_footer' ), 10, 1 );
		}

		add_filter( 'woocommerce_order_item_product', array( $this, 'set_order_email_filters' ), 0, 1 );

	}

	public function email_footer_plain( $text ) {

		$type = ( ! empty( $GLOBALS[ 'wc_gzd_template_name' ] ) ) ? $this->get_email_instance_by_tpl( $GLOBALS[ 'wc_gzd_template_name' ] ) : '';
		
		if ( ! empty( $type ) && $type->get_email_type() == 'plain' )
			$this->add_template_footers();

	}
 
	public function set_order_email_filters( $product ) {
		if ( is_wc_endpoint_url()  )
			return $product;
		// Add order item name actions
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ), 2 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ), 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ), 2 );
		return $product;
	}

	/**
	 * Add email styles
	 *  
	 * @param  string $css 
	 * @return string      
	 */
	public function styles( $css ) {
		return $css .= '
			.unit-price-cart {
				display: block;
				font-size: 0.9em;
			}
		';
	}

	/**
	 * Customer new account activation email.
	 *
	 * @param int $customer_id
	 * @param array $new_customer_data
	 */
	public function customer_new_account_activation( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		global $wp_hasher;

		if ( ! $customer_id )
			return;

		$user_pass = ! empty( $new_customer_data['user_pass'] ) ? $new_customer_data['user_pass'] : '';
		
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$user_activation = $wp_hasher->HashPassword( wp_generate_password( 20 ) );
		$user_activation_url = apply_filters( 'woocommerce_gzd_customer_activation_url', add_query_arg( 'activate', $user_activation, get_permalink( wc_get_page_id( 'myaccount' ) ) ) ); 
		add_user_meta( $customer_id, '_woocommerce_activation', $user_activation );

		$email = WC()->mailer()->emails['WC_GZD_Email_Customer_New_Account_Activation'];
		$email->trigger( $customer_id, $user_activation, $user_activation_url, $user_pass, $password_generated );
	}

	/**
	 * Hook into Email Footer and attach legal page content if necessary
	 *  
	 * @param  object $mail
	 */
	public function hook_mail_footer( $mail ) {
		if ( ! empty( $this->footer_attachments ) ) {
			foreach ( $this->footer_attachments as $option_key => $option ) {
				if ( $option == -1 || ! get_option( $option_key ) )
					continue;
				if ( in_array( $mail->id, get_option( $option_key ) ) ) {
					$this->attach_page_content( $option, $mail->get_email_type() );
				}
			}
		}
	}

	/**
	 * Add global footer Hooks to Email templates
	 */
	public function add_template_footers() {
		$type = ( ! empty( $GLOBALS[ 'wc_gzd_template_name' ] ) ) ? $this->get_email_instance_by_tpl( $GLOBALS[ 'wc_gzd_template_name' ] ) : '';
		if ( ! empty( $type ) )
			do_action( 'woocommerce_germanized_email_footer_' . $type->id, $type );
	}

	/**
	 * Returns Email Object by examining the template file
	 *  
	 * @param  string $tpl 
	 * @return mixed      
	 */
	private function get_email_instance_by_tpl( $tpls = array() ) {
		$found_mails = array();
		foreach ( $tpls as $tpl ) {
			$tpl = apply_filters( 'woocommerce_germanized_email_template_name',  str_replace( array( 'admin-', '-' ), array( '', '_' ), basename( $tpl, '.php' ) ), $tpl );
			$mails = WC()->mailer()->get_emails();
			if ( !empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == $tpl )
						array_push( $found_mails, $mail );
				}
			}
		}
		if ( ! empty( $found_mails ) )
			return $found_mails[ sizeof( $found_mails ) - 1 ];
		return null;
	}

	/**
	 * Attach page content by ID. Removes revocation_form shortcut to not show the form within the Email footer.
	 *  
	 * @param  integer $page_id 
	 */
	public function attach_page_content( $page_id, $email_type = 'html' ) {
		
		remove_shortcode( 'revocation_form' );
		add_shortcode( 'revocation_form', array( $this, 'revocation_form_replacement' ) );
		
		$template = 'emails/email-footer-attachment.php';
		if ( $email_type == 'plain' )
			$template = 'emails/plain/email-footer-attachment.php';
		
		wc_get_template( $template, array(
			'post_attach'  => get_post( $page_id ),
		) );
		
		add_shortcode( 'revocation_form', 'WC_GZD_Shortcodes::revocation_form' );
	}

	/**
	 * Replaces revocation_form shortcut with a link to the revocation form
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public function revocation_form_replacement( $atts ) {
		return '<a href="' . esc_url( get_permalink( wc_get_page_id( 'revocation' ) ) ) . '">' . _x( 'Forward your Revocation online', 'revocation-form', 'woocommerce-germanized' ) . '</a>';
	}

}
