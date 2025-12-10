<?php
use ycd\MultipleChoiceButton;
?>

<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-button-text"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
		<input type="text" class="form-control" id="ycd-sticky-button-text" name="ycd-sticky-button-text" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-text')); ?>">
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-button-bg-color"><?php _e('Background color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	</div>
	<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
			<input type="text" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-sticky-bg-color" class="minicolors-input form-control js-ycd-sticky-bg-color" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-bg-color')); ?>">
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-button-color"><?php _e('Text color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	</div>
	<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
			<input type="text" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-sticky-button-color" class="minicolors-input form-control js-ycd-sticky-color" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-color')); ?>">
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-button-color"><?php _e('Button click behavior', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
	</div>
</div>

<div class="ycd-sub-option">
	<div class="ycd-multichoice-wrapper">
		<?php
		$multipleChoiceButton = new MultipleChoiceButton($stickyExpiration, esc_attr($this->getOptionValue('ycd-sticky-expire-behavior')));
		echo wp_kses($multipleChoiceButton, $allowed_html);
		?>
	</div>
</div>
<div id="ycd-sticky-expire-redirect-url" class="ycd-countdown-show-text ycd-sub-option ycd-hide">
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-url"><?php _e('URL', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<input type="url" placeholder="https://www.example.com" name="ycd-sticky-url" id="ycd-sticky-url" class="form-control" value="<?php echo esc_url($this->getOptionValue('ycd-sticky-url')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-sticky-button-redirect-new-tab" class="ycd-label-of-switch"><?php _e('Redirect to new tab', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
			<label class="ycd-switch">
				<input type="checkbox" id="ycd-sticky-button-redirect-new-tab" class="" name="ycd-sticky-button-redirect-new-tab" <?php echo esc_attr($this->getOptionValue('ycd-sticky-button-redirect-new-tab')); ?>>
				<span class="ycd-slider ycd-round"></span>
			</label>
		</div>
	</div>
</div>
<div id="ycd-sticky-expire-copy" class="ycd-countdown-show-text ycd-sub-option ycd-hide">
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-sticky-button-copy" class="ycd-label-of-switch"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
			<input type="text" name="ycd-sticky-button-copy" class="form-control" placeholder="<?php  _e('Copy to clipboard'); ?>" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-copy')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-sticky-button-copy" class="ycd-label-of-switch"><?php _e('Show alert', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
			<label class="ycd-switch">
				<input type="checkbox" id="ycd-sticky-copy-alert" name="ycd-sticky-copy-alert" class="ycd-accordion-checkbox" <?php echo esc_attr($typeObj->getOptionValue('ycd-sticky-copy-alert')); ?>>
				<span class="ycd-slider ycd-round"></span>
			</label>
		</div>
	</div>
	<div class="ycd-accordion-content ycd-hide-content">
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-sticky-alert-text" class="ycd-label-of-input"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<input type="text" placeholder="<?php _e('Alert text'); ?>" class="form-control" id="ycd-sticky-alert-text" name="ycd-sticky-alert-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-sticky-alert-text')); ?>">
			</div>
		</div>
	</div>
</div>

<div class="row form-group">
	<div class="col-md-6">
		<label for="ycd-sticky-button-padding-enable" class="ycd-label-of-switch"><?php _e('Enable padding', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-6">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-button-padding-enable" class="ycd-accordion-checkbox" name="ycd-sticky-button-padding-enable" <?php echo esc_attr($this->getOptionValue('ycd-sticky-button-padding-enable')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-button-padding"><?php _e('Padding', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<input type="text" name="ycd-sticky-button-padding" id="ycd-sticky-button-padding" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-padding')); ?>">
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<label for="ycd-sticky-button-border-enable" class="ycd-label-of-switch"><?php _e('Enable border', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-6">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-button-border-enable" class="ycd-sticky-button-border-enable ycd-accordion-checkbox" name="ycd-sticky-button-border-enable" <?php echo esc_attr($this->getOptionValue('ycd-sticky-button-border-enable')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-button-border-width"><?php _e('width', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<input type="text" name="ycd-sticky-button-border-width" id="ycd-sticky-button-border-width" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-border-width')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-button-border-radius"><?php _e('radius', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<input type="text" name="ycd-sticky-button-border-radius" id="ycd-sticky-button-border-radius" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-border-radius')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-button-border-color"><?php _e('Background color', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
			<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
				<input type="text" id="ycd-sticky-button-border-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-sticky-button-border-color" class="minicolors-input form-control js-ycd-sticky-color" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-button-border-color')); ?>">
			</div>
		</div>
	</div>
</div>