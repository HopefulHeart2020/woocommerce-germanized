<?php
/**
 * Admin View: Page - Germanized Report
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

?>
<div class="updated woocommerce-message">
	<p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce' ); ?> </p>
	<p class="submit"><a href="#" class="button-primary debug-report"><?php _e( 'Get System Report', 'woocommerce' ); ?></a>
	<div id="debug-report">
		<textarea readonly="readonly"></textarea>
		<p class="submit"><button id="copy-for-support" class="button-primary" href="#" data-tip="<?php _e( 'Copied!', 'woocommerce' ); ?>"><?php _e( 'Copy for Support', 'woocommerce' ); ?></button></p>
	</div>
</div>
<br/>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WooCommerce Germanized"><?php _e( 'WooCommerce Germanized', 'woocommerce-germanized' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="WC GZD Version"><?php _e( 'Version', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo esc_html( WC_germanized()->version ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC GZD Database Version"><?php _e( 'Database Version', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo esc_html( get_option( 'woocommerce_gzd_db_version' ) ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Small Business"><?php _e( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Default Delivery Time"><?php _e( 'Default Delivery Time', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<?php

				$term_id = get_option( 'woocommerce_gzd_default_delivery_time' );
				$term = false;
				if ( $term_id )
					$term = get_term_by( 'id', $term_id, 'product_delivery_time' );

			?>
			<td><?php echo $term ? $term->name : '<mark class="no">'.'&ndash;'.'</mark>' . ( $term_id && ! $term ? ' [' . __( 'Term doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Checkout Fallback"><?php _e( 'Fallback Mode', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo 'yes' === get_option( 'woocommerce_gzd_display_checkout_fallback' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<?php do_action( 'woocommerce_gzd_status_after_germanized' ); ?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Taxes"><?php _e( 'Taxes', 'woocommerce-germanized' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Shipping Taxes"><?php _e( 'Shipping Taxes', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo 'yes' === get_option( 'woocommerce_gzd_shipping_tax' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' . ( 'yes' === get_option( 'woocommerce_gzd_shipping_tax_force' ) ? ' [' . __( 'Forced', 'woocommerce-germanized' ) . ']' : '' ) : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Fee Taxes"><?php _e( 'Fee Taxes', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo 'yes' === get_option( 'woocommerce_gzd_fee_tax' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' . ( 'yes' === get_option( 'woocommerce_gzd_fee_tax_force' ) ? ' [' . __( 'Forced', 'woocommerce-germanized' ) . ']' : '' ) : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Virtual VAT"><?php _e( 'Virtual VAT', 'woocommerce-germanized' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php echo 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Tax Rate Name Collision"><?php _e( 'Tax Rate Name Collision', 'woocommerce-germanized' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Make sure, that different tax rates do not have the same names. WooCommerce will then merge these rates within checkout into one line.', 'woocommerce-germanized' ) . '">[?]</a>'; ?></td>
			<td>
				<?php

					global $wpdb;

					$tax_classes    = WC_Tax::get_tax_classes();
					$tax_rate_names = array(); 
					$collisions 	= array();

					foreach( $tax_classes as $class ) {
						
						$rates = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
							WHERE tax_rate_class = %s
							ORDER BY tax_rate_order
							" ,
							sanitize_title( $class )
						) );

						foreach ( $rates as $rate ) {
							if ( ! isset( $tax_rate_names[ $rate->tax_rate_name ] ) )
								$tax_rate_names[ $rate->tax_rate_name ] = $rate;
							else if ( $tax_rate_names[ $rate->tax_rate_name ]->tax_rate_class != $rate->tax_rate_class )
								array_push( $collisions, array( $tax_rate_names[ $rate->tax_rate_name ], $rate ) );

						}

					}

					if ( ! empty( $collisions ) ) {
						$c_count = 0;
						foreach ( $collisions as $collision ) {
							echo ( $c_count++ > 0 ? ' | ' : '' ) . $collision[0]->tax_rate_id . ' (' . $collision[0]->tax_rate_class . ') && ' . $collision[1]->tax_rate_id . ' (' . $collision[1]->tax_rate_class . ')'; 
						} 
					} else {
						echo '<mark class="no">'.'&ndash;'.'</mark>';
					}

				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="VAT Table Check"><?php _e( 'VAT Table Check', 'woocommerce-germanized' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Checks whether all WooCommerce tax relevant tables have been added.', 'woocommerce-germanized' ) . '">[?]</a>'; ?></td>
			<td><?php echo WC_GZD_Admin_Status::tax_tables_exist() ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>' . ' [' .sprintf( __( 'Missing: %s', 'woocommerce-germanized' ), implode( ', ', WC_GZD_Admin_Status::get_missing_tax_tables() ) ) . ']'; ?></td>
		</tr>
		<?php do_action( 'woocommerce_gzd_status_after_vat' ); ?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Legal Pages"><?php _e( 'Legal Pages', 'woocommerce-germanized' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( WC_GZD_Admin_Status::get_legal_pages() as $option => $page ) : ?>

			<tr>
				<td data-export-label="<?php echo $page[ 'title' ]; ?>"><?php echo $page[ 'title' ]; ?></td>
				<td class="help">&nbsp;</td>
				<td><?php echo $page[ 'id' ] && get_post( $page[ 'id' ] ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>' . ( $page[ 'id' ] && ! get_post( $page[ 'id' ] ) ? ' [' . __( 'Page doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ); ?></td>
			</tr>

		<?php endforeach; ?>
		<?php do_action( 'woocommerce_gzd_status_after_legal_pages' ); ?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Templates"><?php _e( 'Templates', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

			$template_paths     = apply_filters( 'woocommerce_gzd_template_overrides_scan_paths', array( 'WooCommerce Germanized' => WC_germanized()->plugin_path() . '/templates/' ) );
			$scanned_files      = array();
			$found_files        = array();
			$outdated_templates = false;

			foreach ( $template_paths as $plugin_name => $template_path ) {
				$scanned_files[ $plugin_name ] = WC_Admin_Status::scan_template_files( $template_path );
			}

			foreach ( $scanned_files as $plugin_name => $files ) {

				$plugin_subfolder = sanitize_title( $plugin_name );

				foreach ( $files as $file ) {

					if ( strpos( $file, '.php' ) === false )
						continue;

					if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/' . $file;
					} elseif ( file_exists( get_stylesheet_directory() . '/' . $plugin_subfolder . '/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/' . $plugin_subfolder . '/' . $file;
					} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
						$theme_file = get_template_directory() . '/' . $file;
					} elseif( file_exists( get_template_directory() . '/' . $plugin_subfolder . '/' . $file ) ) {
						$theme_file = get_template_directory() . '/' . $plugin_subfolder . '/' . $file;
					} else {
						$theme_file = false;
					}

					if ( $theme_file ) {

						$core_version  = WC_Admin_Status::get_file_version( apply_filters( 'woocommerce_gzd_template_overrides_scan_plugin_dir', WC()->plugin_path() . '/templates/' . $file, $plugin_name, $file ) );
						$theme_version = WC_Admin_Status::get_file_version( $theme_file );

						if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
							if ( ! $outdated_templates ) {
								$outdated_templates = true;
							}
							$found_files[ $plugin_name ][] = sprintf( __( '<code>%s</code> version <strong style="color:red">%s</strong> is out of date. The core version is %s', 'woocommerce' ), str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ), $theme_version ? $theme_version : '-', $core_version );
						} else {
							$found_files[ $plugin_name ][] = sprintf( '<code>%s</code>', str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ) );
						}
					}
				}
			}

			if ( $found_files ) {
				foreach ( $found_files as $plugin_name => $found_plugin_files ) {
					?>
					<tr>
						<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?> (<?php echo $plugin_name; ?>):</td>
						<td class="help">&nbsp;</td>
						<td><?php echo implode( ', <br/>', $found_plugin_files ); ?></td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td>&ndash;</td>
				</tr>
				<?php
			}

			if ( true === $outdated_templates ) {
				?>
				<tr>
					<td>&nbsp;</td>
					<td><a href="http://speakinginbytes.com/2014/02/woocommerce-2-1-outdated-templates/" target="_blank"><?php _e( 'Learn how to update outdated templates', 'woocommerce' ) ?></a></td>
				</tr>
				<?php
			}
		?>
		<?php do_action( 'woocommerce_gzd_status_after_templates' ); ?>
	</tbody>
</table>

<script type="text/javascript">

	jQuery( 'a.help_tip' ).click( function() {
		return false;
	});

	jQuery( 'a.debug-report' ).click( function() {

		var report = '';

		jQuery( '#status thead, #status tbody' ).each(function(){

			if ( jQuery( this ).is('thead') ) {

				var label = jQuery( this ).find( 'th:eq(0)' ).data( 'export-label' ) || jQuery( this ).text();
				report = report + "\n### " + jQuery.trim( label ) + " ###\n\n";

			} else {

				jQuery('tr', jQuery( this ) ).each(function(){

					var label       = jQuery( this ).find( 'td:eq(0)' ).data( 'export-label' ) || jQuery( this ).find( 'td:eq(0)' ).text();
					var the_name    = jQuery.trim( label ).replace( /(<([^>]+)>)/ig, '' ); // Remove HTML
					var the_value   = jQuery.trim( jQuery( this ).find( 'td:eq(2)' ).text() );
					var value_array = the_value.split( ', ' );

					if ( value_array.length > 1 ) {

						// If value have a list of plugins ','
						// Split to add new line
						var output = '';
						var temp_line ='';
						jQuery.each( value_array, function( key, line ){
							temp_line = temp_line + line + '\n';
						});

						the_value = temp_line;
					}

					report = report + '' + the_name + ': ' + the_value + "\n";
				});

			}
		});

		try {
			jQuery( "#debug-report" ).slideDown();
			jQuery( "#debug-report textarea" ).val( report ).focus().select();
			jQuery( this ).fadeOut();
			return false;
		} catch( e ){
			console.log( e );
		}

		return false;
	});

	jQuery( document ).ready( function ( $ ) {
		$( '#copy-for-support' ).tipTip({
			'attribute':  'data-tip',
			'activation': 'click',
			'fadeIn':     50,
			'fadeOut':    50,
			'delay':      0
		});

		$( 'body' ).on( 'copy', '#copy-for-support', function ( e ) {
			e.clipboardData.clearData();
			e.clipboardData.setData( 'text/plain', $( '#debug-report textarea' ).val() );
			e.preventDefault();
		});

	});

</script>