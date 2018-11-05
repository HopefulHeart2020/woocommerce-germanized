<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Email_Customer_New_Account_Activation' ) ) :

/**
 * Customer New Account
 *
 * An email sent to the customer when they create an account.
 *
 * @class 		WC_Email_Customer_New_Account
 * @version		2.3.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class WC_GZD_Email_Customer_New_Account_Activation extends WC_Email {

	public $user_login;
	public $user_email;
	public $user_activation;
	public $user_activation_url;
	public $user_pass;
	public $password_generated;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id 				= 'customer_new_account_activation';
		$this->title 			= __( 'New account activation', 'woocommerce-germanized' );
		$this->description		= __( 'Customer "new account activation" emails are sent to the customer when a customer signs up via checkout or account pages. This mail is being used as double opt in for new customer accounts.', 'woocommerce-germanized' );

		$this->template_html 	= 'emails/customer-new-account-activation.php';
		$this->template_plain 	= 'emails/plain/customer-new-account-activation.php';

		// Call parent constuctor
		parent::__construct();

		$this->customer_email = true;
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Activate your account on {site_title}', 'woocommerce-germanized');
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Account activation {site_title}', 'woocommerce-germanized');
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $user_id, $user_activation, $user_activation_url, $user_pass = '', $password_generated = false ) {
		if ( is_callable( array( $this, 'setup_locale' ) ) ) {
			$this->setup_locale();
		}

		if ( $user_id ) {
			$this->object 			   = new WP_User( $user_id );

			$this->user_pass           = $user_pass;
			$this->user_activation     = $user_activation;
			$this->user_activation_url = $user_activation_url;
			$this->user_login          = stripslashes( $this->object->user_login );
			$this->user_email          = stripslashes( $this->object->user_email );
			$this->recipient           = $this->user_email;
			$this->password_generated  = $password_generated;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		if ( is_callable( array( $this, 'restore_locale' ) ) ) {
			$this->restore_locale();
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'email_heading'       => $this->get_heading(),
			'user_login'          => $this->user_login,
			'user_activation'     => $this->user_activation,
			'user_activation_url' => $this->user_activation_url,
			'user_pass'           => $this->user_pass,
			'password_generated'  => $this->password_generated,
			'blogname'            => $this->get_blogname(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'email_heading'       => $this->get_heading(),
			'user_login'          => $this->user_login,
			'user_activation'     => $this->user_activation,
			'user_activation_url' => $this->user_activation_url,
			'user_pass'           => $this->user_pass,
			'password_generated'  => $this->password_generated,
			'blogname'            => $this->get_blogname(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		) );
		return ob_get_clean();
	}
}

endif;

return new WC_GZD_Email_Customer_New_Account_Activation();
