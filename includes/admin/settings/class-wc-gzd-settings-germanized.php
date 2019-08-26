<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

    protected $id = 'germanized';

    protected $tabs = null;

	public function __construct() {
		$this->label = __( 'Germanized', 'woocommerce-germanized' );
		$this->get_tabs();

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		parent::__construct();
	}

	public function admin_styles() {
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/';

		// Register admin styles.
		wp_register_style( 'woocommerce-gzd-admin-settings', $assets_path . 'css/admin-settings' . $suffix . '.css', array( 'woocommerce_admin_styles', 'woocommerce-gzd-admin' ), WC_GERMANIZED_VERSION );

		// Admin styles for WC pages only.
		if ( $this->is_active() ) {
			wp_enqueue_style( 'woocommerce-gzd-admin-settings' );
		}
	}

	public function admin_scripts() {
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/';

		wp_register_script( 'wc-gzd-admin-settings', $assets_path . 'js/admin/settings' . $suffix . '.js', array( 'jquery', 'woocommerce_settings' ), WC_GERMANIZED_VERSION, true );

		if ( $this->is_active() ) {
			wp_enqueue_script( 'wc-gzd-admin-settings' );
		}
	}

	protected function is_active() {
		if ( isset( $_GET['tab'] ) && strpos( $_GET['tab'], 'germanized' ) !== false ) {
			return true;
		}

		return false;
	}

	public function add_body_classes( $classes ) {
		if ( $this->is_active() ) {
			$classes = $classes . ' wc-gzd-settings';
		}

        return $classes;
	}

	public function get_tabs() {
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shopmarks.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-emails.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-taxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-button-solution.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-checkboxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-doi.php';

	    $tabs = apply_filters( 'woocommerce_gzd_admin_settings_tabs', array(
            'shopmarks'       => 'WC_GZD_Settings_Tab_Shopmarks',
            'taxes'           => 'WC_GZD_Settings_Tab_Taxes',
            'button_solution' => 'WC_GZD_Settings_Tab_Button_Solution',
            'emails'          => 'WC_GZD_Settings_Tab_Emails',
            'checkboxes'      => 'WC_GZD_Settings_Tab_Checkboxes',
            'double_opt_in'   => 'WC_GZD_Settings_Tab_DOI',
        ) );

	    if ( is_null( $this->tabs ) ) {
	    	$this->tabs = array();

	    	foreach( $tabs as $key => $tab ) {
	    		$this->tabs[ $key ] = new $tab;
		    }
	    }

	    return $this->tabs;
    }

    public function output() {
		$GLOBALS['hide_save_button'] = true;
		$tabs                        = $this->get_tabs();

	    include_once dirname( __FILE__ ) . '/views/html-admin-settings-tabs.php';
    }
}