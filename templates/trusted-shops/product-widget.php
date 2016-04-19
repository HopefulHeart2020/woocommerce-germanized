<?php
/**
 * Trusted Shops Product Widget
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	global $post;
	$product = wc_get_product( $post->ID );
?>
<div id="ts_product_widget"></div>

<script type="text/javascript" src="//widgets.trustedshops.com/reviews/tsSticker/tsProductStickerSummary.js"></script>

<script type="text/javascript">
	<?php echo WC_germanized()->trusted_shops->get_product_widget_code( true, array( 'sku' => ( $product->get_sku() ? $product->get_sku() : $product->id ) ) ); ?>
</script>