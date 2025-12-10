<?php
use ycd\AdminHelper;
$defaultData = AdminHelper::defaultData();
$type = $this->getCurrentTypeFromOptions();

$proSpan = '';
$isPro = '';
if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('pro', YCD_TEXT_DOMAIN).'</span>';
}
$createCountdown = AdminHelper::getCreateCountdownUrl();
$args = array('allowTypes' => array(
	'circle', 
	'simple', 
	'moneyCounter', 
	'timer', 
	'clock1',
	'clock2',
	'clock3',
	'clock4',
	'clock5',
	'clock6',
	'clock7',
	'flipClock',
), 'except' => array('sticky'));
$countdownsIdAndTitle = \ycd\Countdown::getCountdownsIdAndTitle($args);
$stickySectionsOrder = $defaultData['stickySectionsOrder'];
$stickyExpiration = $defaultData['stickyButtonExpiration'];
$allowed_html = AdminHelper::getAllowedTags();
?>
<div class="ycd-bootstrap-wrapper">
	<!-- General Section Start -->
	<div class="row form-group ycd-sub-setting-label-wrapper" data-status="false">
		<div class="col-md-6">
			<label class="ycd-label-of-input"><?php _e('General', YCD_TEXT_DOMAIN); ?><span class="toggle-indicator" aria-hidden="true"></span></label></label>
		</div>
		<div class="col-md-5">

		</div>
	</div>
	<div class="ycd-sub-options-settings">
		<?php require_once(dirname(__FILE__).'/sticky_sub_options/stickyGeneralSettings.php'); ?>
	</div>
	<?php if(YCD_PKG_VERSION != YCD_FREE_VERSION) : ?>
		<div class="row form-group">
			<div class="col-md-6">
				<label class="ycd-label-of-input"><?php _e('Display rule', YCD_TEXT_DOMAIN); ?></label>
			</div>
			<div class="col-md-5">

			</div>
		</div>
		<div class="ycd-sub-options-settings">
			<div class="row form-group">
				<div class="col-md-6">
					<label class="ycd-label-of-input" for="ycd-sticky-all-pages"><?php _e('All pages', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-5">
					<label class="ycd-switch">
						<input type="checkbox" id="ycd-sticky-all-pages" name="ycd-sticky-all-pages" class="ycd-accordion-checkbox js-ycd-time-status" <?php echo esc_attr($this->getOptionValue('ycd-sticky-all-pages')); ?>>
						<span class="ycd-slider ycd-round"></span>
					</label>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<!-- General Section Start -->

	<!-- Button settings start -->
	<div class="row form-group ycd-sub-setting-label-wrapper" data-status="false">
		<div class="col-md-6">
			<label class="ycd-label-of-input"><?php _e('Button settings', YCD_TEXT_DOMAIN); ?> <span class="toggle-indicator" aria-hidden="true"></span></label>
		</div>
		<div class="col-md-5">
		</div>
	</div>
	<div class="ycd-sub-options-settings">
		<?php require_once(dirname(__FILE__).'/sticky_sub_options/stickyButtonSettings.php'); ?>
	</div>
	<!-- Button settings end -->
	<!-- Text start -->
	<div class="row form-group ycd-sub-setting-label-wrapper" data-status="false">
		<div class="col-md-6">
			<label class="ycd-label-of-input"><?php _e('Text section', YCD_TEXT_DOMAIN); ?> <span class="toggle-indicator" aria-hidden="true"></span></label></label>
		</div>
		<div class="col-md-5">
		</div>
	</div>
	<div class="ycd-sub-options-settings ycd-sub-setting-label-wrapper" data-status="false">
		<?php require_once(dirname(__FILE__).'/sticky_sub_options/stickyTextSettings.php'); ?>
	</div>
	<!-- Text End -->
	<!-- Countdown Section Start -->
	<div class="row form-group ycd-sub-setting-label-wrapper" data-status="false">
		<div class="col-md-6">
			<label class="ycd-label-of-input"><?php _e('Countdown', YCD_TEXT_DOMAIN); ?><span class="toggle-indicator" aria-hidden="true"></span></label></label>
		</div>
		<div class="col-md-5">

		</div>
	</div>
	<div class="ycd-sub-options-settings">
		<?php require_once(dirname(__FILE__).'/sticky_sub_options/stickyCountdownSettings.php'); ?>
	</div>
	<!-- Countdown Section End -->
</div>
<input type="hidden" name="ycd-type" value="<?php echo esc_attr($type); ?>"> 