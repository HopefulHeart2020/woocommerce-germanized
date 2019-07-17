<?php
/**
 * Email Footer Page Attachment
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

global $post;
$post = $post_attach;

setup_postdata( $post );

$content     = ( get_post_meta( $post->ID, '_legal_text', true ) ? htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) ) : $post->post_content );
$print_title = true;

if ( substr( trim( $content ), 0, 2 ) == '<h' )
	$print_title = false;

?>

<div class="wc-gzd-email-attach-post smaller" id="wc-gzd-email-attach-post-<?php the_id();?>">

	<?php if ( $print_title ) : ?>
		<h4 class="wc-gzd-mail-main-title"><?php the_title();?></h4>
	<?php endif; ?>

	<div class="wc-gzd-email-attached-content">
		<?php if ( ! get_post_meta( $post->ID, '_legal_text', true ) ) : ?>

			<?php the_content(); ?>

		<?php else : ?>
            <?php
                /**
                 * Filter that allows disabling the `the_content` filter for optional legal page content.
                 *
                 * @param bool   $enable Enable or disable the `the_content` filter.
                 * @param string $content The content.
                 */
                $apply_content_filters = apply_filters( 'woocommerce_gzd_apply_optional_content_filter_email_attachment', true, $content );
                $plain_content         = htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) );
                $content               = $apply_content_filters ? apply_filters( 'the_content', $plain_content ) : $plain_content;
            ?>

			<?php echo $content; ?>

		<?php endif; ?>
	</div>
</div>

<?php wp_reset_postdata(); ?>