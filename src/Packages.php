<?php
/**
 * Loads Germanized packages from the /packages directory. These are packages developed outside of core.
 *
 * @package Vendidero/Germanized
 */

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.0.0
 */
class Packages {

	/**
	 * Static-only class.
	 */
	private function __construct() {
	}

	/**
	 * Array of package names and their main package classes.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 *
	 * @TODO remove one-stop-shop-woocommerce and woocommerce-trusted-shops after migration period.
	 */
	protected static $packages = array(
		'woocommerce-trusted-shops'        => '\\Vendidero\\TrustedShops\\Package',
		'woocommerce-germanized-shipments' => '\\Vendidero\\Germanized\\Shipments\\Package',
		'woocommerce-germanized-dhl'       => '\\Vendidero\\Germanized\\DHL\\Package',
		'one-stop-shop-woocommerce'        => '\\Vendidero\\OneStopShop\\Package',
	);

	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
	}

	public static function get_packages() {
		return self::$packages;
	}

	/**
	 * Callback for WordPress init hook.
	 */
	public static function on_init() {
		self::load_packages();
	}

	/**
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $package Package name.
	 *
	 * @return boolean
	 */
	public static function package_exists( $package ) {
		return file_exists( dirname( __DIR__ ) . '/packages/' . $package );
	}

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function load_packages() {
		foreach ( self::$packages as $package_name => $package_class ) {
			/**
			 * Do not load the OSS package in case the separate plugin is active or the default
			 * option after a bundled-install has not been set.
			 *
			 * @TODO remove after removing the package from Germanized core.
			 */
			if ( 'one-stop-shop-woocommerce' === $package_name ) {
				if ( PluginsHelper::is_oss_plugin_active() || ! get_option( 'oss_use_oss_procedure' ) ) {
					continue;
				} elseif ( version_compare( get_option( 'woocommerce_gzd_db_version', '1.0.0' ), '3.10.0', '<' ) ) {
					/**
					 * Temp check in case the DB update has not been completed yet. In this special case, check for global OSS activation and load package.
					 */
					if ( 'yes' !== get_option( 'oss_use_oss_procedure' ) && 'yes' !== get_option( 'oss_enable_auto_observation' ) ) {
						continue;
					}
				} elseif ( 'yes' !== get_option( 'woocommerce_gzd_is_oss_standalone_update' ) ) {
					/**
					 * After the DB update has been completed, check the temp option which indicates the necessity to load the bundled package.
					 */
					continue;
				}
			} elseif ( 'woocommerce-trusted-shops' === $package_name ) {
				if ( ( PluginsHelper::is_trusted_shops_plugin_active() && get_option( 'ts_easy_integration_client_id' ) ) || ! get_option( 'woocommerce_gzd_trusted_shops_id' ) ) {
					continue;
				} elseif ( version_compare( get_option( 'woocommerce_gzd_db_version', '1.0.0' ), '3.10.4', '<' ) ) {
					/**
					 * Temp check in case the DB update has not been completed yet. In this special case, check for global OSS activation and load package.
					 */
					if ( ! get_option( 'woocommerce_gzd_trusted_shops_id' ) ) {
						continue;
					}
				} elseif ( 'yes' !== get_option( 'woocommerce_gzd_is_ts_standalone_update' ) ) {
					/**
					 * After the DB update has been completed, check the temp option which indicates the necessity to load the bundled package.
					 */
					continue;
				}
			}

			if ( ! self::package_exists( $package_name ) ) {
				self::missing_package( $package_name );
				continue;
			}

			/**
			 * Prevent calling init twice in case feature plugin is installed
			 */
			if ( ! has_action( 'plugins_loaded', array( $package_class, 'init' ) ) ) {
				call_user_func( array( $package_class, 'init' ) );

				$package_hook_name = sanitize_key( $package_name );

				do_action( "woocommerce_gzd_package_{$package_hook_name}_init" );
			}
		}
	}

	/**
	 * If a package is missing, add an admin notice.
	 *
	 * @param string $package Package name.
	 */
	protected static function missing_package( $package ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				sprintf(
				/* Translators: %s package name. */
					esc_html__( 'Missing the Germanized %s package', 'woocommerce-germanized' ),
					'<code>' . esc_html( $package ) . '</code>'
				) . ' - ' . esc_html__( 'Your installation of Germanized is incomplete. If you installed Germanized from GitHub, please refer to this document to set up your development environment: https://github.com/vendidero/woocommerce-germanized/wiki/How-to-set-up-a-Germanized-development-environment', 'woocommerce-germanized' )
			);
		}
		add_action(
			'admin_notices',
			function () use ( $package ) {
				?>
				<div class="notice notice-error">
					<p>
						<strong>
							<?php
							printf(
							/* Translators: %s package name. */
								esc_html__( 'Missing the Germanized %s package', 'woocommerce-germanized' ),
								'<code>' . esc_html( $package ) . '</code>'
							);
							?>
						</strong>
						<br>
						<?php
						printf(
						/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of Germanized is incomplete. If you installed Germanized from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce-germanized' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
