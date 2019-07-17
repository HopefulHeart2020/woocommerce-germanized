<?php
/**
 * Checkout SEPA terms checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkbox_id = $checkbox->get_id();

/**
 * Before render checkbox template.
 *
 * Fires before a checkbox with `$checkbox_id` is rendered.
 *
 * @since 2.0.0
 *
 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
 */
do_action( "woocommerce_gzd_before_legal_checkbox_{$checkbox_id}", $checkbox );
?>

<p class="<?php $checkbox->render_classes( $checkbox->get_html_wrapper_classes() ); ?>" style="<?php echo esc_attr( $checkbox->get_html_style() ); ?>" data-checkbox="<?php echo esc_attr( $checkbox->get_id() ); ?>">
    <label for="<?php echo esc_attr( $checkbox->get_html_id() ); ?>" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
        <input type="checkbox" class="<?php $checkbox->render_classes( $checkbox->get_html_classes() ); ?>" name="<?php echo esc_attr( $checkbox->get_html_name() ); ?>" id="<?php echo esc_attr( $checkbox->get_html_id() ); ?>" />
        <span class="woocommerce-gzd-<?php echo esc_attr( $checkbox->get_html_id() ); ?>-checkbox-text"><?php echo $checkbox->get_label(); ?></span>
        <a href="" rel="prettyPhoto" id="show-direct-debit-pretty" class="hidden"></a>
	</label>
</p>