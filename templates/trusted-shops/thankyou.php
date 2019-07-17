<?php
/**
 * Thankyou page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order = wc_get_order( $order_id );

?>
<!-- Module: WooCommerce Germanized -->
<div id="trustedShopsCheckout" style="display: none;">
	<span id="tsCheckoutOrderNr"><?php echo wc_ts_get_crud_data( $order, 'id' );?></span> 
	<span id="tsCheckoutBuyerEmail"><?php echo wc_ts_get_crud_data( $order, 'billing_email' ); ?></span>
	<span id="tsCheckoutBuyerId"><?php echo wc_ts_get_crud_data( $order, 'user_id' ); ?></span>
	<span id="tsCheckoutOrderAmount"><?php echo $order->get_total(); ?></span>
	<span id="tsCheckoutOrderCurrency"><?php echo wc_ts_get_order_currency( $order ); ?></span>
	<span id="tsCheckoutOrderPaymentType"><?php echo $plugin->get_payment_gateway( wc_ts_get_crud_data( $order, 'payment_method' ) );?></span>
	<span id="tsCheckoutOrderEstDeliveryDate"></span>
	<?php if ( $plugin->is_product_reviews_enabled() ) : ?>
		<?php foreach( $order->get_items() as $item_id => $item ) : 
			
			$org_product    = ( is_callable( array( $item, 'get_product' ) ) ) ? $item->get_product() : $order->get_product_from_item( $item );
		    $parent_product = $org_product;

	        if ( ! $org_product ) {
	            continue;
	        }
			
			// Currently not supporting reviews for variations	
			if ( $org_product->is_type( 'variation' ) ) {
                $parent_product = wc_get_product( wc_ts_get_crud_data( $org_product, 'parent' ) );
            }

            /**
             * Filter to adjust Trusted Shops product gtin.
             *
             * @since 2.0.0
             *
             * @param string     $gtin The gtin data.
             * @param WC_Product $product The product object.
             */
            $product_gtin  = apply_filters( 'woocommerce_gzd_trusted_shops_product_gtin', $plugin->get_product_gtin( $org_product ), $org_product );

            /**
             * Filter to adjust Trusted Shops product brand.
             *
             * @since 2.0.0
             *
             * @param string     $gtin The brand data.
             * @param WC_Product $product The product object.
             */
            $product_brand = apply_filters( 'woocommerce_gzd_trusted_shops_product_brand', $plugin->get_product_brand( $parent_product ), $parent_product );

            /**
             * Filter to adjust Trusted Shops product mpn.
             *
             * @since 2.0.0
             *
             * @param string     $gtin The mpn data.
             * @param WC_Product $product The product object.
             */
            $product_mpn   = apply_filters( 'woocommerce_gzd_trusted_shops_product_mpn', $plugin->get_product_mpn( $org_product ), $org_product );
			?>
			<span class="tsCheckoutProductItem">
				<span class="tsCheckoutProductUrl"><?php echo get_permalink( wc_ts_get_crud_data( $parent_product, 'id' ) ); ?></span>
				<span class="tsCheckoutProductImageUrl"><?php echo $plugin->get_product_image( $org_product ); ?></span>
				<span class="tsCheckoutProductName"><?php echo get_the_title( wc_ts_get_crud_data( $parent_product, 'id' ) ); ?></span>
                <span class="tsCheckoutProductSKU"><?php echo ( $parent_product->get_sku() ? $parent_product->get_sku() : wc_ts_get_crud_data( $parent_product, 'id' ) ); ?></span>
                <span class="tsCheckoutProductGTIN"><?php echo $product_gtin; ?></span>
                <span class="tsCheckoutProductBrand"><?php echo $product_brand; ?></span>
                <span class="tsCheckoutProductMPN"><?php echo $product_mpn; ?></span>
            </span>
		<?php endforeach; ?>
	<?php endif; ?>
</div>