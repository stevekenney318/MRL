<?php
use ycd\AdminHelper;
$id = 0;
if (!empty($_GET['post'])) {
    $id = $_GET['post'];
}
$key = "send_newslatter_".$id;
$sent = get_option($key);
$newslatters = array();

$isFree = YCD_PKG_VERSION === YCD_FREE_VERSION;
if (!$isFree) {
    global $wpdb;
    $table_name = $wpdb->prefix . YCD_COUNTDOWN_NEWSLETTER_TABLE;
    $table_name = $wpdb->prefix . YCD_COUNTDOWN_NEWSLETTER_TABLE;
    $newslattersData = $wpdb->get_results("SELECT id, title FROM {$table_name} ORDER BY id DESC", ARRAY_A);
    foreach ($newslattersData as $latter) {
        $newslatters[$latter['id']] = esc_html($latter['title']);
    }
}

?>
<div class="ycd-bootstrap-wrapper ycd-not-options-wrapper ycd-full-pro-wrapper">
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-enable-subscribe-form" class="ycd-label-of-input"><?php _e('Enable Subscribe Form', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <label class="ycd-switch">
                <input type="checkbox" id="ycd-enable-subscribe-form" name="ycd-enable-subscribe-form" class="ycd-enable-subscribe-form" <?php echo esc_attr($this->getOptionValue('ycd-enable-subscribe-form')); ?>>
                <span class="ycd-slider ycd-round"></span>
            </label>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-enable-send-newslatter" class="ycd-label-of-input"><?php _e('After Countdown Expiration Send Newslatter', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <label class="ycd-switch">
                <input type="checkbox" id="ycd-enable-send-newslatter" name="ycd-enable-send-newslatter" class="ycd-enable-subscribe-form ycd-accordion-checkbox " <?php echo esc_attr($this->getOptionValue('ycd-enable-send-newslatter')); ?>>
                <span class="ycd-slider ycd-round"></span>
            </label>
        </div>
    </div>
    <div class="ycd-accordion-content ycd-hide-content">
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-enable-send-newslatter" class="ycd-label-of-input"><?php _e('Choose Newslatter', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <?php echo AdminHelper::selectBox($newslatters,$this->getOptionValue('ycd-auto-newslatter'), array('class' => 'js-ycd-select', 'name' => 'ycd-auto-newslatter')); ?>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-enable-send-newslatter" class="ycd-label-of-input"><?php _e('After Expiration send already send newslatter', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <p><?php echo ($sent == 1 ) ? '<span class="ypm-autosent-newslatter">Sent a newslatter</span>': '<span class="ypm-active-newslatter">Currently Active</span>';?></p>
            <button class="btn reset-sent-newslatter" data-id="<?php echo esc_attr($id); ?>" <?php echo empty($sent)? 'disabled': ''?>>Reset</button>
        </div>
    </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-subscribe-width" class="ycd-label-of-input"><?php _e('Subscribe Form Width', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-subscribe-width" class="form-control" id="ycd-subscribe-width" value="<?php echo esc_attr($this->getOptionValue('ycd-subscribe-width')); ?>">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-form-above-text" class="ycd-label-of-input"><?php _e('Form Above Text', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-form-above-text" class="form-control" id="ycd-form-above-text" value="<?php echo esc_attr($this->getOptionValue('ycd-form-above-text')); ?>">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-form-input-text" class="ycd-label-of-input"><?php _e('Form Input Text', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-form-input-text" class="form-control" id="ycd-form-input-text" value="<?php echo esc_attr($this->getOptionValue('ycd-form-input-text')); ?>">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-form-submit-text" class="ycd-label-of-input"><?php _e('Form Submit Text', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-form-submit-text" class="form-control" id="ycd-form-submit-text" value="<?php echo esc_attr($this->getOptionValue('ycd-form-submit-text')); ?>">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-form-submit-color" class="ycd-label-of-input"><?php _e('Submit Button Color', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
                <input type="text" id="ycd-form-submit-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-form-submit-color" class=" minicolors-input form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-form-submit-color')); ?>">
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-subscribe-success-message" class="ycd-label-of-input"><?php _e('Thank You Message', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-subscribe-success-message" class="form-control" id="ycd-subscribe-success-message" value="<?php echo esc_attr($this->getOptionValue('ycd-subscribe-success-message'))?>">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-subscribe-error-message" class="ycd-label-of-input"><?php _e('Error Message', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <input type="text" name="ycd-subscribe-error-message" class="form-control" id="ycd-subscribe-error-message" value="<?php echo esc_attr($this->getOptionValue('ycd-subscribe-error-message'))?>">
        </div>
    </div>
	<?php
		$allowed_html = AdminHelper::getAllowedTags();
		echo wp_kses(AdminHelper::upgradeButton(), $allowed_html);
	?>
</div>