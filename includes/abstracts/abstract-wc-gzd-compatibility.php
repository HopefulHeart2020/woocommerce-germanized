<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The
 *
 * @class WC_GZD_Compatibility
 * @version  1.0.0
 * @author   Vendidero
 */
abstract class WC_GZD_Compatibility {

	public function __construct() {
		add_action( 'init', array( $this, 'early_execution' ), 0 );
		add_action( 'init', array( $this, 'load' ), 15 );

		$this->after_plugins_loaded();
	}

	public function early_execution() {
	}

	public function after_plugins_loaded() {
	}

	protected static function parse_version_data( $version_data ) {
		$version_data = wp_parse_args( $version_data, array(
			'version'           => '1.0.0',
			'requires_at_least' => '',
			'tested_up_to'      => '',
		) );

		if ( empty( $version_data['requires_at_least'] ) && empty( $version_data['tested_up_to'] ) ) {
			$version_data['requires_at_least'] = $version_data['version'];
			$version_data['tested_up_to']      = $version_data['version'];
		} elseif ( empty( $version_data['tested_up_to'] ) ) {
			$version_data['tested_up_to'] = $version_data['requires_at_least'];
			if ( wc_gzd_get_dependencies()->compare_versions( $version_data['version'], $version_data['requires_at_least'], '>' ) ) {
				$version_data['tested_up_to'] = $version_data['version'];
			}
		} elseif ( empty( $version_data['requires_at_least'] ) ) {
			$version_data['requires_at_least'] = $version_data['tested_up_to'];

			if ( wc_gzd_get_dependencies()->compare_versions( $version_data['version'], $version_data['requires_at_least'], '<' ) ) {
				$version_data['requires_at_least'] = $version_data['version'];
			}
		}

		return $version_data;
	}

	public static function is_applicable() {
		return static::is_activated() && static::is_supported();
	}

	public static function is_activated() {
		return wc_gzd_get_dependencies()->is_plugin_activated( static::get_path() );
	}

	public static function is_supported() {
		$version_data = static::get_version_data();

		return
			wc_gzd_get_dependencies()->compare_versions( $version_data['version'], $version_data['requires_at_least'], '>=' ) &&
			wc_gzd_get_dependencies()->compare_versions( $version_data['version'], $version_data['tested_up_to'], '<=' );
	}

	abstract public static function get_name();

	abstract public static function get_path();

	public static function get_version_data() {
		return static::parse_version_data( array() );
	}

	abstract public function load();
}
