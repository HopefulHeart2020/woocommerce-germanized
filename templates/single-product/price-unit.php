<?php
/**
 * The Template for displaying unit price for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/price-unit.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->has_unit() ) : ?>
    <p class="price price-unit smaller wc-gzd-additional-info"><?php echo wc_gzd_get_product( $product )->get_unit_price_html(); ?></p>
<?php endif; ?>