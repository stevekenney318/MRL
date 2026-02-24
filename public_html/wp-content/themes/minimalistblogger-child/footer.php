<?php
/**
 * The template for displaying the footer
 *
 * @package minimalistblogger-child
 */
?>

<footer id="colophon" class="site-footer">
    <div style="width: 100%; margin: 0 auto; text-align: center; padding: 18px 12px; box-sizing: border-box;">
        <div style="font-size: 16pt; line-height: 1.2; font-family: 'Century Gothic', sans-serif; color: #dfcca8;">
            <div style="margin-bottom: 10px;">
                Some older pages may contain links or images that no longer work.
            </div>

            <div>
                Copyright &copy; 2017-<?php echo esc_html(date('Y')); ?> Manlius Racing League
            </div>
        </div>
    </div>
</footer><!-- #colophon -->

<?php wp_footer(); ?>
</body>
</html>