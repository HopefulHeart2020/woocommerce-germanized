<?php
/**
 * Adds unit price and delivery time to Product metabox.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Germanized_Meta_Box_Product_Data
 */
class WC_Germanized_Meta_Box_Product_Data {
	
	public static function init() {
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'output' ));
		add_action( 'woocommerce_process_product_meta_simple', array( __CLASS__, 'save' ), 1 );
		add_action( 'woocommerce_process_product_meta_external', array( __CLASS__, 'save' ), 1 );
	}

	public static function output() {
		global $post, $thepostid;

		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );
		$terms = array();

		$delivery_time = $_product->gzd_product->delivery_time;

		woocommerce_wp_select( array( 'id' => '_unit', 'label' => __( 'Unit', 'woocommerce-germanized' ), 'options' => array_merge( array( 'none' => __( 'Select unit', 'woocommerce-germanized' ) ), WC_germanized()->units->get_units() ), 'desc_tip' => true, 'description' => __( 'Needed if selling on a per unit basis', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_base', 'label' => __( 'Unit Base', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Unit price per amount (e.g. 100)', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_regular', 'label' => __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_sale', 'label' => __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		
		if ( version_compare( WC()->version, '2.3', '<' ) )
			return;
		?>
		
		<p class="form-field">
			<label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
			<input type="hidden" class="wc-product-search wc-gzd-delivery-time-search" style="width: 50%" id="delivery_time" name="delivery_time" data-minimum_input_length="1" data-allow_clear="true" data-placeholder="<?php _e( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ); ?>" data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false" data-selected="<?php echo ( $delivery_time ? $delivery_time->name : '' ); ?>" value="<?php echo ( $delivery_time ? $delivery_time->term_id : '' ); ?>" />
		</p>
		
		<?php
	}

	public static function save($post_id) {
		if ( isset( $_POST['_unit'] ) ) {
			update_post_meta( $post_id, '_unit', sanitize_text_field( $_POST['_unit'] ) );
		}
		if ( isset( $_POST['_unit_base'] ) ) {
			update_post_meta( $post_id, '_unit_base', ( $_POST['_unit_base'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_base'] ) );
		}
		if ( isset( $_POST['_unit_price_regular'] ) ) {
			update_post_meta( $post_id, '_unit_price_regular', ( $_POST['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_regular'] ) );
			update_post_meta( $post_id, '_unit_price', ( $_POST['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_regular'] ) );
		}
		if ( isset( $_POST['_unit_price_sale'] ) ) {
			update_post_meta( $post_id, '_unit_price_sale', '' );
			// Update Sale Price only if is on sale (Cron?!)
			if ( get_post_meta( $post_id, '_price', true ) != $_POST['_regular_price'] && $_POST['_unit_price_sale'] !== '' ) {
				update_post_meta( $post_id, '_unit_price_sale', ( $_POST['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_sale'] ) );
				update_post_meta( $post_id, '_unit_price', ( $_POST['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_sale'] ) );
			}
		}
		if ( isset( $_POST[ '_mini_desc' ] ) ) {
			update_post_meta( $post_id, '_mini_desc', esc_html( $_POST[ '_mini_desc' ] ) );
		}
		
		if ( isset( $_POST[ 'delivery_time' ] ) && ! is_numeric( $_POST[ 'delivery_time' ] ) )
			wp_set_post_terms( $post_id, sanitize_text_field( $_POST[ 'delivery_time' ] ), 'product_delivery_time' );
		else
			wp_set_object_terms( $post_id, absint( $_POST[ 'delivery_time' ] ) , 'product_delivery_time' );
	}

}

WC_Germanized_Meta_Box_Product_Data::init();