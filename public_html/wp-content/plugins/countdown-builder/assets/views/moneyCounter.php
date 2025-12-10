<?php
// https://chatgpt.com/c/67dec4aa-e360-8007-a4e0-d7d5b794a51c
use ycd\AdminHelper;
$allowed_html = AdminHelper::getAllowedTags();
$proSpan = '';
$isPro = '';
if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('pro', YCD_TEXT_DOMAIN).'</span>';
}
?>

<div class="ycd-bootstrap-wrapper">
    <h2>Money Counter Settings</h2>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-initial"><?php _e('Starting Value', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <input type="number" id="ycd-money-initial" name="ycd-money-initial" value="<?php echo esc_attr($this->getOptionValue('ycd-money-initial')); ?>" step="0.01" class="form-control" />
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-increase-unite"><?php _e('Increase Per Second', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <input type="number" id="ycd-money-increase-unite" name="ycd-money-increase-unite" value="<?php echo esc_attr($this->getOptionValue('ycd-money-increase-unite')); ?>" step="0.01" class="form-control" />
        </div>
    </div>
    <div class="row form-group">
		<div class="col-md-5">
			<label for="ycd-money-start-date" class="ycd-label-of-input">
				<?php _e('Date', YCD_TEXT_DOMAIN); ?>
			</label>
		</div>
		<div class="col-md-5">
			<input type="text" id="ycd-money-start-date" class="form-control ycd-money-time-picker" name="ycd-money-start-date" value="<?php echo esc_attr($this->getOptionValue('ycd-money-start-date')); ?>">
		</div>
	</div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-prefix"><?php _e('Perfix', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <input type="text" id="ycd-money-prefix" name="ycd-money-prefix" value="<?php echo esc_attr($this->getOptionValue('ycd-money-prefix')); ?>" class="form-control" />
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-decimal-places"><?php _e('Decimal Places', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <?php echo AdminHelper::selectBox(array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4),$this->getOptionValue('ycd-money-decimal-places'), array('class' => 'js-ycd-select', 'name' => 'ycd-money-decimal-places', 'id' => 'ycd-money-decimal-places')); ?>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-target-value"><?php _e('Target Value(Optional)', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <input type="number" id="ycd-money-target-value" name="ycd-money-target-value" value="<?php echo esc_attr($this->getOptionValue('ycd-money-target-value')); ?>" class="form-control" />
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-font-size"><?php _e('Font Size', YCD_TEXT_DOMAIN)?></label>
        </div>
        <div class="col-md-5">
            <input type="text" id="ycd-money-font-size" name="ycd-money-font-size" value="<?php echo esc_attr($this->getOptionValue('ycd-money-font-size')); ?>" class="form-control" />
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-color" class="ycd-label-of-input"><?php _e('Color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);?></label>
        </div>
        <div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
            <div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
                <input type="text" id="ycd-money-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-money-color" class="minicolors-input form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-money-color')); ?>">
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-5">
            <label for="ycd-money-bg-color" class="ycd-label-of-input"><?php _e('Background Color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);?></label>
        </div>
        <div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
            <div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
                <input type="text" id="ycd-money-bg-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-money-bg-color" class="minicolors-input form-control" value="<?php echo esc_attr($this->getOptionValue('ycd-money-bg-color')); ?>">
            </div>
        </div>
    </div>
    <div>
        <?php
            require_once(YCD_VIEWS_PATH.'preview.php');
        ?>
    </div>
</div>

<?php
$type = $this->getCurrentTypeFromOptions();
?>
<style>
    .ycd-livew-preview-content {min-width: 460px;}
</style>
<input type="hidden" name="ycd-type" value="<?= esc_attr($type); ?>">