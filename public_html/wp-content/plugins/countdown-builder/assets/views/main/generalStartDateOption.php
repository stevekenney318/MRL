<?php
	use ycd\AdminHelper;
	$defaultData = AdminHelper::defaultData();
	$allowed_html = AdminHelper::getAllowedTags();
?>
<div class="row">
	<div class="col-md-6">
		<label for="ycd-countdown-enable-start-date" class="ycd-label-of-switch"><?php _e('Enable start date', YCD_TEXT_DOMAIN); ?></label>
	</div>
	<div class="col-md-6">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-countdown-enable-start-date" name="ycd-countdown-enable-start-date" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-countdown-enable-start-date')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-countdown-start-date" class="ycd-label-of-input">
				<?php _e('Date', YCD_TEXT_DOMAIN); ?>
			</label>
		</div>
		<div class="col-md-6">
			<input type="text" id="ycd-countdown-start-date" class="form-control ycd-date-time-picker" name="ycd-countdown-start-date" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-start-date')); ?>">
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-countdown-start-time-zone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
			<div class="ycd-select-wrapper">
				<?php
					$timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-countdown-start-time-zone')), array('name' => 'ycd-countdown-start-time-zone', 'class' => 'js-ycd-select js-circle-time-zone'));
					echo wp_kses($timeZone, $allowed_html);
				?>
			</div>
		</div>
	</div>
</div>