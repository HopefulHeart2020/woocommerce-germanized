<?php

class WC_GZD_Customer_Helper {

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
		
		// Customer Account checkbox
		add_action( 'template_redirect', array( $this, 'init_gettext_replacement' ) );
		// Send customer account notification
		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );
		
		if ( $this->is_double_opt_in_enabled() ) {

			// Check for customer activation
			add_action( 'template_redirect', array( $this, 'customer_account_activation_check' ) );
			// Cronjob to delete unactivated users
			add_action( 'woocommerce_gzd_customer_cleanup', array( $this, 'account_cleanup' ) );

			if ( $this->is_double_opt_in_login_enabled() ) {
				// Disable login for unactivated users
				add_filter( 'wp_authenticate_user', array( $this, 'login_restriction' ) , 10, 2 );
				// Disable auto login after registration
				add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'disable_registration_auto_login' ), 10, 2 );			
				// Redirect customers that are not logged in to customer account page
				add_action( 'template_redirect', array( $this, 'disable_checkout' ), 10 );
				// Show notices on customer account page
				add_action( 'template_redirect', array( $this, 'show_disabled_checkout_notice' ), 20 );
				// Redirect customers to checkout after login
				add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 10, 2 );
				// Disable customer signup if customer has forced guest checkout
				add_action( 'woocommerce_checkout_init', array( $this, 'disable_signup' ), 10, 1 );
				// Remove the checkout signup cookie if customer logs out
				add_action( 'wp_logout', array( $this, 'delete_checkout_signup_cookie' ) );
			}

		}

	}

	public function is_double_opt_in_enabled() {
		return get_option( 'woocommerce_gzd_customer_activation' ) === 'yes';
	}

	public function is_double_opt_in_login_enabled() {
		return get_option( 'woocommerce_gzd_customer_activation_login_disabled' ) === 'yes';
	}

	public function delete_checkout_signup_cookie() {
		unset( WC()->session->disable_checkout_signup );
		unset( WC()->session->login_redirect );
	}

	public function disable_signup( $checkout ) {

		if ( WC()->session->get( 'disable_checkout_signup' ) )
			$checkout->enable_signup = false;

	}

	public function login_redirect( $redirect, $user ) {

		if ( WC()->session->get( 'login_redirect' ) && 'checkout' === WC()->session->get( 'login_redirect' ) ) {
			return wc_gzd_get_page_permalink( 'checkout' );
		}

		return $redirect;

	}

	public function disable_checkout() {

		$user_id = get_current_user_id();

		if ( get_option( 'woocommerce_enable_guest_checkout' ) === 'yes' && isset( $_GET[ 'force-guest' ] ) ) {

			// Disable registration
			WC()->session->set( 'disable_checkout_signup', true );

		} else if ( ! WC()->session->get( 'disable_checkout_signup' ) ) {
			
			if ( is_checkout() && ( ! is_user_logged_in() || ! wc_gzd_is_customer_activated() ) ) {
				
				WC()->session->set( 'login_redirect', 'checkout' );
				wp_safe_redirect( wc_gzd_get_page_permalink( 'myaccount' ) );
				exit;

			} else if ( is_checkout() ) {

				unset( WC()->session->login_redirect );

			}

		}

	}

	public function show_disabled_checkout_notice() {

		if ( ! is_user_logged_in() && isset( $_GET[ 'account' ] ) && 'activate' === $_GET[ 'account' ] ) {
			wc_add_notice( __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' ), 'notice' );
		}

		if ( is_account_page() && WC()->session->get( 'login_redirect' ) ) {

			if ( ! is_user_logged_in() ) {

				if ( get_option( 'woocommerce_enable_guest_checkout' ) === 'yes' ) {
					wc_add_notice( sprintf( __( 'Continue without creating an account? <a href="%s">Click here</a>', 'woocommerce-germanized' ), add_query_arg( array( 'force-guest' => 'yes' ), wc_gzd_get_page_permalink( 'checkout' ) ) ), 'notice' );
				} else {
					wc_add_notice( __( 'Please create an account or login before continuing to checkout', 'woocommerce-germanized' ), 'notice' );
				}

			} else {

				// Redirect to checkout
				wp_safe_redirect( wc_gzd_get_page_permalink( 'checkout' ) );
				exit;

			}

		}

	}

	public function registration_redirect( $redirect ) {
		return add_query_arg( array( 'account' => 'activate' ), wc_gzd_get_page_permalink( 'myaccount' ) );
	}

	public function disable_registration_auto_login( $result, $user_id ) {

		// Has not been activated yet
		if ( ! wc_gzd_is_customer_activated( $user_id ) ) {
			add_filter( 'woocommerce_registration_redirect', array( $this, 'registration_redirect' ) );
			return false;
		}

		return true;

	}

	public function login_restriction( $user, $password ) {

		// Has not been activated yet
		if ( ! wc_gzd_is_customer_activated( $user->ID ) )
			return new WP_Error( 'woocommerce_gzd_login', __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' ) );

		return $user;

	}

		/**
	 * Check for activation codes on my account page
	 */
	public function customer_account_activation_check() {
		
		if ( is_account_page() ) {

			if ( isset( $_GET[ 'activate' ] ) ) {
			
				$activation_code = sanitize_text_field( $_GET[ 'activate' ] );
			
				if ( ! empty( $activation_code ) ) {

					if ( $this->customer_account_activate( $activation_code, true ) ) {
						
						wc_add_notice( __( 'Thank you. You have successfully activated your account.', 'woocommerce-germanized' ), 'notice' );
						return;
					}

				}

				wc_add_notice( __( 'Sorry, but this activation code cannot be found.', 'woocommerce-germanized' ), 'error' );
			
			}
		
		}

	}

	/**
	 * Check for customer that didn't activate their accounts within a couple of time and delete them
	 */
	public function account_cleanup() {
		
		if ( ! get_option( 'woocommerce_gzd_customer_cleanup_interval' ) || get_option( 'woocommerce_gzd_customer_cleanup_interval' ) == 0 )
			return;

		$user_query = new WP_User_Query(
			array( 'role' => 'Customer', 'meta_query' =>
				array(
					array(
						'key'     => '_woocommerce_activation',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( ! empty( $user_query->results ) ) {

			foreach ( $user_query->results as $user ) {

				// Check date interval
				$registered = $user->data->user_registered;
				$date_diff = WC_germanized()->get_date_diff( $registered, date( 'Y-m-d' ) );
				if ( $date_diff[ 'd' ] >= (int) get_option( 'woocommerce_gzd_customer_cleanup_interval' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/user.php' );
					wp_delete_user( $user->ID );
				}
			}

		}
	}

	/**
	 * Activate customer account based on activation code
	 *  
	 * @param  string $activation_code hashed activation code
	 * @return boolean                  
	 */
	public function customer_account_activate( $activation_code, $login = false ) {
		
		$user_query = new WP_User_Query(
			array( 'role' => 'Customer', 'number' => 1, 'meta_query' =>
				array(
					array(
						'key'     => '_woocommerce_activation',
						'value'   => $activation_code,
						'compare' => '=',
					),
				),
			)
		);

		if ( ! empty( $user_query->results ) ) {
			
			foreach ( $user_query->results as $user ) {
				
				do_action( 'woocommerce_gzd_customer_opted_in', $user );
				delete_user_meta( $user->ID, '_woocommerce_activation' );
				WC()->mailer()->customer_new_account( $user->ID );

				if ( $login && ! is_user_logged_in() )
					wc_set_customer_auth_cookie( $user->ID );

				return true;
			}

		}

		return false;
	}

	public function init_gettext_replacement() {
		if ( is_checkout() && get_option( 'woocommerce_gzd_customer_account_checkout_checkbox' ) == 'yes' )
			add_filter( 'gettext', array( $this, 'set_customer_account_checkbox_text' ), 10, 3 );
	}

	public function set_customer_account_checkbox_text( $translated, $original, $domain ) {
		$search = "Create an account?";
		if ( $domain === 'woocommerce' && $original === $search ) {
			remove_filter( 'gettext', array( $this, 'set_customer_account_checkbox_text' ), 10, 3 );
			return wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_customer_account_text' ) );
		}
		return $translated;
	}

	public function email_hooks( $mailer ) {

		// Add new customer activation
		if ( get_option( 'woocommerce_gzd_customer_activation' ) == 'yes' ) {
			
			remove_action( 'woocommerce_created_customer_notification', array( $mailer, 'customer_new_account' ), 10 );
			add_action( 'woocommerce_created_customer_notification', array( $this, 'customer_new_account_activation' ), 9, 3 );
		
		}

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
		$user_activation_url = apply_filters( 'woocommerce_gzd_customer_activation_url', add_query_arg( array( 'activate' => $user_activation ), wc_gzd_get_page_permalink( 'myaccount' ) ) ); 
		add_user_meta( $customer_id, '_woocommerce_activation', $user_activation );

		if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) )
			$email->trigger( $customer_id, $user_activation, $user_activation_url, $user_pass, $password_generated );

	}

}

WC_GZD_Customer_Helper::instance();