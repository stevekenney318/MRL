<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package minimalistblogger-child
 */

?>

</div>
</div><!-- #content -->

<footer id="colophon" class="site-footer clearfix">

	<!-- <div class="content-wrap">
		<?php if ( is_active_sidebar( 'footerwidget-1' ) ) : ?>
		<div class="footer-column-wrapper">
			<div class="footer-column-three footer-column-left">
				<?php dynamic_sidebar( 'footerwidget-1' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footerwidget-2' ) ) : ?>
		<div class="footer-column-three footer-column-middle">
			<?php dynamic_sidebar( 'footerwidget-2' ); ?>
		</div>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footerwidget-3' ) ) : ?>
		<div class="footer-column-three footer-column-right">
			<?php dynamic_sidebar( 'footerwidget-3' ); ?>
		</div>
		<?php endif; ?>

		</div>
	</div> -->

	<div class="site-info">
		<div style="text-align:center; padding: 18px 12px; box-sizing: border-box;">
			<div style="font-size:16pt; line-height:1.2; font-family:'Century Gothic', sans-serif; color:#dfcca8;">
				<div style="margin-bottom:10px;">
					Some older pages may contain links or images that no longer work.
				</div>
				<div>
					Copyright &copy; 2017-<?php echo esc_html( date('Y') ); ?> Manlius Racing League
				</div>
			</div>
		</div>
	</div><!-- .site-info -->

</footer>

</div>

<!-- Off canvas menu overlay, required for mobile menu -->
<div id="smobile-menu" class="mobile-only"></div>
<div id="mobile-menu-overlay"></div>

<?php wp_footer(); ?>
</body>
</html>