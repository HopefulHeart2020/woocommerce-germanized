<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Settings_Germanized' ) ) :

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id    = 'germanized';
		$this->label = __( 'Germanized', 'woocommerce-germanized' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_filter( 'woocommerce_gzd_get_settings_display', array( $this, 'get_display_settings' ) );
		add_action( 'woocommerce_gzd_before_save_section_', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_after_save_section_', array( $this, 'after_save' ), 0, 1 );

	}

	/**
	 * Gets setting sections
	 */
	public function get_sections() {
		$sections = apply_filters( 'woocommerce_gzd_settings_sections', array(
			''   		 	=> __( 'General Options', 'woocommerce-germanized' ),
			'display'       => __( 'Display Options', 'woocommerce-germanized' ),
		) );
		return $sections;
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		
		$delivery_terms = array('' => __( 'None', 'woocommerce-germanized' ));
		$terms = get_terms( 'product_delivery_time', array('fields' => 'id=>name', 'hide_empty' => false) );
		if ( !is_wp_error( $terms ) )
			$delivery_terms = $delivery_terms + $terms;

		$mailer 			= WC()->mailer();
		$email_templates 	= $mailer->get_emails();
		$email_select 		= array();
		foreach ( $email_templates as $email )
			$email_select[ $email->id ] = empty( $email->title ) ? ucfirst( $email->id ) : ucfirst( $email->title );

		return apply_filters( 'woocommerce_germanized_settings', array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_small_enterprise',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'set this Option if you have chosen <a href="%s" target="_blank">&#167;19 UStG</a>', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) )
			),

			array(
				'title' 	=> __( 'Show no VAT notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show no VAT &#167;19 UStG notice on single product', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_small_enterprise',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array(
				'title' 	=> __( 'Submit Order Button Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text serves as Button text for the Order Submit Button.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_submit_btn_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Buy Now', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Phone as required field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Should phone number be a required field within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_phone_required',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array(
				'title' 	=> __( 'Add title field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a title field to the address within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_address_field',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Legal Pages', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'legal_pages_options' ),

			array(
				'title' 	=> __( 'Imprint', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain an imprint with your company\'s information.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_imprint_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Data Security Statement', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding your data security policy.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_data_security_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Power of Revocation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding your customer\'s Right of Revocation.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_revocation_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Payment Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding the Payment Methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_payment_methods_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Shipping Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding shipping methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_shipping_costs_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array( 'type' => 'sectionend', 'id' => 'legal_pages_options' ),

			array( 'title' => __( 'Delivery Times', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'delivery_times_options' ),

			array(
				'title' 	=> __( 'Default Delivery Time', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This delivery time will be added to every product if no delivery time has been chosen individually', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_delivery_time',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$delivery_terms,
				'desc_tip'	=>  true,
			),

			array(
				'title' 	=> __( 'Delivery Time Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to indicate delivery time for products. Use {delivery_time} as placeholder.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_delivery_time_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Delivery time: {delivery_time}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_times_options' ),

			array(	'title' => __( 'Shipping Costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_costs_options' ),

			array(
				'title' 	=> __( 'Shipping Costs Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to inform the customer about shipping costs. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_shipping_costs_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'plus {link}Shipping Costs{/link}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array(	'title' => __( 'Unit Price', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'unit_price_options' ),

			array(
				'title' 	=> __( 'Unit Price Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to display the unit price. Use {price} to insert the price.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_unit_price_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( '{price}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'unit_price_options' ),

			array(	'title' => __( 'Right of Recission', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'recission_options' ),

			array(
				'title' 	=> __( 'Revocation Address', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Type in an address, telephone/telefax number, email address which is to be used as revocation address', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_revocation_address',
				'type' 		=> 'textarea',
			),

			array( 'type' => 'sectionend', 'id' => 'recission_options' ),

			array(	'title' => __( 'E-Mails', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_options' ),

			array(
				'title' 	=> __( 'Attach Imprint', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Imprint to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_imprint',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array(
				'title' 	=> __( 'Attach Terms & Conditions', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Terms & Conditions to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_terms',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array(
				'title' 	=> __( 'Attach Power of Recission', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Power of Recission to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_revocation',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
				'default'	=> array( 'customer_processing_order' ),
			),

			array(
				'title' 	=> __( 'Attach Data Security', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Data Security Statement to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_data_security',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array( 'type' => 'sectionend', 'id' => 'email_options' ),

			array(	'title' => __( 'Virtual VAT', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'virtual_vat_options' ),

			array(
				'title' 	=> __( 'Enable Virtual VAT', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_enable_virtual_vat',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ),
			),

			array( 'type' => 'sectionend', 'id' => 'virtual_vat_options' ),

		) ); // End general settings
	}

	public function get_display_settings() {

		return array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Add to Cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show add to cart button on listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_add_to_cart',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'unset this option if you don\'t want to show the add to cart button within the product listings', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Link to Details', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Want to link to product details page instead of add to cart within listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Decide whether you like to link to your product\'s details page instead of displaying an add to cart button within product listings.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Product Details Text', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details_text',
				'default'	=> __( 'Details', 'woocommerce-germanized' ),
				'type' 		=> 'text',
				'desc_tip'	=> __( 'If you have chosen to link to product details page instead of add to cart URL you may want to change the button text.', 'woocommerce-germanized' ),
				'css' 		=> 'min-width:300px;',
			),

			array(
				'title' 	=> __( 'Notice Footer', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show a global VAT notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_vat_notice',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'start'
			),

			array(
				'desc' 		=> __( 'Show a global sale price notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_sale_price_notice',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'checkboxgroup'		=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Products', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'product_options' ),

			array(
				'title' 	=> __( 'Show within Product Listings', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Unit Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> 'end',
			),

			array(
				'title' 	=> __( 'Show on Product Detail Page', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Unit Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> 'end',
			),

			array(
				'title' 	=> __( 'Shipping Costs for Virtual', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select this option if you want to display shipping costs notice for virtual products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_shipping_costs_virtual',
				'type' 		=> 'checkbox',
				'default'	=> 'false',
			),

			array( 'type' => 'sectionend', 'id' => 'product_options' ),

			array(	'title' => __( 'Checkout & Cart', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'checkout_options' ),

			array(
				'title' 	=> __( 'Hide taxes estimated', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Do you want to hide the "taxes and shipping estimated" text from your cart?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_hide_cart_tax_estimated',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'By default WooCommerce adds a "taxes and shipping estimated" text to your cart. This might puzzle your customers and may not meet german law.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show Thumbnails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show product thumbnails on checkout page?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_thumbnails',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Uncheck if you don\'t want to show your product thumbnails within checkout table.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide Shipping Select', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide shipping rate selection from checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_shipping_rate_select',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will hide shipping rate selection from checkout. By then customers will only be able to change their shipping rate on cart page.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show back to cart button', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show back to cart button within your checkout table?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_back_to_cart_button',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This button may let your customer edit their order before submitting. Some people state that this button should be hidden to avoid legal problems.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Checkout Table Color', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_table_color',
				'desc_tip'	=> __( 'Choose the color of your checkout product table. This table should be highlighted within your checkout page.', 'woocommerce-germanized' ),
				'default'	=> '#eeeeee',
				'type' 		=> 'color',
			),

			array(
				'title' 	=> __( 'Checkout Legal Display', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Use Text without Checkbox', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_legal_no_checkbox',
				'desc_tip'	=> __( 'This version will remove checkboxes from Checkout and display a text instead. This seems to be legally compliant (Zalando & Co are using this option).', 'woocommerce-germanized' ),
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} and your {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Legal Text Error', 'woocommerce-germanized' ),
				'desc' 		=> __( 'If you have chosen to use checkbox validation please choose a error message which will be shown if the user doesn\'t check checkbox. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'To finish the order you have to accept to our {term_link}Terms and Conditions{/term_link} and {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_error',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Show digital notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show checkbox for digital products.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Disable this option if you want your customers to obtain their right of recission even if digital products are being bought.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_digital_checkbox',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Digital Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button if a user has picked a digital product. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_digital',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Order Success Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a custom text to display on order success page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_order_success_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Order Success Data', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide product table and customer data on order success page', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_order_success_details',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_options' ),

		);

	}

	public function output() {
		global $current_section;
		$settings = $this->get_settings();
		$sidebar = $this->get_sidebar();

		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
					$sidebar = apply_filters( 'woocommerce_gzd_get_sidebar_' . $section, $this->get_sidebar() );
				}
			}
		}

		?>
		<div class="wc-gzd-admin-settings">
			<?php do_action( 'wc_germanized_settings_section_before_' . sanitize_title( $current_section ) ); ?>
			<?php WC_Admin_Settings::output_fields( $settings ); ?>
			<?php do_action( 'wc_germanized_settings_section_after_' . sanitize_title( $current_section ) ); ?>
		</div>
		<?php echo $sidebar; ?>
		<?php
	}

	public function get_sidebar() {
		$html = '
			<div class="wc-gzd-admin-settings-sidebar">
				<h3>VendiPro - Typisch deutsch!</h3>
				<div class="wc-gzd-sidebar-img">
					<a href="http://vendidero.de/vendipro" target="_blank"><img class="browser" src="' . WC_germanized()->plugin_url() . '/assets/images/vendidero.jpg" /></a>
				</div>
				<p>VendiPro ist ein für den deutschen Markt entwickeltes WooCommerce Theme. Mit VendiPro sind alle WooCommerce und WooCommerce Germanized Einstellungen auch optisch perfekt auf den deutschen Markt abgestimmt.</p>
				<div class="wc-gzd-sidebar-action">
					<a class="button button-primary wc-gzd-button" href="http://vendidero.de/vendipro" target="_blank">jetzt entdecken</a>
					<span class="small">ab 49,95 € inkl. Mwst. und 1 Jahr Updates & Support!</span>
				</div>
			</div>
		';
		return $html;
	}

	/**
	 * Save settings
	 */
	public function save() {

		global $current_section;

		$settings = array();

		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
				}
			}
		}
		if ( empty( $settings ) )
			return;

		do_action( 'woocommerce_gzd_before_save_section_' . $current_section, $settings );

		WC_Admin_Settings::save_fields( $settings );

		do_action( 'woocommerce_gzd_after_save_section_' . $current_section, $settings );
	}

	public function before_save( $settings ) {
		if ( !empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				if ( $setting[ 'id' ] == 'woocommerce_gzd_small_enterprise' ) {
					if ( get_option('woocommerce_gzd_small_enterprise') == 'no' && !empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to not show tax
						update_option( 'woocommerce_calc_taxes', 'no' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_price_display_suffix', '' );
					} elseif ( get_option('woocommerce_gzd_small_enterprise') == 'yes' && ! isset( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to show tax
						update_option( 'woocommerce_calc_taxes', 'yes' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
					}
				} else if ( $setting[ 'id' ] == 'woocommerce_gzd_enable_virtual_vat' ) {
					if ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) != 'yes' && ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
						if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) )
							continue;
						// Update WooCommerce options to show prices including taxes
						// Check if is small business
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_tax_total_display', 'itemized' );
					}
				}
			}
		}
	}

	public function after_save( $settings ) {
		if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) && ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
			update_option( 'woocommerce_gzd_enable_virtual_vat', 'no' );
			WC_Admin_Settings::add_error( __( 'Sorry, but the new Virtual VAT rules cannot be applied to small business.', 'woocommerce-germanized' ) );
		}
	}

}

endif;

?>