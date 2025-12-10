<?php
use ycd\AdminHelper;
use ycd\MultipleChoiceButton;
?>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input"><?php _e('Show countdown', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">

	</div>
</div>
<div class="ycd-sub-options-settings">
	<div class="ycd-multichoice-wrapper">
		<?php
		$multipleChoiceButton = new MultipleChoiceButton($defaultData['sticky_show_condition'], esc_attr($this->getOptionValue('ycd-sticky-show-condition')));
		echo wp_kses($multipleChoiceButton, $allowed_html);
		?>
	</div>
    <div id="ycd-sticky-show-condition-load" class="ycd-sub-option ycd-hide">
        <div class="row form-group">
            <div class="col-md-6">
                <label class="ycd-label-of-input" for="ycd-sticky-scroll-delay"><?php _e('Delay', YCD_TEXT_DOMAIN); ?></label>
            </div>
            <div class="col-md-5">
                <input type="text" name="ycd-sticky-scroll-delay" id="ycd-sticky-scroll-delay" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-scroll-delay')); ?>">
            </div>
            <div class="row">
                <label>Seconds</label>
            </div>
        </div>
    </div>
	<div id="ycd-sticky-show-condition-scroll" class="ycd-sub-option ycd-hide">
		<div class="row form-group">
			<div class="col-md-6">
				<label class="ycd-label-of-input" for="ycd-sticky-scroll-percent"><?php _e('Show after', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-5">
				<input type="text" name="ycd-sticky-scroll-percent" id="ycd-sticky-scroll-percent" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-scroll-percent')); ?>">
			</div>
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input"><?php _e('Sections order', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
		<?php
		$contdownSections = AdminHelper::selectBox($stickySectionsOrder, esc_attr($this->getOptionValue('ycd-sticky-countdown-sections')), array('name' => 'ycd-sticky-countdown-sections', 'class' => 'js-ycd-select'));
		echo wp_kses($contdownSections, $allowed_html);
		?>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-enable-sections"><?php _e('Mobile Sections orders', YCD_TEXT_DOMAIN);  echo wp_kses($proSpan, $allowed_html); ?></label>
	</div>
	<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-enable-sections" class="ycd-accordion-checkbox" name="ycd-sticky-enable-sections" <?php echo esc_attr($this->getOptionValue('ycd-sticky-enable-sections')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input"><?php _e('Sections order', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<?php
			$contdownSections = AdminHelper::selectBox($stickySectionsOrder, esc_attr($this->getOptionValue('ycd-sticky-countdown-mobile-sections')), array('name' => 'ycd-sticky-countdown-mobile-sections', 'class' => 'js-ycd-select'));
			echo wp_kses($contdownSections, $allowed_html);
			?>
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input"><?php _e('Background color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	</div>
	<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
			<input type="text" id="ycd-sticky-text-background-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-sticky-text-background-color" class="minicolors-input form-control js-ycd-sticky-color" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-text-background-color')); ?>">
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-enable-footer"><?php _e('Enable sticky footer', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-enable-footer" name="ycd-sticky-enable-footer" <?php echo esc_attr($this->getOptionValue('ycd-sticky-enable-footer')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-enable-close"><?php _e('Enable close', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-enable-close" class="ycd-accordion-checkbox" name="ycd-sticky-enable-close" <?php echo esc_attr($this->getOptionValue('ycd-sticky-enable-close')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-close-text"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<input type="text" name="ycd-sticky-close-text" id="ycd-sticky-close-text" class="form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-close-text')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-input" for="ycd-sticky-close-position"><?php _e('Close position', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-5">
			<?php
			$closePossition = AdminHelper::selectBox($defaultData['sticky-close-position'], esc_attr($this->getOptionValue('ycd-sticky-close-position')), array('name' => 'ycd-sticky-close-position', 'class' => 'js-ycd-select'));
			echo wp_kses($closePossition, $allowed_html);
			?>
		</div>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-enable-double-digits"><?php _e('Double digits', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-5">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-sticky-enable-double-digits" class="" name="ycd-sticky-enable-double-digits" <?php echo esc_attr($this->getOptionValue('ycd-sticky-enable-double-digits')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>