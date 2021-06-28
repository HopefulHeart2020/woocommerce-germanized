<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use \Vendidero\Germanized\Packages;

if ( ! class_exists( 'WC_GZD_Install' ) ) :

	/**
	 * Installation related functions and hooks
	 *
	 * @class        WC_GZD_Install
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Install {

		/** @var array DB updates that need to be run */
		private static $db_updates = array(
			'1.0.4' => 'updates/woocommerce-gzd-update-1.0.4.php',
			'1.4.2' => 'updates/woocommerce-gzd-update-1.4.2.php',
			'1.4.6' => 'updates/woocommerce-gzd-update-1.4.6.php',
			'1.5.0' => 'updates/woocommerce-gzd-update-1.5.0.php',
			'1.6.0' => 'updates/woocommerce-gzd-update-1.6.0.php',
			'1.6.3' => 'updates/woocommerce-gzd-update-1.6.3.php',
			'1.8.0' => 'updates/woocommerce-gzd-update-1.8.0.php',
			'1.8.9' => 'updates/woocommerce-gzd-update-1.8.9.php',
			'1.9.2' => 'updates/woocommerce-gzd-update-1.9.2.php',
			'2.0.1' => 'updates/woocommerce-gzd-update-2.0.1.php',
			'2.2.5' => 'updates/woocommerce-gzd-update-2.2.5.php',
			'2.3.0' => 'updates/woocommerce-gzd-update-2.3.0.php',
			'3.0.0' => 'updates/woocommerce-gzd-update-3.0.0.php',
			'3.0.1' => 'updates/woocommerce-gzd-update-3.0.1.php',
			'3.0.6' => 'updates/woocommerce-gzd-update-3.0.6.php',
			'3.0.8' => 'updates/woocommerce-gzd-update-3.0.8.php',
			'3.1.6' => 'updates/woocommerce-gzd-update-3.1.6.php',
			'3.1.9' => 'updates/woocommerce-gzd-update-3.1.9.php',
			'3.3.4' => 'updates/woocommerce-gzd-update-3.3.4.php',
			'3.3.5' => 'updates/woocommerce-gzd-update-3.3.5.php',
			'3.4.0' => 'updates/woocommerce-gzd-update-3.4.0.php'
		);

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'admin_init', array( __CLASS__, 'check_version' ), 10 );
			add_action( 'in_plugin_update_message-woocommerce-germanized/woocommerce-germanized.php', array(
				__CLASS__,
				'in_plugin_update_message'
			) );

			add_action( 'admin_init', array( __CLASS__, 'redirect' ) );
		}

		public static function redirect() {
			if ( get_option( '_wc_gzd_setup_wizard_redirect' ) ) {

				// Bail if activating from network, or bulk, or within an iFrame
				if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
					return;
				}

				if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce-germanized.php' ) ) ) {
					return;
				}

				delete_option( '_wc_gzd_setup_wizard_redirect' );
				wp_safe_redirect( admin_url( 'admin.php?page=wc-gzd-setup' ) );
				exit();

			} elseif ( get_transient( '_wc_gzd_activation_redirect' ) ) {

				// Delete the redirect transient
				delete_transient( '_wc_gzd_activation_redirect' );

				// Bail if we are waiting to install or update via the interface update/install links
				if ( get_option( '_wc_gzd_needs_update' ) == 1 ) {
					return;
				}

				// Bail if activating from network, or bulk, or within an iFrame
				if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
					return;
				}

				if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce-germanized.php' ) ) ) {
					return;
				}

				wp_redirect( admin_url( 'index.php?page=wc-gzd-about' ) );
				exit;
			} elseif ( ! empty( $_GET['do_update_woocommerce_gzd'] ) ) {

				self::update();

				// Update complete
				delete_option( '_wc_gzd_needs_pages' );
				delete_option( '_wc_gzd_needs_update' );

				if ( $note = WC_GZD_Admin_Notices::instance()->get_note( 'update' ) ) {
					$note->dismiss();
				}

				delete_transient( '_wc_gzd_activation_redirect' );

				// What's new redirect
				wp_redirect( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-updated=true' ) );
				exit;
			}
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

				/**
				 * Plugin updated.
				 *
				 * Germanized was updated to a new version.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_gzd_updated' );
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
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-germanized' );
			$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized.mo';

			if ( file_exists( WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo' ) ) {
				$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo';
			}

			load_textdomain( 'woocommerce-germanized', $mofile );

			if ( ! wc_gzd_get_dependencies()->is_woocommerce_activated() ) {
				deactivate_plugins( WC_GERMANIZED_PLUGIN_FILE );
				wp_die( sprintf( __( 'Please install <a href="%s" target="_blank">WooCommerce</a> before installing WooCommerce Germanized. Thank you!', 'woocommerce-germanized' ), 'http://wordpress.org/plugins/woocommerce/' ) );
			}

			// Register post types
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-post-types.php';
			WC_GZD_Post_types::register_taxonomies();

			self::create_cron_jobs();
			self::install_packages();

			self::create_units();
			self::create_labels();
			self::create_options();

			// Delete plugin header data for dependency check
			delete_option( 'woocommerce_gzd_plugin_header_data' );

			$notices = WC_GZD_Admin_Notices::instance();

			// Refresh notes
			foreach( $notices->get_notes() as $note ) {
				$note->delete_note();
			}

			// Recheck outdated templates
			if ( $note = $notices->get_note( 'template_outdated' ) ) {
				$note->reset();
			}

			// Show the importer
			if ( $note = $notices->get_note( 'dhl_importer' ) ) {
				$note->reset();
			}

			// Show the importer
			if ( $note = $notices->get_note( 'internetmarke_importer' ) ) {
				$note->reset();
			}

			// Queue upgrades
			$current_version    = get_option( 'woocommerce_gzd_version', null );
			$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

			// Queue messages and notices
			if ( ! is_null( $current_version ) ) {
				$major_version     = substr( $current_version, 0, 3 );
				$new_major_version = substr( WC_germanized()->version, 0, 3 );

				// Only on major update
				if ( version_compare( $new_major_version, $major_version, ">" ) ) {

					if ( $note = $notices->get_note( 'review' ) ) {
						$note->reset();
					}

					if ( $note = $notices->get_note( 'pro' ) ) {
						$note->reset();
					}

					if ( $note = $notices->get_note( 'theme_supported' ) ) {
						$note->reset();
					}
				}
			}

			$needs_db_update = ! is_null( $current_db_version ) && ! empty( $current_db_version ) && version_compare( $current_db_version, max( array_keys( self::$db_updates ) ), '<' );

			/**
			 * Decides whether Germanized needs a database update.
			 *
			 * @param boolean Whether a database update is needed or not.
			 *
			 * @since 3.0.0
			 *
			 */
			if ( apply_filters( 'woocommerec_gzd_needs_db_update', $needs_db_update ) ) {

				if ( $note = $notices->get_note( 'update' ) ) {
					$note->reset();
				}

				// Update
				update_option( '_wc_gzd_needs_update', 1 );
			} else {
				self::update_db_version();
			}

			self::update_wc_gzd_version();

			// Update activation date
			update_option( 'woocommerce_gzd_activation_date', date( 'Y-m-d' ) );

			// Flush rules after install
			flush_rewrite_rules();

			// Check if pages are needed - start setup
			if ( wc_get_page_id( 'revocation' ) < 1 ) {
				update_option( '_wc_gzd_setup_wizard_redirect', 1 );
			} elseif ( ! defined( 'DOING_AJAX' ) ) {
				// Redirect to welcome screen
				set_transient( '_wc_gzd_activation_redirect', 1, 60 * 60 );
			}

			/**
			 * Plugin installed.
			 *
			 * Germanized was installed successfully.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_gzd_installed' );
		}

		protected static function install_packages() {
			foreach ( Packages::get_packages() as $package_slug => $namespace ) {
				if ( is_callable( array( $namespace, 'install_integration' ) ) ) {
					$namespace::install_integration();
				}
			}
		}

		public static function deactivate() {
			// Clear Woo sessions to remove WC_GZD_Shipping_Rate instance
			if ( class_exists( 'WC_REST_System_Status_Tools_Controller' ) ) {
				$tools_controller = new WC_REST_System_Status_Tools_Controller;
				$tools_controller->execute_tool( 'clear_sessions' );
			}

			/**
			 * Remove notices.
			 */
			$notices = WC_GZD_Admin_Notices::instance();

			foreach( $notices->get_notes() as $note ) {
				$note->delete_note();
			}
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

			/**
			 * Runs as soon as a database update has been triggered by the user.
			 *
			 * @since 3.0.0
			 */
			do_action( 'woocommerce_gzd_db_update' );

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
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		private static function parse_update_notice( $content ) {
			// Output Upgrade Notice
			$matches        = null;
			$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WC_GERMANIZED_VERSION ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			if ( preg_match( $regexp, $content, $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

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
		}

		public static function create_units() {
			$units = include( WC_Germanized()->plugin_path() . '/i18n/units.php' );

			if ( ! empty( $units ) ) {
				foreach ( $units as $slug => $unit ) {
					wp_insert_term( $unit, 'product_unit', array( 'slug' => $slug ) );
				}
			}
		}

		public static function create_labels() {
			$labels = include( WC_Germanized()->plugin_path() . '/i18n/labels.php' );

			if ( ! empty( $labels ) ) {
				foreach ( $labels as $slug => $unit ) {
					wp_insert_term( $unit, 'product_price_label', array( 'slug' => $slug ) );
				}
			}
		}

		public static function create_tax_rates() {
			if ( \Vendidero\OneStopShop\Package::oss_procedure_is_enabled() ) {
				\Vendidero\OneStopShop\Tax::import_oss_tax_rates();
			} else {
				\Vendidero\OneStopShop\Tax::import_default_tax_rates();
			}
		}

		/**
		 * Updates WooCommerce Options if user chooses to automatically adapt german options
		 */
		public static function set_default_settings() {
			global $wpdb;

			$base_country = ( isset( WC()->countries ) ) ? WC()->countries->get_base_country() : 'DE';
			$eu_countries = ( isset( WC()->countries ) ) ? WC()->countries->get_european_union_countries() : array( $base_country );

			$options = array(
				'woocommerce_default_country'            => $base_country,
				'woocommerce_currency'                   => 'EUR',
				'woocommerce_currency_pos'               => 'right_space',
				'woocommerce_price_thousand_sep'         => '.',
				'woocommerce_price_decimal_sep'          => ',',
				'woocommerce_price_num_decimals'         => 2,
				'woocommerce_weight_unit'                => 'kg',
				'woocommerce_dimension_unit'             => 'cm',
				'woocommerce_calc_taxes'                 => 'yes',
				'woocommerce_prices_include_tax'         => 'yes',
				'woocommerce_tax_round_at_subtotal'      => 'yes',
				'woocommerce_tax_display_cart'           => 'incl',
				'woocommerce_tax_display_shop'           => 'incl',
				'woocommerce_tax_total_display'          => 'itemized',
				'woocommerce_tax_based_on'               => 'shipping',
				'woocommerce_allowed_countries'          => 'specific',
				'woocommerce_specific_allowed_countries' => $eu_countries,
				'woocommerce_default_customer_address'   => 'base',
				'woocommerce_gzd_hide_tax_rate_shop'     => \Vendidero\OneStopShop\Package::oss_procedure_is_enabled() ? 'yes' : 'no'
			);

			if ( ! empty( $options ) ) {
				foreach ( $options as $key => $option ) {
					update_option( $key, $option );
				}
			}
		}

		/**
		 * Create pages that the plugin relies on, storing page id's in variables.
		 *
		 * @access public
		 * @return void
		 */
		public static function create_pages() {

			if ( ! function_exists( 'wc_create_page' ) ) {
				include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
			}

			/**
			 * Filter to add/edit pages to be created on install.
			 *
			 * @param array $pages Array containing page data.
			 *
			 * @since 1.0.0
			 *
			 */
			$pages = apply_filters( 'woocommerce_gzd_create_pages', array(
				'data_security'   => array(
					'name'    => _x( 'data-security', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Privacy Policy', 'Page title', 'woocommerce-germanized' ),
					'content' => ''
				),
				'imprint'         => array(
					'name'    => _x( 'imprint', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Imprint', 'Page title', 'woocommerce-germanized' ),
					'content' => '[gzd_complaints]'
				),
				'terms'           => array(
					'name'    => _x( 'terms', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Terms & Conditions', 'Page title', 'woocommerce-germanized' ),
					'content' => ''
				),
				'revocation'      => array(
					'name'    => _x( 'revocation', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Cancellation Policy', 'Page title', 'woocommerce-germanized' ),
					'content' => ''
				),
				'shipping_costs'  => array(
					'name'    => _x( 'shipping-methods', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Shipping Methods', 'Page title', 'woocommerce-germanized' ),
					'content' => ''
				),
				'payment_methods' => array(
					'name'    => _x( 'payment-methods', 'Page slug', 'woocommerce-germanized' ),
					'title'   => _x( 'Payment Methods', 'Page title', 'woocommerce-germanized' ),
					'content' => '[payment_methods_info]'
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
			include_once WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/abstract-wc-gzd-settings-tab.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-legal-checkboxes.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/class-wc-gzd-settings-germanized.php';

			$settings = new WC_GZD_Settings_Germanized();

			/**
			 * Filter to adjust default options to be created on install.
			 *
			 * @param array $settings The settings to be added as wp_option on install.
			 *
			 * @since 1.0.0
			 */
			$options = apply_filters( 'woocommerce_gzd_installation_default_settings', $settings->get_settings_for_section_core( '' ) );

			$manager = WC_GZD_Legal_Checkbox_Manager::instance();
			$manager->do_register_action();

			$checkbox_options = $manager->get_options();

			foreach ( $manager->get_checkboxes( array( 'is_core' => true ) ) as $id => $checkbox ) {

				if ( ! isset( $checkbox_options[ $id ] ) ) {
					$checkbox_options[ $id ] = array();
				}

				foreach ( $checkbox->get_form_fields() as $field ) {
					if ( isset( $field['default'] ) && isset( $field['id'] ) ) {
						$field_id = str_replace( $checkbox->get_form_field_id_prefix(), '', $field['id'] );

						if ( ! isset( $checkbox_options[ $id ][ $field_id ] ) ) {
							$checkbox_options[ $id ][ $field_id ] = $field['default'];
						}
					}
				}
			}

			$manager->update_options( $checkbox_options );

			foreach ( $options as $value ) {
				if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
					wp_cache_delete( $value['id'], 'options' );

					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}

endif;

return new WC_GZD_Install();
