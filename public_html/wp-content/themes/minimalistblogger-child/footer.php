<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package minimalistblogger
 */

?>

<div class="site-info">
	<?php echo esc_html('&copy;', 'minimalistblogger') ?> <?php echo "2017-"; ?> <?php echo esc_html(date('Y')); ?> <?php bloginfo( 'name' ); ?>
	<!-- Delete below lines to remove copyright from footer -->
	
	<!-- Delete above lines to remove copyright from footer -->

</div><!-- .site-info -->
</div>



</footer>
</div>
<!-- Off canvas menu overlay, delete to remove dark shadow -->
<div id="smobile-menu" class="mobile-only"></div>
<div id="mobile-menu-overlay"></div>

<?php wp_footer(); ?>
<?php echo esc_html('&copy;', 'minimalistblogger') ?> <?php echo "2017-"; ?> <?php echo esc_html(date('Y')); ?> <?php bloginfo( 'name' ); ?>
</body>
</html>
