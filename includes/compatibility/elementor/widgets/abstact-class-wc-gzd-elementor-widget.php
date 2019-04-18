<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;

abstract class WC_GZD_Elementor_Widget extends ElementorPro\Modules\Woocommerce\Widgets\Products_Base {

    public function get_keywords() {
        return [ 'woocommerce', 'shop', 'product' ];
    }

    public function get_name() {
        return 'woocommerce-gzd-' . str_replace( '_', '-', $this->get_postfix() );
    }

    abstract public function get_postfix();

    protected abstract function get_title_raw();

    public function get_title() {
        return $this->get_title_prefix() . $this->get_title_raw();
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'section_' . $this->get_postfix() . '_style',
            [
                'label' => $this->get_title_raw(),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        if ( apply_filters( 'woocommerce_gzd_show_elementor_upgrade_notice', true ) ) {
            $this->add_responsive_control(
                'upgrade',
                [
                    'label' => '',
                    'type'  => Controls_Manager::RAW_HTML,
                    'raw'   => sprintf( __( 'Upgrade to WooCommerce Germanized Pro to use your our custom Elementor Widgets. %s', 'woocommerce-germanized' ), '<a class="button button-primary elementor-button" href="https://vendidero.de/woocommerce-germanized" target="_blank" style="margin-top: 10px;">' . __( 'Upgrade now', 'woocommerce-germanized' ) . '</a>' ),
                ]
            );
        }

        do_action( "woocommerce_gzd_elementor_widget_{$this->get_postfix()}_controls", $this, Controls_Manager::class );

        $this->end_controls_section();
    }

    protected function get_title_prefix() {
        if ( ! apply_filters( 'woocommerce_gzd_show_elementor_upgrade_notice', true ) ) {
            return '';
        }

        return ' <span style="font-size: 10px; margin: 0 1em; margin-bottom: .5em; display: block; background: #e4e4e4; border: 1px solid #CCC; color: #555; border-radius: 3px; padding: 1px 3px; text-align: center; text-transform: uppercase;">Germanized Pro</span>';
    }

    protected function render() {
        $product = wc_get_product();

        if ( ! $product ) {
            return '';
        }

        do_action( "woocommerce_gzd_elementor_widget_{$this->get_postfix()}_render", $product, $this );
    }

    public function render_plain_content() {}
}
