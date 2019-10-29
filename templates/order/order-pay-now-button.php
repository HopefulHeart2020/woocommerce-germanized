<?php
/**
 * The Template for displaying the order pay now button e.g. in emails or on order page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/order/order-pay-now-button.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
$order = wc_get_order( $order_id );
?>

<p>
    <a href="<?php echo $url; ?>"
       class="button wc-gzdp-order-pay-button"><?php _e( 'Pay now', 'woocommerce-germanized' ); ?></a>
</p>