jQuery( function ( $ ) {

    var wc_gzd_product_variations_actions = {

        params: {},

        init: function() {
            this.params = wc_gzd_admin_product_variations_params;

            $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', this.variations_loaded );
            $( '#woocommerce-product-data' ).on( 'click', '.woocommerce_variation', this.show_or_hide_unit_variation );
            $( '#general_product_data' ).on( 'blur', 'input#_unit_base', this.show_or_hide_unit_variation );
            $( '#general_product_data' ).on( 'change', 'select#_unit', this.show_or_hide_unit_variation );

            $( document ).bind( 'woocommerce_variations_save_variations_button', this.save_variations );
            $( document ).bind( 'woocommerce_variations_save_variations_on_submit', this.save_variations );

            $( document ).on( 'click', '.wc-gzd-general-product-data-tab', this.on_click_general_product_data );

            $( 'select.variation_actions' ).on( 'variable_delivery_time_ajax_data', this.onSetDeliveryTime );
            $( 'select.variation_actions' ).on( 'variable_unit_product_ajax_data', this.onSetProductUnit );

            $( '#variable_product_options' )
                .on( 'change', 'input.variable_service', this.variable_is_service )
                .on( 'change', 'input.variable_used_good', this.variable_is_used_good )
                .on( 'change', 'input.variable_defective_copy', this.variable_is_defective_copy );
        },

        variations_loaded: function( event, needsUpdate ) {
            needsUpdate = needsUpdate || false;

            var wrapper = $( '#woocommerce-product-data' );

            if ( ! needsUpdate ) {
                /**
                 * This will mark variations as needing updates (which is not the case)
                 */
                $( 'input.variable_service, input.variable_used_good, input.variable_defective_copy', wrapper ).trigger( 'change' );

                // Remove variation-needs-update classes
                $( '.woocommerce_variations .variation-needs-update', wrapper ).removeClass( 'variation-needs-update' );

                // Disable cancel and save buttons
                $( 'button.cancel-variation-changes, button.save-variation-changes', wrapper ).attr( 'disabled', 'disabled' );
            }
        },

        variable_is_service: function() {
            $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_service' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_service' ).show();
            }
        },

        variable_is_used_good: function() {
            $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_used_good' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_used_good' ).show();
            }
        },

        variable_is_defective_copy: function() {
            $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_defective_copy' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_defective_copy' ).show();
            }
        },

        onSetDeliveryTime: function( e, data ) {
            return wc_gzd_product_variations_actions.onVariationAction( data, 'set_delivery_time' );
        },

        onSetProductUnit: function( e, data ) {
            return wc_gzd_product_variations_actions.onVariationAction( data, 'set_product_unit' );
        },

        onVariationAction: function( data, type ) {
            var value = window.prompt( wc_gzd_product_variations_actions.params['i18n_' + type] );

            if ( value !== null ) {
                data.value = value;

                return data;
            } else {
                return;
            }
        },

        on_click_general_product_data: function() {
        	$( 'ul.wc-tabs > li.general_options > a' ).trigger( 'click' );
        	return false;
		},

        save_variations: function() {

            var fields = [ 'unit', 'unit_base', 'unit_product' ];
            var variations = $( '.woocommerce_variations' ).find( '.woocommerce_variation' );

            $.each( fields, function( index, id ) {
                var parent_val = $( '#_' + id ).val();

                variations.each( function() {
                    $( this ).find( '.wc-gzd-parent-' + id ).val( parent_val );
                });
            });

        },

        show_or_hide_unit_variation: function() {
            if ( wc_gzd_product_variations_actions.is_variable() ) {

                $( '.variable_pricing_unit .form-row' ).hide();
                $( '.variable_pricing_unit .wc-gzd-unit-price-disabled-notice' ).show();

                if ( ! wc_gzd_product_variations_actions.has_unit_price() && wc_gzd_product_variations_actions.has_unit() ) {

                    $( '.variable_pricing_unit .form-row' ).hide();
                    $( '.variable_pricing_unit .wc-gzd-unit-price-disabled-notice' ).show();
                    $( '.variable_pricing_unit' ).find( 'input[name*=variable_unit_product]' ).parents( '.form-row' ).show();

                } else if ( wc_gzd_product_variations_actions.has_unit_price() ) {

                    $( '.variable_pricing_unit .form-row' ).show();
                    $( '.variable_pricing_unit .wc-gzd-unit-price-disabled-notice' ).hide();
                }

                var $last = $( '.variable_pricing_unit .form-row:not(.wc-gzd-unit-price-disabled-notice):visible:last' );

                if ( $last.length > 0 && $last.hasClass( 'form-row-first' ) ) {
                    $( '.variable_pricing_unit .wc-gzd-unit-price-disabled-notice' ).removeClass( 'form-row-first' ).addClass( 'form-row-last' );
				} else {
                    $( '.variable_pricing_unit .wc-gzd-unit-price-disabled-notice' ).removeClass( 'form-row-last' ).addClass( 'form-row-first' );
                }
            }
        },

        is_variable: function() {
            return $( 'select#product-type' ).val() === 'variable';
        },

        has_unit: function() {
            return $( '#_unit' ).val() !== '0';
        },

        has_unit_price: function() {
            return $( '#_unit' ).val() !== '0' && $( '#_unit_base' ).val().length !== 0;
        }

    };

    wc_gzd_product_variations_actions.init();

});