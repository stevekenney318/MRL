<?php
use \ycd\AdminHelper;
$default = \ycd\AdminHelper::defaultData();
?>
<div class="ycd-bootstrap-wrapper">
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-countdown-enable-fixed-position" class="ycd-label-of-switch"><?php _e('Enable fixed position', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
			<label class="ycd-switch">
				<input type="checkbox" id="ycd-countdown-fixed-position" name="ycd-countdown-enable-fixed-position" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-countdown-enable-fixed-position')); ?>>
				<span class="ycd-slider ycd-round"></span>
			</label>
		</div>
	</div>
	<div class="ycd-accordion-content ycd-hide-content">
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-fixed-position" class="ycd-label-of-switch"><?php _e('Position', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<?php echo AdminHelper::selectBox($default['fixed-positions'],$this->getOptionValue('ycd-fixed-position'), array('class' => 'js-ycd-select ycd-fixed-position-val', 'name' => 'ycd-fixed-position')); ?>
			</div>
		</div>
		<div class="row ypm-margin-bottom-15">
			<div class="col-xs-3 ycd-fixed-positions-wrapper ycd-position-wrapper-top ycd-hide">
				<label for="ycd-fixed-positions-top"><?php _e('Top',YCD_TEXT_DOMAIN )?></label>
				<input name="ycd-fixed-positions-top" value="<?php esc_attr_e($this->getOptionValue('ycd-fixed-positions-top'));?>" id="ycd-fixed-positions-top" class="form-control">
			</div>
			<div class="col-xs-3 ycd-fixed-positions-wrapper ycd-position-wrapper-right ycd-hide">
				<label for="ycd-fixed-positions-right"><?php _e('Right',YCD_TEXT_DOMAIN )?></label>
				<input name="ycd-fixed-positions-right" value="<?php esc_attr_e($this->getOptionValue('ycd-fixed-positions-right'));?>" id="ycd-fixed-positions-right" class="form-control">
			</div>
			<div class="col-xs-3 ycd-fixed-positions-wrapper ycd-position-wrapper-bottom ycd-hide">
				<label for="ycd-fixed-positions-bottom"><?php _e('Bottom',YCD_TEXT_DOMAIN )?></label>
				<input name="ycd-fixed-positions-bottom" value="<?php esc_attr_e($this->getOptionValue('ycd-fixed-positions-bottom'));?>" id="ycd-fixed-positions-bottom" class="form-control">
			</div>
			<div class="col-xs-3 ycd-fixed-positions-wrapper ycd-position-wrapper-left ycd-hide">
				<label for="ycd-fixed-positions-left"><?php _e('Left',YCD_TEXT_DOMAIN )?></label>
				<input name="ycd-fixed-positions-left" value="<?php esc_attr_e($this->getOptionValue('ycd-fixed-positions-left'));?>" id="ycd-fixed-positions-left" class="form-control">
			</div>
		</div>
	</div>
    <div class="row">
        <div class="col-md-6">
            <label for="ycd-countdown-end-sound" class="ycd-label-of-switch"><?php _e('Display On', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
            <label class="ycd-switch">
                <input type="checkbox" id="ycd-countdown-display-on" name="ycd-countdown-display-on" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-countdown-display-on')); ?>>
                <span class="ycd-slider ycd-round"></span>
            </label>
        </div>
    </div>
    <div class="ycd-accordion-content ycd-hide-content">
        <?php require_once dirname(__FILE__).'/displaySettings.php'; ?>
    </div>
</div>