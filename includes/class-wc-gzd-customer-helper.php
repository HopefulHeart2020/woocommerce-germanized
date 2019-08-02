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
		// Send customer account notification
		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );
		// Add Title to user profile
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'profile_field_title' ), 10, 1 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'load_customer_fields' ), 10, 3 );

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
				// WC Social Login comp
				add_filter( 'wc_social_login_set_auth_cookie', array( $this, 'social_login_activation_check' ), 10, 2 );
			}
		}
	}

	public function load_customer_fields( $data, $customer, $user_id ) {

		$fields = WC_GZD_Checkout::instance()->custom_fields_admin;

		if ( is_array( $fields ) ) {
			foreach( $fields as $key => $field ) {

				$types = array( 'shipping', 'billing' );

				if ( isset( $field[ 'address_type' ] ) ) {
					$types = array( $field[ 'address_type' ] );
				}

				foreach( $types as $type ) {
					if ( ! isset( $data[ $type ] ) )
						continue;

					$data[ $type ][ $key ] = get_user_meta( $user_id,  $type . '_' . $key, true );
				}
			}
		}
		return $data;
	}

	public function social_login_activation_check( $message, $user ) {
		$user_id = $user->ID;

		if ( $this->enable_double_opt_in_for_user( $user ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
			return __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' );
		}

		return $message;
	}

	public function profile_field_title( $fields ) {

		if ( get_option( 'woocommerce_gzd_checkout_address_field' ) !== 'yes' )
			return $fields;

		$fields['billing']['fields']['billing_title'] = array(
			'label'       => __( 'Title', 'woocommerce-germanized' ),
			'type'		  => 'select',
			'options'	  => wc_gzd_get_customer_title_options(),
			'description' => '',
			'class'       => '',
		);

		$fields['shipping']['fields']['shipping_title'] = array(
			'label'       => __( 'Title', 'woocommerce-germanized' ),
			'type'		  => 'select',
			'options'	  => wc_gzd_get_customer_title_options(),
			'description' => '',
			'class'       => '',
		);

		return $fields;
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

		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );

		if ( WC()->session && WC()->session->get( 'disable_checkout_signup' ) ) {
			$checkout->enable_signup = false;
		}

	}

	public function login_redirect( $redirect, $user ) {

		if ( WC()->session->get( 'login_redirect' ) && 'checkout' === WC()->session->get( 'login_redirect' ) ) {

            /**
             * Filter URL to redirect customers to after a successfull opt-in which
             * was related to a checkout request (e.g. customer was redirected to the register page before checkout).
             *
             * @since 1.0.0
             *
             * @param string $url The redirect URL.
             */
			return apply_filters( 'woocommerce_gzd_customer_activation_checkout_redirect', wc_gzd_get_page_permalink( 'checkout' ) );
		}

		return $redirect;

	}

	public function disable_checkout() {

		$user_id = get_current_user_id();

		if ( is_cart() ) {
			// On accessing cart - reset disable checkout signup so that the customer is rechecked before redirecting him to the checkout.
			unset( WC()->session->disable_checkout_signup );
		}

		if ( ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) && isset( $_GET[ 'force-guest' ] ) ) || 'yes' !== get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) ) {

		    // Disable registration
			WC()->session->set( 'disable_checkout_signup', true );

		} elseif ( ! WC()->session->get( 'disable_checkout_signup' ) ) {

			if ( is_checkout() && ( ! is_user_logged_in() || ( $this->enable_double_opt_in_for_user() && ! wc_gzd_is_customer_activated() ) ) ) {
				
				WC()->session->set( 'login_redirect', 'checkout' );
				wp_safe_redirect( $this->registration_redirect() );
				exit;

			} elseif ( is_checkout() ) {
				unset( WC()->session->login_redirect );
			}
		}
	}

	public function show_disabled_checkout_notice() {

		if ( ! is_user_logged_in() && isset( $_GET[ 'account' ] ) && 'activate' === $_GET[ 'account' ] ) {
			wc_add_notice( __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' ), 'notice' );
			return;
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
				unset( WC()->session->login_redirect );
				/** This filter is documented in includes/class-wc-gzd-customer-helper.php */
				wp_safe_redirect( apply_filters( 'woocommerce_gzd_customer_activation_checkout_redirect', wc_gzd_get_page_permalink( 'checkout' ) ) );
				exit;

			}
		}
	}

	protected function registration_redirect( $query_args = array() ) {

        /**
         * Filter URL which serves as redirection if a customer has not yet activated it's account and
         * wants to access checkout.
         *
         * @since 1.0.0
         *
         * @param string $url               The redirection URL.
         * @param array[string] $query_args Arguments passed to the URL.
         */
		return apply_filters( 'woocommerce_gzd_customer_registration_redirect', add_query_arg( $query_args, wc_gzd_get_page_permalink( 'myaccount' ) ), $query_args );
	}

	public function disable_registration_auto_login( $result, $user_id ) {

		if ( is_a( $user_id, 'WP_User' ) ) {
			$user_id = $user_id->ID;
		}

		// Has not been activated yet
		if ( $this->enable_double_opt_in_for_user( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
            wp_redirect( wp_validate_redirect( $this->registration_redirect( array( 'account' => 'activate' ) ) ) ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            exit;
		}

		return true;
	}

	public function get_double_opt_in_user_roles() {

        /**
         * Filters supported DOI user roles. By default only the WooCommerce customer role is supported.
         *
         * ```php
         * function ex_add_doi_roles( $roles ) {
         *      $roles[] = 'my_custom_role';
         *
         *      return $roles;
         * }
         * add_filter( 'woocommerce_gzd_customer_double_opt_in_supported_user_roles', 'ex_add_doi_roles', 10, 1 );
         * ```
         *
         * @since 1.0.0
         *
         * @param array $roles Array of roles to be supported.
         */
		return apply_filters( 'woocommerce_gzd_customer_double_opt_in_supported_user_roles', array( 'customer' ) );
	}

	public function enable_double_opt_in_for_user( $user = false ) {

		if ( ! $user ) {
			$user = get_current_user_id();
		}

		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'id', absint( $user ) );
		}

		if ( ! $user ) {
			return false;
		}

		$supported_roles        = $this->get_double_opt_in_user_roles();
		$supports_double_opt_in = false;
		$user_roles             = ( isset( $user->roles ) ? (array) $user->roles : array() );

		foreach( $user_roles as $role ) {
			if ( in_array( $role, $supported_roles ) ) {
				$supports_double_opt_in = true;
				break;
			}
		}

        /**
         * Filter whether the DOI is supported for a certain user.
         *
         * @since 1.0.0
         *
         * @param bool    $supports_double_opt_in Whether the user is supported or not.
         * @param WP_User $user The user instance.
         */
		return apply_filters( 'woocommerce_gzd_customer_supports_double_opt_in', $supports_double_opt_in, $user );
	}

	public function login_restriction( $user, $password ) {

		// Has not been activated yet
		if ( $this->enable_double_opt_in_for_user( $user ) && ! wc_gzd_is_customer_activated( $user->ID ))
			return new WP_Error( 'woocommerce_gzd_login', __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' ) );

		return $user;
	}

		/**
	 * Check for activation codes on my account page
	 */
	public function customer_account_activation_check() {
		if ( is_account_page() ) {
			if ( isset( $_GET['activate'] ) ) {
				$activation_code = wc_clean( wp_unslash( $_GET['activate'] ) );

				if ( ! empty( $activation_code ) ) {
					$result = $this->customer_account_activate( $activation_code, true );

					if ( $result === true ) {
						$url = add_query_arg( array( 'activated' => 'yes' ) );
						$url = remove_query_arg( 'activate', $url );
						$url = remove_query_arg( 'suffix', $url );

                        /**
                         * Filters the URL after a successful DOI.
                         *
                         * @since 1.0.0
                         *
                         * @param string $url The URL to redirect to.
                         */
						wp_safe_redirect( apply_filters( 'woocommerce_gzd_double_opt_in_successful_redirect', $url ) );
					} elseif ( is_wp_error( $result ) && 'expired_key' === $result->get_error_code() ) {
						wc_add_notice( __( 'This activation code has expired. We have sent you a new activation code via e-mail.', 'woocommerce-germanized' ), 'error' );
					} else {
						wc_add_notice( __( 'Sorry, but this activation code cannot be found.', 'woocommerce-germanized' ), 'error' );
					}
				}
			} elseif( isset( $_GET['activated'] ) ) {
				wc_add_notice( __( 'Thank you. You have successfully activated your account.', 'woocommerce-germanized' ), 'notice' );
			}
		}
	}

	/**
	 * Check for customer that didn't activate their accounts within a couple of time and delete them
	 */
	public function account_cleanup() {

		if ( ! get_option( 'woocommerce_gzd_customer_cleanup_interval' ) || get_option( 'woocommerce_gzd_customer_cleanup_interval' ) == 0 )
			return;

		$roles             = array_map( 'ucfirst', $this->get_double_opt_in_user_roles() );
		$cleanup_days      = (int) get_option( 'woocommerce_gzd_customer_cleanup_interval' );
		$registered_before = date('Y-m-d H:i:s', strtotime( "-{$cleanup_days} days" ) );

		$user_query = new WP_User_Query(
			array(
				'role'       => $roles,
				'date_query' => array(
					array(
						'before'    => $registered_before,
						'inclusive' => true,
					)
				),
				'meta_query' => array(
					array(
						'key'     => '_woocommerce_activation',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {

                /**
                 * Filters whether a certain user which has not yet been activated
                 * should be deleted.
                 *
                 * @since 1.0.0
                 *
                 * @param bool    $delete Whether to delete the unactivated customer or not.
                 * @param WP_User $user The user instance.
                 */
				if ( apply_filters( 'woocommerce_gzd_delete_unactivated_customer', true, $user ) ) {
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
	 * @return boolean|WP_Error
	 */
	public function customer_account_activate( $activation_code, $login = false ) {
		$roles = array_map( 'ucfirst', $this->get_double_opt_in_user_roles() );

        /**
         * Filter to adjust arguments for the customer activation user query.
         *
         * @since 1.0.0
         *
         * @param array  $args Arguments being passed to `WP_User_Query`.
         * @param string $activation_code The activation code.
         * @param bool   $login Whether the customer should be authenticated after activation or not.
         */
		$user_query = new WP_User_Query( apply_filters( 'woocommerce_gzd_customer_account_activation_query', array( 
			'role__in'   => $roles,
			'number'     => 1,
			'meta_query' => array(
				array(
					'key'     => '_woocommerce_activation',
					'value'   => $activation_code,
					'compare' => '=',
				),
			),
		), $activation_code, $login ) );

		/**
		 * Filters the expiration time of customer activation keys.
		 *
		 * @since 1.0.0
		 *
		 * @param int $expiration The expiration time in seconds.
		 */
		$expiration_duration = apply_filters( 'woocommerce_germanized_account_activation_expiration', DAY_IN_SECONDS );
		
		if ( ! empty( $user_query->results ) ) {

			foreach ( $user_query->results as $user ) {

				$expiration_time = false;

				if ( false !== strpos( $activation_code, ':' ) ) {
					list( $activation_request_time, $activation_key ) = explode( ':', $activation_code, 2 );
					$expiration_time                                  = $activation_request_time + $expiration_duration;
				}

				if ( $expiration_time && time() < $expiration_time ) {

                    /**
                     * Customer has opted-in.
                     *
                     * Fires whenever a customer has opted-in (DOI).
                     * Triggers before the confirmation e-mail has been sent and the user meta has been deleted.
                     *
                     * @since 2.0.3
                     *
                     * @param WP_User $user The user instance.
                     */
					do_action( 'woocommerce_gzd_customer_opted_in', $user );
					delete_user_meta( $user->ID, '_woocommerce_activation' );

					WC()->mailer()->customer_new_account( $user->ID );

                    /**
                     * Filter to optionally disable automatically authenticate activated customers.
                     *
                     * @since 1.0.0
                     *
                     * @param bool    $login Whether to authenticate the customer or not.
                     * @param WP_User $user The user instance.
                     */
					if ( apply_filters( 'woocommerce_gzd_user_activation_auto_login', $login, $user ) && ! is_user_logged_in() ) {
						wc_set_customer_auth_cookie( $user->ID );
                    }

                    /**
                     * Customer opt-in finished.
                     *
                     * Fires after a customer has been marked as opted-in and received the e-mail confirmation.
                     * Customer may already be authenticated at this point.
                     *
                     * @since 2.0.3
                     *
                     * @param WP_User $user The user instance.
                     */
					do_action( 'woocommerce_gzd_customer_opt_in_finished', $user );

					return true;
				} else {

                    /**
                     * Customer activation code expired.
                     *
                     * Hook fires whenever a customer tries to acitvate it's account but the activation key
                     * has already expired.
                     *
                     * @since 2.0.3
                     *
                     * @param WP_User $user The user instance.
                     */
					do_action( 'woocommerce_gzd_customer_activation_expired', $user );

					delete_user_meta( $user->ID, '_woocommerce_activation' );
					$activation_code = $this->get_customer_activation_meta( $user->ID, true );

					$user_activation_url = $this->get_customer_activation_url( $activation_code );

					if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
						$email->trigger( $user->ID, $activation_code, $user_activation_url );
                    }

					return new WP_Error( 'expired_key', __( 'Expired activation key', 'woocommerce-germanized' ) );
				}
			}
		}

		return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'woocommerce-germanized' ) );
	}

	public function email_hooks( $mailer ) {
		// Add new customer activation
		if ( 'yes' === get_option( 'woocommerce_gzd_customer_activation' ) ) {
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

		if ( ! $customer_id ) {
			return;
        }

		if ( ! $this->enable_double_opt_in_for_user( $customer_id ) ) {
			return;
        }

		$user_pass           = ! empty( $new_customer_data['user_pass'] ) ? $new_customer_data['user_pass'] : '';
		$user_activation     = $this->get_customer_activation_meta( $customer_id );
		$user_activation_url = $this->get_customer_activation_url( $user_activation );

		if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
			$email->trigger( $customer_id, $user_activation, $user_activation_url, $user_pass, $password_generated );
        }
	}

	public function get_customer_activation_url( $key ) {
        /**
         * Filter the customer activation URL.
         * Added a custom suffix to prevent email clients from stripping points as last chars.
         *
         * @since 1.0.0
         *
         * @param string $url The activation URL.
         */
		return apply_filters( 'woocommerce_gzd_customer_activation_url', add_query_arg( array( 'activate' => $key, 'suffix' => 'yes' ), wc_gzd_get_page_permalink( 'myaccount' ) ) );
	}

	public function get_customer_activation_meta( $customer_id, $force_new = false ) {
		global $wp_hasher;

		if ( ! $customer_id )
			return;

		if ( ! $this->enable_double_opt_in_for_user( $customer_id ) )
			return;

		// If meta does already exist - return activation code
		if ( ! $force_new && ( $activation = get_user_meta( $customer_id, '_woocommerce_activation', true ) ) ) {
			return $activation;
		}

		// Generate something random for a password reset key.
		$key = wp_generate_password( 20, false );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}

		$user_activation = time() . ':' . $wp_hasher->HashPassword( $key );

		update_user_meta( $customer_id, '_woocommerce_activation', $user_activation );

		return $user_activation;
	}

}

WC_GZD_Customer_Helper::instance();