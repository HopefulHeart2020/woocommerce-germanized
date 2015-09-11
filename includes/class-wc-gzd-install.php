<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Install' ) ) :

/**
 * Installation related functions and hooks
 *
 * @class 		WC_GZD_Install
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Install {

	/** @var array DB updates that need to be run */
	private static $db_updates = array(
		'1.0.4' => 'updates/woocommerce-gzd-update-1.0.4.php',
		'1.4.2' => 'updates/woocommerce-gzd-update-1.4.2.php'
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_action( 'in_plugin_update_message-woocommerce-germanized/woocommerce-germanized.php', array( __CLASS__, 'in_plugin_update_message' ) );
	}

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzd_version' ) != WC_germanized()->version ) ) {
			self::install();
			do_action( 'woocommerce_gzd_updated' );
		}
	}

	/**
	 * Install actions such as installing pages when a button is clicked.
	 */
	public static function install_actions() {
		// Install - Add pages button
		if ( ! empty( $_GET['install_woocommerce_gzd'] ) ) {

			if ( ! empty( $_GET['install_woocommerce_gzd_pages'] ) )
				self::create_pages();

			if ( ! empty( $_GET['install_woocommerce_gzd_settings'] ) )
				self::set_default_settings();

			if ( ! empty( $_GET['install_woocommerce_gzd_tax_rates'] ) )
				self::create_tax_rates();

			// We no longer need to install pages
			delete_option( '_wc_gzd_needs_pages' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-installed=true' ) );
			exit;

		// Skip button
		} elseif ( ! empty( $_GET['skip_install_woocommerce_gzd'] ) ) {

			// We no longer need to install pages
			delete_option( '_wc_gzd_needs_pages' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about' ) );
			exit;

		// Update button
		} elseif ( ! empty( $_GET['do_update_woocommerce_gzd'] ) ) {

			self::update();

			// Update complete
			delete_option( '_wc_gzd_needs_pages' );
			delete_option( '_wc_gzd_needs_update' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-updated=true' ) );
			exit;

		}
		
	}

	/**
	 * Install WC_Germanized
	 */
	public static function install() {

		global $wpdb;

		if ( ! defined( 'WC_GZD_INSTALLING' ) ) {
			define( 'WC_GZD_INSTALLING', true );
		}
		
		// Load Translation for default options
		$locale = apply_filters( 'plugin_locale', get_locale() );
		$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized.mo';
		
		if ( file_exists( WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo' ) )
			$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo';
		
		load_textdomain( 'woocommerce-germanized', $mofile );
		
		if ( ! WC_GZD_Dependencies::instance()->is_woocommerce_activated() ) {
			deactivate_plugins( WC_GERMANIZED_PLUGIN_FILE );
			wp_die( sprintf( __( 'Please install <a href="%s" target="_blank">WooCommerce</a> before installing WooCommerce Germanized. Thank you!', 'woocommerce-germanized' ), 'http://wordpress.org/plugins/woocommerce/' ) );
		}

		self::create_options();
		
		// Register post types
		include_once( 'class-wc-gzd-post-types.php' );
		WC_GZD_Post_types::register_taxonomies();

		self::create_cron_jobs();
		self::create_units();

		// Virtual Tax Classes
		$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
		
		if ( ! in_array( 'Virtual Rate', $tax_classes ) || ! in_array( 'Virtual Reduced Rate', $tax_classes ) ) {
			update_option( '_wc_gzd_needs_pages', 1 );
			if ( ! in_array( 'Virtual Rate', $tax_classes ) )
				array_push( $tax_classes, 'Virtual Rate' );
			if ( ! in_array( 'Virtual Reduced Rate', $tax_classes ) )
				array_push( $tax_classes, 'Virtual Reduced Rate' );
			update_option( 'woocommerce_tax_classes', implode( "\n", $tax_classes ) );
		}

		// Queue upgrades
		$current_version    = get_option( 'woocommerce_gzd_version', null );
		$current_db_version = get_option( 'woocommerce_gzd_db_version', null );
		
		if ( ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			update_option( '_wc_gzd_needs_update', 1 );
		} else {
			self::update_db_version();
		}

		self::update_wc_gzd_version();

		// Update activation date
		update_option( 'woocommerce_gzd_activation_date', date( 'Y-m-d' ) );

		// Add theme compatibility check
		delete_option( '_wc_gzd_hide_theme_notice' );
		delete_option( '_wc_gzd_hide_review_notice' );
		delete_option( '_wc_gzd_hide_pro_notice' );

		// Check if pages are needed
		if ( wc_get_page_id( 'revocation' ) < 1 )
			update_option( '_wc_gzd_needs_pages', 1 );

		// Flush rules after install
		flush_rewrite_rules();

		// Redirect to welcome screen
		set_transient( '_wc_gzd_activation_redirect', 1, 60 * 60 );
	}

	/**
	 * Update WC version to current
	 */
	private static function update_wc_gzd_version() {
		delete_option( 'woocommerce_gzd_version' );
		add_option( 'woocommerce_gzd_version', WC_germanized()->version );
	}

	/**
	 * Update DB version to current
	 */
	private static function update_db_version( $version = null ) {
		delete_option( 'woocommerce_gzd_db_version' );
		add_option( 'woocommerce_gzd_db_version', is_null( $version ) ? WC_germanized()->version : $version );
	}

	/**
	 * Handle updates
	 */
	private static function update() {
		$current_db_version = get_option( 'woocommerce_gzd_db_version' );

		foreach ( self::$db_updates as $version => $updater ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				include( $updater );
				self::update_db_version( $version );
			}
		}

		self::update_db_version();
	}

	/**
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 */
	public static function in_plugin_update_message( $args ) {
		$transient_name = 'wc_gzd_upgrade_notice_' . $args['Version'];

		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce-germanized/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = self::parse_update_notice( $response['body'] );
				set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
			}
		}

		echo wp_kses_post( $upgrade_notice );
	}

	/**
	 * Parse update notice from readme file
	 * @param  string $content
	 * @return string
	 */
	private static function parse_update_notice( $content ) {
		// Output Upgrade Notice
		$matches        = null;
		$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WC_GERMANIZED_VERSION ) . '\s*=|$)~Uis';
		$upgrade_notice = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$version = trim( $matches[1] );
			$notices = (array) preg_split('~[\r\n]+~', trim( $matches[2] ) );

			if ( version_compare( WC_GERMANIZED_VERSION, $version, '<' ) ) {

				$upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) );
				}

				$upgrade_notice .= '</div> ';
			}
		}

		return wp_kses_post( $upgrade_notice );
	}

	/**
	 * Create cron jobs (clear them first)
	 */
	private static function create_cron_jobs() {
		// Cron jobs
		wp_clear_scheduled_hook( 'woocommerce_gzd_customer_cleanup' );
		wp_schedule_event( time(), 'daily', 'woocommerce_gzd_customer_cleanup' );

		wp_clear_scheduled_hook( 'woocommerce_gzd_trusted_shops_reviews' );
		wp_schedule_event( time(), 'twicedaily', 'woocommerce_gzd_trusted_shops_reviews' );
		
		wp_clear_scheduled_hook( 'woocommerce_gzd_ekomi' );
		wp_schedule_event( time(), 'daily', 'woocommerce_gzd_ekomi' );
	}

	private static function create_units() {
		$units = include_once( WC_Germanized()->plugin_path() . '/i18n/units.php' );
		if ( ! empty( $units ) ) {
			foreach ( $units as $slug => $unit )
				wp_insert_term( $unit, 'product_unit', array( 'slug' => $slug ) );
		}
	}

	public static function create_tax_rates() {

		global $wpdb;

		// Delete digital rates
		$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array( 'tax_rate_class' => 'virtual-rate' ), array( '%s' ) );

		$rates = array(
			'BE' => 21,
			'BG' => 20,
			'CZ' => 21,
			'DK' => 25,
			'DE' => 19,
			'EE' => 20,
			'EL' => 23,
			'ES' => 21,
			'FR' => 20,
			'HR' => 25,
			'IE' => 23,
			'IT' => 22,
			'CY' => 19,
			'LV' => 21,
			'LT' => 21,
			'LU' => 15,
			'HU' => 27,
			'MT' => 18,
			'NL' => 21,
			'AT' => 20,
			'PL' => 23,
			'PT' => 23,
			'RO' => 24,
			'SI' => 22,
			'SK' => 20,
			'FI' => 24,
			'SE' => 25,
			'UK' => 20,
		);

		if ( ! empty( $rates ) ) {
			$count = 0;
			foreach ( $rates as $iso => $rate ) {
				$_tax_rate = array(
					'tax_rate_country'  => $iso,
					'tax_rate_state'    => '',
					'tax_rate'          => (string) number_format( (double) wc_clean( $rate ), 4, '.', '' ),
					'tax_rate_name'     => 'MwSt. ' . $iso . ' virtual',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 0,
					'tax_rate_order'    => $count++,
					'tax_rate_class'    => 'virtual-rate'
				);
				// Check if standard rate exists
				if ( WC()->countries->get_base_country() == $iso ) {
					$base_rate = WC_Tax::get_shop_base_rate();
					$base_rate = reset( $base_rate );
					if ( ! empty( $base_rate ) )
						$_tax_rate[ 'tax_rate_name' ] = $base_rate[ 'label' ];
				}
				$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );
				$tax_rate_id = $wpdb->insert_id;
				do_action( 'woocommerce_tax_rate_added', $tax_rate_id, $_tax_rate );
			}
		}
		// Clear tax transients
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_wc_tax_rates%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_timeout_wc_tax_rates%' ) );
	}

	/**
	 * Updates WooCommerce Options if user chooses to automatically adapt german options
	 */
	public static function set_default_settings() {
		global $wpdb;

		$options = array(
			'woocommerce_default_country' 			 => 'DE',
			'woocommerce_currency' 					 => 'EUR',
			'woocommerce_currency_pos'				 => 'right_space',
			'woocommerce_price_thousand_sep' 	     => '.',
			'woocommerce_price_decimal_sep'     	 => ',',
			'woocommerce_price_num_decimals'		 => 2,
			'woocommerce_weight_unit'				 => 'kg',
			'woocommerce_dimension_unit'			 => 'cm',
			'woocommerce_calc_taxes'				 => 'yes',
			'woocommerce_prices_include_tax'		 => 'yes',
			'woocommerce_tax_display_cart'			 => 'incl',
			'woocommerce_tax_display_shop'			 => 'incl',
			'woocommerce_tax_total_display'			 => 'itemized',
			'woocommerce_allowed_countries'	    	 => 'specific',
			'woocommerce_specific_allowed_countries' => array( 'DE' ),
		);
		if ( !empty($options ) ) {
			foreach ( $options as $key => $option ) {
				update_option( $key, $option );
			}
		}
		// Tax Rates
		$_tax_rate = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => number_format( (double) wc_clean( 19.0 ), 4, '.', '' ),
			'tax_rate_name'     => 'MwSt.',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => '',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => 1,
			'tax_rate_class'    => ''
		);
		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate LIKE "19%"' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		$_tax_rate[ 'tax_rate' ] = number_format( (double) wc_clean( 7.0 ), 4, '.', '' );
		$_tax_rate[ 'tax_rate_class' ] = 'reduced-rate';
		$_tax_rate[ 'tax_rate_name' ] = 'MwSt. 7%';

		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate LIKE "7%"' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		// Clear tax transients
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_wc_tax_rates%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_timeout_wc_tax_rates%' ) );
 	}

	/**
	 * Create pages that the plugin relies on, storing page id's in variables.
	 *
	 * @access public
	 * @return void
	 */
	public static function create_pages() {
		$pages = apply_filters( 'woocommerce_gzd_create_pages', array(
			'data_security' => array(
				'name'    => _x( 'data-security', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Data Security Statement', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'imprint' => array(
				'name'    => _x( 'imprint', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Imprint', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'terms' => array(
				'name'    => _x( 'terms', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Terms & Conditions', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'revocation' => array(
				'name'    => _x( 'revocation', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Power of Revocation', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'shipping_costs' => array(
				'name'    => _x( 'shipping-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Shipping Methods', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'payment_methods' => array(
				'name'    => _x( 'payment-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Payment Methods', 'Page title', 'woocommerce-germanized' ),
				'content' => '[' . apply_filters( 'woocommerce_gzd_payment_methods_shortcode_tag', 'payment_methods_info' ) . ']'
			),
		) );

		foreach ( $pages as $key => $page ) {
			wc_create_page( esc_sql( $page['name'] ), 'woocommerce_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );
		}

	}

	/**
	 * Default options
	 *
	 * Sets up the default options used on the settings page
	 *
	 * @access public
	 */
	public static function create_options() {
		// Include settings so that we can run through defaults
		include_once( WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php' );
		include_once( 'admin/settings/class-wc-gzd-settings-germanized.php' );

		$settings = new WC_GZD_Settings_Germanized();
		$options = array_merge( $settings->get_settings(), $settings->get_display_settings() );

		foreach ( $options as $value ) {
			if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
				$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
			}
		}
	}

}

endif;

return new WC_GZD_Install();
