<?php
use ycd\AdminHelper;
?>
<div class="ycd-bootstrap-wrapper ycd-not-options-wrapper">
	<?php //require_once YCD_ADMIN_VIEWS_PATH.'demo.php'; ?>
    <div class="row">
        <div class="col-md-12">
            <label>Show Options</label>
        </div>
        <div class="col-md-12">
            <?php echo wp_kses(AdminHelper::selectBox(
                array(
                    'all' => 'All', 
                    'showFreeOptions' => 'Show free options', 
                    'showProOptions' => 'Show pro options'),
                    '',
                    array('class' => 'js-ycd-select js-features-select')
                ), AdminHelper::getAllowedTags())?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <label>
		        <?php _e('Shortcode', YCD_TEXT_DOMAIN); ?>
            </label>
	        <?php
	        $postId = (int)@$_GET['post'];
	        if (empty($postId)) : ?>
                <p>Please do save the Countdown, to getting the shortcode.</p>
	        <?php else: ?>
                <div class="ycd-tooltip">
                    <span class="ycd-tooltiptext" id="ycd-tooltip-<?php echo esc_attr($postId); ?>">Copy to clipboard</span><input type="text" data-id="<?php echo esc_attr($postId); ?>" onfocus="this.select();" readonly="" id="ycd-shortcode-input-<?php echo esc_attr($postId); ?>" value="[ycd_countdown id=<?php echo esc_attr($postId); ?>]" class="large-text code countdown-shortcode"></div>
	        <?php endif; ?>
            <br>
            <label>
		        <?php _e('Current version', YCD_TEXT_DOMAIN); ?>
            </label>
            <p class="current-version-text" style="color: #3474ff;"><?php echo esc_attr(YCD_VERSION_TEXT); ?></p>
            <label>
		        <?php _e('Last update date', YCD_TEXT_DOMAIN); ?>
            </label>
            <p style="color: #11ca79;"><?php echo esc_attr(YCD_LAST_UPDATE); ?></p>
            <label>
		        <?php _e('Next update date', YCD_TEXT_DOMAIN); ?>
            </label>
            <p style="color: #efc150;"><?php echo esc_attr(YCD_NEXT_UPDATE); ?></p>
        </div>
    </div>
</div>