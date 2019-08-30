<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class 		WC_GZD_Settings_Tab_Taxes
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Taxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Find tax related settings like shipping costs taxation here.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Taxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'taxes';
	}

	public function get_sections() {
		return array(
			''                      => __( 'VAT', 'woocommerce-germanized' ),
			'split_tax'             => __( 'Split Tax', 'woocommerce-germanized' ),
			'differential_taxation' => __( 'Differential Taxation', 'woocommerce-germanized' ),
		);
	}

	protected function get_vat_settings() {
		$virtual_vat = 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) ? array() : array(
			'title' 	=> __( 'Virtual VAT', 'woocommerce-germanized' ),
			'desc' 		=> __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ) . '</div>',
			'id' 		=> 'woocommerce_gzd_enable_virtual_vat',
			'default'	=> 'no',
			'type' 		=> 'gzd_toggle',
		);

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'vat_options' ),

			$virtual_vat,

			array(
				'title' 	=> __( 'Tax Rate', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide specific tax rate within shop pages.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_tax_rate_shop',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This option will make sure that within shop pages no specific tax rates are shown. Instead only incl. tax or excl. tax notice is shown.', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'vat_options' ),
		);

		return array_merge( $settings, $this->get_vat_id_settings() );
	}

	protected function get_vat_id_settings() {
		return array(
			array( 'title' => __( 'VAT ID', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'vat_id_options', 'desc' => '<div class="notice inline notice-warning wc-gzd-premium-overlay"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and enjoy premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button button-primary wc-gzd-button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),
			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_enable_vat_check',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-vat-v2.png?v=' . WC_germanized()->version,
				'href'      => 'https://vendidero.de/woocommerce-germanized#vat',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'vat_id_options' ),
		);
	}

	protected function get_split_tax_settings() {

		$shipping_tax_example = sprintf( __( 'By choosing this option shipping cost taxation will be calculated based on tax rates within cart. Imagine the following example. Further information can be found <a href="%s" target="_blank">here</a>. %s', 'woocommerce-germanized' ), 'http://www.it-recht-kanzlei.de/umsatzsteuer-versandkosten-mehrwertsteuer.html', '<table class="wc-gzd-tax-example"><thead><tr><th>Produkt</th><th>Preis</th><th>MwSt.-Satz</th><th>Anteil</th><th>MwSt.</th></tr></thead><tbody><tr><td>Buch</td><td>' . wc_price( 40 ) . '</td><td>7%</td><td>40%</td><td>' . wc_price( 2.62 ) . '</td></tr><tr><td>DVD</td><td>' . wc_price( 60 ) . '</td><td>19%</td><td>60%</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>Versand</td><td>' . wc_price( 5 ) . '</td><td>7% | 19%</td><td>40% | 60%</td><td>' . wc_price( 0.13 ) . ' | ' . wc_price( 0.48 ) . '</td></tr></tbody></table>' );

		return array(
			array( 'title' => __( 'Shipping costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_tax_options' ),

			array(
				'title' 	=> __( 'Split-tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable split-tax calculation for shipping costs.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . $shipping_tax_example . '</div>',
				'id' 		=> 'woocommerce_gzd_shipping_tax',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Force', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force split-tax calculation for shipping methods.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipping_tax' => '',
				),
				'desc_tip'	=> __( 'This option will overwrite settings for each individual shipping method to force tax calculation (instead of only calculating tax for those methods which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_tax_options' ),

			array( 'title' => __( 'Fees', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'fee_tax_options' ),

			array(
				'title' 	=> __( 'Split-tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable split-tax calculation for fees.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Force', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force split-tax calculation for fees.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax_force',
				'default'	=> 'yes',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_fee_tax' => '',
				),
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual fee to force tax calculation (instead of only calculating tax for those fees which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'fee_tax_options' ),
		);
	}

	protected function get_differential_taxation_settings() {
		return array(
			array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'differential_taxation_options' ),

			array(
				'title' 	=> __( 'Taxation Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation text notice beneath product price.', 'woocommerce-germanized' ) . ' <div class="wc-gzd-additional-desc">' . __( 'If you have disabled this option, a normal VAT notice will be displayed, which is sufficient as Trusted Shops states. To further inform your customers you may enable this notice.', 'woocommerce-germanized' ) . '</div>',
				'id' 		=> 'woocommerce_gzd_differential_taxation_show_notice',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Notice Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be shown as a further notice for the customer to inform him about differential taxation.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_differential_taxation_notice_text',
				'type' 		=> 'textarea',
				'css' 		=> 'width:100%; height: 50px;',
				'default'	=> __( 'incl. VAT (differential taxation according to §25a UStG.)', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Checkout & E-Mails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation notice during checkout and in emails.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_differential_taxation_checkout_notices',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'differential_taxation_options' ),
		);
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_vat_settings();
		} elseif( 'split_tax' === $current_section ) {
			$settings = $this->get_split_tax_settings();
		} elseif( 'differential_taxation' === $current_section ) {
			$settings = $this->get_differential_taxation_settings();
		}

		return $settings;
	}

	protected function before_save( $settings, $current_section = '' ) {
		if ( '' === $current_section ) {
			if ( 'yes' !== get_option( 'woocommerce_gzd_enable_virtual_vat' ) && ! empty( $_POST['woocommerce_gzd_enable_virtual_vat'] ) ) {
				if ( 'no' === get_option( 'woocommerce_gzd_small_enterprise' ) ) {
					// Update WooCommerce options to show prices including taxes
					update_option( 'woocommerce_prices_include_tax', 'yes' );
					update_option( 'woocommerce_tax_display_shop', 'incl' );
					update_option( 'woocommerce_tax_display_cart', 'incl' );
					update_option( 'woocommerce_tax_total_display', 'itemized' );
				}
			}
		}

		parent::before_save( $settings, $current_section );
	}

	protected function after_save( $settings, $current_section = '' ) {
		if ( '' === $current_section ) {
			if ( 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) ) {
				if ( ! empty( $_POST['woocommerce_gzd_enable_virtual_vat'] ) ) {
					update_option( 'woocommerce_gzd_enable_virtual_vat', 'no' );
					WC_Admin_Settings::add_error( __( 'Sorry, but the new Virtual VAT rules cannot be applied to small business.', 'woocommerce-germanized' ) );
				}
			} elseif ( 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) ) {
				// Make sure that tax based location is set to billing address
				if ( 'base' === get_option( 'woocommerce_tax_based_on' ) ) {
					update_option( 'woocommerce_tax_based_on', 'billing' );
				}
			}
		}

		parent::after_save( $settings, $current_section );
	}
}