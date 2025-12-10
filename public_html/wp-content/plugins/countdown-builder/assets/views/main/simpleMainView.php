<?php
$type = $this->getCurrentTypeFromOptions();
use ycd\AdminHelper;
$proSpan = '';
$isPro = '';
if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('pro', YCD_TEXT_DOMAIN).'</span>';
}
$defaultData = AdminHelper::defaultData();
$allowed_html = AdminHelper::getAllowedTags();

?>
<div class="ycd-bootstrap-wrapper">
    <!-- Labels start  -->
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-countdown-timer-days" class="ycd-label-of-switch"><?php _e('Labels', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
	        <!--  Years section start -->
	        <div class="row form-group">
		        <div class="col-md-6">
			        <label for="ycd-simple-enable-years"><?php _e('Years', YCD_TEXT_DOMAIN); ?></label>
		        </div>
		        <div class="col-md-6">
			        <label class="ycd-switch">
				        <input type="checkbox" id="ycd-simple-enable-years" data-time-type="years" name="ycd-simple-enable-years" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-years')); ?>>
				        <span class="ycd-slider ycd-round"></span>
			        </label>
		        </div>
	        </div>
	        <div class="ycd-accordion-content ycd-hide-content">
		        <div class="row form-group">
			        <div class="col-md-6">
				        <label for="ycd-simple-years-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
			        </div>
			        <div class="col-md-6">
				        <input type="text" id="ycd-simple-years-text" class="form-control ycd-simple-text" data-time-type="years" name="ycd-simple-years-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-years-text')); ?>" >
			        </div>
		        </div>
	        </div>
	        <!--  Years section end -->
        </div>
        <div class="col-md-6">
	        <!--  Mounts section start -->
	        <div class="row form-group">
		        <div class="col-md-6">
			        <label for="ycd-simple-enable-months"><?php _e('Months', YCD_TEXT_DOMAIN); ?></label>
		        </div>
		        <div class="col-md-6">
			        <label class="ycd-switch">
				        <input type="checkbox" id="ycd-simple-enable-months" data-time-type="months" name="ycd-simple-enable-months" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-months')); ?>>
				        <span class="ycd-slider ycd-round"></span>
			        </label>
		        </div>
	        </div>
	        <div class="ycd-accordion-content ycd-hide-content">
		        <div class="row form-group">
			        <div class="col-md-6">
				        <label for="ycd-simple-months-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
			        </div>
			        <div class="col-md-6">
				        <input type="text" id="ycd-simple-months-text" class="form-control ycd-simple-text" data-time-type="months" name="ycd-simple-months-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-months-text')); ?>" >
			        </div>
		        </div>
	        </div>
	        <!--  Mounts section end -->
        </div>
    </div>
	<div class="row form-group">
		<div class="col-md-6">
			<!--  Days section start -->
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-enable-days"><?php _e('Days', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<label class="ycd-switch">
						<input type="checkbox" id="ycd-simple-enable-days" data-time-type="days" name="ycd-simple-enable-days" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-days')); ?>>
						<span class="ycd-slider ycd-round"></span>
					</label>
				</div>
			</div>
			<div class="ycd-accordion-content ycd-hide-content">
				<div class="row form-group">
					<div class="col-md-6">
						<label for="ycd-simple-days-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
					</div>
					<div class="col-md-6">
						<input type="text" id="ycd-simple-days-text" class="form-control ycd-simple-text" data-time-type="days" name="ycd-simple-days-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-days-text')); ?>" >
					</div>
				</div>
			</div>
			<!--  Days section end -->
		</div>
		<div class="col-md-6">
			<!--  Hours section start -->
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-enable-hours"><?php _e('Hours', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<label class="ycd-switch">
						<input type="checkbox" id="ycd-simple-enable-hours" data-time-type="hours" name="ycd-simple-enable-hours" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-hours')); ?>>
						<span class="ycd-slider ycd-round"></span>
					</label>
				</div>
			</div>
			<div class="ycd-accordion-content ycd-hide-content">
				<div class="row form-group">
					<div class="col-md-6">
						<label for="ycd-simple-hours-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
					</div>
					<div class="col-md-6">
						<input type="text" id="ycd-simple-hours-text" class="form-control ycd-simple-text" data-time-type="hours" name="ycd-simple-hours-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-hours-text')); ?>" >
					</div>
				</div>
			</div>
			<!--  Hours section end -->
		</div>
	</div>
    <div class="row form-group">
        <div class="col-md-6">
            <!--  Minutes section start -->
            <div class="row form-group">
                <div class="col-md-6">
                    <label for="ycd-simple-enable-minutes"><?php _e('Minutes', YCD_TEXT_DOMAIN); ?></label>
                </div>
                <div class="col-md-6">
                    <label class="ycd-switch">
                        <input type="checkbox" id="ycd-simple-enable-minutes" data-time-type="minutes" name="ycd-simple-enable-minutes" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-minutes')); ?>>
                        <span class="ycd-slider ycd-round"></span>
                    </label>
                </div>
            </div>
            <div class="ycd-accordion-content ycd-hide-content">
                <div class="row form-group">
                    <div class="col-md-6">
                        <label for="ycd-simple-minutes-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="ycd-simple-minutes-text" class="form-control ycd-simple-text" data-time-type="minutes" name="ycd-simple-minutes-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-minutes-text')); ?>" >
                    </div>
                </div>
            </div>
            <!--  Minutes section end -->
        </div>
        <div class="col-md-6">
            <!--  Seconds section start -->
            <div class="row form-group">
                <div class="col-md-6">
                    <label for="ycd-simple-enable-seconds"><?php _e('Seconds', YCD_TEXT_DOMAIN); ?></label>
                </div>
                <div class="col-md-6">
                    <label class="ycd-switch">
                        <input type="checkbox" id="ycd-simple-enable-seconds" data-time-type="seconds" name="ycd-simple-enable-seconds" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-seconds')); ?>>
                        <span class="ycd-slider ycd-round"></span>
                    </label>
                </div>
            </div>
            <div class="ycd-accordion-content ycd-hide-content">
                <div class="row form-group">
                    <div class="col-md-6">
                        <label for="ycd-simple-seconds-text"><?php _e('label', YCD_TEXT_DOMAIN); ?></label>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="ycd-simple-seconds-text" class="form-control ycd-simple-text" data-time-type="seconds" name="ycd-simple-seconds-text" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-seconds-text')); ?>" >
                    </div>
                </div>
            </div>
            <!--  Seconds section end -->
        </div>
    </div>
    <!-- Labels end  -->
    <!-- Styles start -->
    <div class="row form-group">
        <div class="col-md-6">
            <label class="ycd-label-of-switch"><?php _e('Styles', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
        </div>
    </div>
	<div class="row form-group">
		<div class="col-md-6">
			<label for="ycd-simple-numbers-font-size"><?php _e('Numbers', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
		</div>
	</div>

	<div class="ycd-sub-option-wrapper">
		<!-- Numbers Styles start -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-simple-numbers-font-size"><?php _e('font size', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<input type="text" id="ycd-simple-numbers-font-size" class="form-control ycd-simple-font-size" data-field-type="number" name="ycd-simple-numbers-font-size" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-font-size')); ?>" >
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-simple-numbers-font-family" class="ycd-label-of-select"><?php _e('font family', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);?></label>
			</div>
			<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<?php 
					$fontFamily = AdminHelper::selectBox($defaultData['font-family'], esc_attr($typeObj->getOptionValue('ycd-simple-numbers-font-family')), array('name' => 'ycd-simple-numbers-font-family', 'class' => 'js-ycd-select js-simple-font-family ycd-custom-value-accordion', 'data-field-type' => 'number', 'data-custom' => 'customFont'));
					echo wp_kses($fontFamily, $allowed_html);
				?>
			</div>
		</div>
        <div class="ycd-accordion-content ycd-hide-content">
            <div class="row">
                <div class="col-xs-6">
                    <label class="control-label" for="ycd-simple-numbers-font-family-custom"><?php _e('custom font family', YCD_TEXT_DOMAIN);?>:</label>
                </div>
                <div class="col-xs-6">
                    <input type="text" id="ycd-simple-numbers-font-family-custom" class="form-control input-md custom-font-family" name="ycd-simple-numbers-font-family-custom" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-font-family-custom'))?>" placeholder="Custom Font Family"><br>
                </div>
            </div>
        </div>
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-simple-numbers-color" class=""><?php _e('color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
			</div>
			<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
					<input type="text" id="ycd-simple-numbers-color" data-time-type="number" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-simple-numbers-color" class=" minicolors-input form-control js-ycd-simple-time-color" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-color')); ?>">
				</div>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-4">
				<label for="ycd-simple-text-margin" class=""><?php _e('margin', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-numbers-margin-top" class="yrm-label">Top</label>
				<input type="text" id="ycd-simple-numbers-margin-top" data-direction="top" name="ycd-simple-numbers-margin-top" class="form-control ycd-numbers-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-margin-top'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-numbers-margin-right" class="yrm-label">Right</label>
				<input type="text" id="ycd-simple-numbers-margin-right" data-direction="right" name="ycd-simple-numbers-margin-right" class="form-control ycd-numbers-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-margin-right'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-text-margin-bottom" class="yrm-label">Bottom</label>
				<input type="text" id="ycd-simple-numbers-margin-bottom" data-direction="bottom" name="ycd-simple-numbers-margin-bottom" class="form-control ycd-numbers-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-margin-bottom'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-numbers-margin-left" class="yrm-label">Left</label>
				<input type="text" id="ycd-simple-numbers-margin-left" data-direction="left" name="ycd-simple-numbers-margin-left" class="form-control ycd-numbers-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-numbers-margin-left'))?>">
			</div>
		</div>
		<!-- Numbers Styles end -->
	</div>
    <div class="row form-group">
        <div class="col-md-6">
            <label for="ycd-simple-text-font-size"><?php _e('Text', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-6">
        </div>
    </div>
	<div class="ycd-sub-option-wrapper">
		<!-- Text Styles start -->
		<div class="row form-group">
	        <div class="col-md-6">
	            <label for="ycd-simple-text-font-size"><?php _e('font size', YCD_TEXT_DOMAIN); ?></label>
	        </div>
	        <div class="col-md-6">
	            <input type="text" id="ycd-simple-text-font-size" class="form-control ycd-simple-font-size" data-field-type="label" name="ycd-simple-text-font-size" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-font-size')); ?>" >
	        </div>
	    </div>
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-countdown-text-size" class="ycd-label-of-select"><?php _e('font family', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<?php 
					$fontFamily = AdminHelper::selectBox($defaultData['font-family'], esc_attr($typeObj->getOptionValue('ycd-simple-text-font-family')), array('name' => 'ycd-simple-text-font-family', 'class' => 'js-ycd-select js-simple-font-family  ycd-custom-value-accordion', 'data-field-type' => 'label', 'data-custom' => 'customFont'));
					echo wp_kses($fontFamily, $allowed_html);
				?>
			</div>
		</div>
        <div class="ycd-accordion-content ycd-hide-content">
            <div class="row">
                <div class="col-xs-6">
                    <label class="control-label" for="ycd-simple-text-font-family-custom"><?php _e('custom font family', YCD_TEXT_DOMAIN);?>:</label>
                </div>
                <div class="col-xs-6">
                    <input type="text" id="ycd-simple-text-font-family-custom" class="form-control input-md custom-font-family" name="ycd-simple-text-font-family-custom" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-font-family-custom'))?>" placeholder="Custom Font Family"><br>
                </div>
            </div>
        </div>
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-simple-text-color" class=""><?php _e('color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
					<input type="text" id="ycd-simple-text-color" data-time-type="label" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-simple-text-color" class=" minicolors-input form-control js-ycd-simple-time-color" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-color')); ?>">
				</div>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-4">
				<label for="ycd-simple-text-margin" class=""><?php _e('margin', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-text-margin-top" class="yrm-label">Top</label>
				<input type="text" id="ycd-simple-text-margin-top" data-direction="top" name="ycd-simple-text-margin-top" class="form-control ycd-text-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-margin-top'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-text-margin-right" class="yrm-label">Right</label>
				<input type="text" id="ycd-simple-text-margin-right" data-direction="right" name="ycd-simple-text-margin-right" class="form-control ycd-text-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-margin-right'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-text-margin-bottom" class="yrm-label">Bottom</label>
				<input type="text" id="ycd-simple-text-margin-bottom" data-direction="bottom" name="ycd-simple-text-margin-bottom" class="form-control ycd-text-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-margin-bottom'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-text-margin-left" class="yrm-label">Left</label>
				<input type="text" id="ycd-simple-text-margin-left" data-direction="left" name="ycd-simple-text-margin-left" class="form-control ycd-text-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-text-margin-left'))?>">
			</div>
		</div>
		<!-- Text Styles end -->
	</div>
    <!-- Styles end -->
	<div class="row form-group">
		<div class="col-md-6">
			<label class="ycd-label-of-switch"><?php _e('Settings', YCD_TEXT_DOMAIN); ?></label>
		</div>
		<div class="col-md-6">
		</div>
	</div>
	<div class="ycd-sub-option-wrapper">
		<div class="row form-group">
			<div class="col-md-4">
				<label for="ycd-simple-unite-margin" class=""><?php _e('Unites padding', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-padding-top" class="yrm-label">Top</label>
				<input type="text" id="ycd-simple-unite-padding-top" data-direction="top" name="ycd-simple-unite-padding-top" class="form-control ycd-unite-padding" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-padding-top'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-padding-right" class="yrm-label">Right</label>
				<input type="text" id="ycd-simple-unite-padding-right" data-direction="right" name="ycd-simple-unite-padding-right" class="form-control ycd-unite-padding" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-padding-right'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-padding-bottom" class="yrm-label">Bottom</label>
				<input type="text" id="ycd-simple-unite-padding-bottom" data-direction="bottom" name="ycd-simple-unite-padding-bottom" class="form-control ycd-unite-padding" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-padding-bottom'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-padding-left" class="yrm-label">Left</label>
				<input type="text" id="ycd-simple-unite-padding-left" data-direction="left" name="ycd-simple-unite-padding-left" class="form-control ycd-unite-padding" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-padding-left'))?>">
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-4">
				<label for="ycd-simple-unite-margin" class=""><?php _e('Unites margin', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-margin-top" class="yrm-label">Top</label>
				<input type="text" id="ycd-simple-unite-margin-top" data-direction="top" name="ycd-simple-unite-margin-top" class="form-control ycd-unite-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-margin-top'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-margin-right" class="yrm-label">Right</label>
				<input type="text" id="ycd-simple-unite-margin-right" data-direction="right" name="ycd-simple-unite-margin-right" class="form-control ycd-unite-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-margin-right'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-margin-bottom" class="yrm-label">Bottom</label>
				<input type="text" id="ycd-simple-unite-margin-bottom" data-direction="bottom" name="ycd-simple-unite-margin-bottom" class="form-control ycd-unite-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-margin-bottom'))?>">
			</div>
			<div class="col-md-2 ycd-option-wrapper">
				<label for="ycd-simple-unite-margin-left" class="yrm-label">Left</label>
				<input type="text" id="ycd-simple-unite-margin-left" data-direction="left" name="ycd-simple-unite-margin-left" class="form-control ycd-unite-margin" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-margin-left'))?>">
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<label class="ycd-label-of-switch" for="ycd-simple-enable-unite-border"><?php _e('Enable Unite Border', YCD_TEXT_DOMAIN); ?><?php echo wp_kses(ycd\ycd_info('If you enable this option we recommend you from the Timer dotes option choose without the dotes option'), $allowed_html)?></label>
			</div>
			<div class="col-md-6">
				<label class="ycd-switch">
					<input type="checkbox" class="ycd-accordion-checkbox" id="ycd-simple-enable-unite-border" class="ycd-label-of-switch" name="ycd-simple-enable-unite-border" <?php echo esc_attr($typeObj->getOptionValue('ycd-simple-enable-unite-border')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-unite-width"><?php _e('Width', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<input type="text" id="ycd-simple-unite-width" class="form-control ycd-simple-border-width" name="ycd-simple-unite-width" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-width')); ?>" >
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-unite-border-width"><?php _e('border width', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<input type="text" id="ycd-simple-unite-border-width" class="form-control ycd-simple-border-width" name="ycd-simple-unite-border-width" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-border-width')); ?>" >
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-unite-border-radius"><?php _e('border radius', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<input type="text" id="ycd-simple-unite-border-radius" class="form-control ycd-simple-border-radius" name="ycd-simple-unite-border-radius" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-border-radius')); ?>" >
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-unite-border-type"><?php _e('border type', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<?php
						$borderType = AdminHelper::selectBox($defaultData['borderTypes'], esc_attr($typeObj->getOptionValue('ycd-simple-unite-border-type')), array('name' => 'ycd-simple-unite-border-type', 'class' => 'js-ycd-select ycd-simple-unite-border-type  ycd-custom-value-accordion', 'data-custom' => 'customFont'));
						echo wp_kses($borderType, $allowed_html);
					?>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-simple-unite-border-color" class=""><?php _e('color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
				</div>
				<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
					<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
						<input type="text" id="ycd-simple-unite-border-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-simple-unite-border-color" class=" form-control" value="<?php echo esc_attr($typeObj->getOptionValue('ycd-simple-unite-border-color')); ?>">
					</div>
				</div>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-6">
				<label class="ycd-label-of-switch" for="enable-double-digits"><?php _e('Double Digits', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<label class="ycd-switch">
					<input type="checkbox" id="enable-double-digits" data-time-type="hours" name="ycd-enable-simple-double-digits" class="ycd-accordion-checkbox" <?php echo esc_attr($typeObj->getOptionValue('ycd-enable-simple-double-digits')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
			<div class="col-md-6">
				<label class="ycd-label-of-switch" for="text-to-top"><?php _e('Text to top', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<label class="ycd-switch">
					<input type="checkbox" id="text-to-top" name="ycd-text-to-top" class="ycd-accordion-checkbox" <?php echo esc_attr($typeObj->getOptionValue('ycd-text-to-top')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-6">
				<label class="ycd-label-of-switch"><?php _e('Timer dotes', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<?php
				$fontFamily = AdminHelper::selectBox(array(':' => ':', '-' => '-', ',' => ',', ';' => ';', '' => 'Widthout dotes'), esc_attr($typeObj->getOptionValue('ycd-simple-timer-dotes')), array('name' => 'ycd-simple-timer-dotes', 'class' => 'js-ycd-select js-simple-timer-dotes  ycd-custom-value-accordion', 'data-field-type' => 'label', 'data-custom' => 'customFont'));
				echo wp_kses($fontFamily, $allowed_html);
				?>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-md-6">
				<label class="ycd-label-of-switch"><?php _e('Horizontal align', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-6">
				<?php
					$fontFamily = AdminHelper::selectBox($defaultData['horizontal-alignment'], esc_attr($typeObj->getOptionValue('ycd-simple-timer-horizontal-align')), array('name' => 'ycd-simple-timer-horizontal-align', 'class' => 'js-ycd-select js-simple-timer-align ycd-custom-value-accordion', 'data-field-type' => 'label', 'data-custom' => 'customFont'));
					echo wp_kses($fontFamily, $allowed_html);
				?>
			</div>
		</div>

	</div>
    <div class="row form-group">
        <div class="col-md-6">
            <label class="ycd-label-of-input" for="ycd-circle-countdown-before-countdown"><?php _e('Before countdown', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-12">
            <?php
            $editorId = 'ycd-simple-countdown-before-countdown';
            $beforeCountdown = $this->getOptionValue($editorId);
            $settings = array(
                'wpautop' => false,
                'tinymce' => array(
                    'width' => '100%'
                ),
                'textarea_rows' => '6',
                'media_buttons' => true
            );
            wp_editor($beforeCountdown, $editorId, $settings);
            ?>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-6">
            <label class="ycd-label-of-input" for="ycd-simple-countdown-after-countdown"><?php _e('After countdown', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <div class="col-md-12">
            <?php
            $editorId = 'ycd-simple-countdown-after-countdown';
            $afterCountdown = $this->getOptionValue($editorId);
            $settings = array(
                'wpautop' => false,
                'tinymce' => array(
                    'width' => '100%'
                ),
                'textarea_rows' => '6',
                'media_buttons' => true
            );
            wp_editor($afterCountdown, $editorId, $settings);
            ?>
        </div>
    </div>
	<div class="row">
		<div class="col-md-12">
			<h5><?php _e('Translations(Synchronized with the browser language)', YCD_TEXT_DOMAIN)?></h5>
			<?php
			require_once (dirname(__FILE__).'/../translations/circleTranslation.php');
			?>
		</div>
	</div>
    <?php
        require_once YCD_VIEWS_PATH.'preview.php';
    ?>
</div>
<input type="hidden" name="ycd-type" value="<?php echo esc_attr($type); ?>">