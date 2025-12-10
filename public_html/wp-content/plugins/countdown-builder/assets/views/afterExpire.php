<?php
use ycd\MultipleChoiceButton;
use ycd\AdminHelper;

$defaultData = AdminHelper::defaultData();
$allowed_html = AdminHelper::getAllowedTags();
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
<div class="ycd-bootstrap-wrapper">
	<div class="row form-group">
		<label class="col-md-8 control-label ycd-static-padding-top">
			<?php _e('After Countdown Expire', YCD_TEXT_DOMAIN) ?>:
		</label>
		<div class="col-md-7">
		</div>
	</div>
	<div class="ycd-multichoice-wrapper">
	<?php
		$multipleChoiceButton = new MultipleChoiceButton($defaultData['countdownExpireTime'], esc_attr($this->getOptionValue('ycd-countdown-expire-behavior')));
		echo wp_kses($multipleChoiceButton, $allowed_html);
	?>
	</div>
	<div id="ycd-countdown-show-text" class="ycd-countdown-show-text ycd-hide">
		<div>
			<div class="col-md-12">
				<label class="ycd-sub-label"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div>
				<?php
				$editorId = 'ycd-expire-text';
				$settings = array(
					'wpautop' => false,
					'tinymce' => array(
						'width' => '100%'
					),
					'textarea_rows' => '6',
					'media_buttons' => true
				);
				wp_editor(esc_html($this->getOptionValue('ycd-expire-text')), $editorId, $settings);
				?>
			</div>
		</div>
	</div>
    <div id="ycd-countdown-countUp-behavior" class="ycd-countdown-show-text ycd-hide">
        <div class="row">
            <div class="col-md-6 ycd-sub-option">
                <label for="ycd-count-up-from-end-date" class="ycd-label-of-input"><?php _e('Count up from End Date', YCD_TEXT_DOMAIN); ?></label>
            </div>
            <div class="col-md-6">
                <input type="checkbox" id="ycd-count-up-from-end-date" name="ycd-count-up-from-end-date" <?php echo esc_attr($this->getOptionValue('ycd-count-up-from-end-date')); ?>>
            </div>
        </div>
    </div>

	<div id="ycd-countdown-redirect-url" class="ycd-countdown-show-text ycd-hide">
		<div class="row">
			<div class="col-md-6 ycd-sub-option">
				<label for="ycd-expire-url" class="ycd-label-of-input"><?php _e('URL', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<input type="url" placeholder="https://www.example.com" class="form-control" id="ycd-expire-url" name="ycd-expire-url" value="<?php echo esc_url($this->getOptionValue('ycd-expire-url')); ?>">
			</div>
		</div>
	</div>
</div>