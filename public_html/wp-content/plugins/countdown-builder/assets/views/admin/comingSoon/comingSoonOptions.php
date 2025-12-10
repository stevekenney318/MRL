<?php
use ycd\AdminHelper;
use ycd\HelperFunction;
$defaultData = AdminHelper::defaultData();
$userSavedRoles = $this->getOptionValue('ycd-coming-soon-user-roles');

if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('pro', YCD_TEXT_DOMAIN).'</span>';
}
$allowed_html = AdminHelper::getAllowedTags();
?>
<div class="panel panel-default">
	<div class="panel-heading"><?php _e('Options', YCD_TEXT_DOMAIN)?></div>
	<div class="panel-body">
		<?php if (YcdDataAccess::isHidden('comingSoonSchedule')): ?>
		<!-- Start automatically enable countdown -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-automat-enable" class="ycd-label-of-switch"><?php _e('Automatically start by date', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-automat-enable" name="ycd-coming-soon-automat-enable" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-automat-enable')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-coming-soon-start-timezone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<div class="ycd-select-wrapper">
						<?php
							$timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-coming-soon-start-timezone')), array('name' => 'ycd-coming-soon-start-timezone', 'class' => 'js-ycd-select'));
							echo wp_kses($timeZone, $allowed_html);
						?>
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-coming-soon-user-roles"><?php _e('Select start date', YCD_TEXT_DOMAIN)?></label>
				</div>
				<div class="col-md-6">
					<input type="text" id="ycd-coming-soon-start" class="form-control ycd-date-time-picker" name="ycd-coming-soon-start" value="<?php echo esc_attr($this->getOptionValue('ycd-coming-soon-start')); ?>">
				</div>
			</div>
		</div>
		<!-- End automatically enable countdown -->
		<!-- Start automatically expiration countdown -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-automat-expiration" class="ycd-label-of-switch"><?php _e('Automatically Expire by date', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-automat-expiration" name="ycd-coming-soon-automat-expiration" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-automat-expiration')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-coming-soon-expiration-timezone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<div class="ycd-select-wrapper">
						<?php
							$timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-coming-soon-expiration-timezone')), array('name' => 'ycd-coming-soon-expiration-timezone', 'class' => 'js-ycd-select'));
							echo wp_kses($timeZone, $allowed_html);
						?>
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-coming-soon-expiration"><?php _e('Select expiration date', YCD_TEXT_DOMAIN)?></label>
				</div>
				<div class="col-md-6">
					<input type="text" id="ycd-coming-soon-expiration" class="form-control ycd-date-time-picker" name="ycd-coming-soon-expiration" value="<?php echo esc_attr($this->getOptionValue('ycd-coming-soon-expiration')); ?>">
				</div>
			</div>
		</div>
		<!-- End automatically expiration countdown -->
		<?php endif; ?>
		<!-- start User role options -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-for-loggdin" class="ycd-label-of-switch"><?php _e('Show on selected user roles', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-for-loggdin" name="ycd-coming-soon-for-loggdin" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-for-loggdin')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="row form-group ycd-accordion-content ycd-hide-content">
			<div class="col-md-6">
				<label for="ycd-coming-soon-user-roles"><?php _e('Select user role(s)', YCD_TEXT_DOMAIN)?></label>
			</div>
			<div class="col-md-6">
				<?php
					$userRoles = HelperFunction::createSelectBox($defaultData['userRoles'], $userSavedRoles, array('name' => 'ycd-coming-soon-user-roles[]', 'class' => 'js-ycd-select  ycd-countdowns', 'multiple' => 'multiple', 'id' => 'ycd-coming-soon-user-roles'));
					echo wp_kses($userRoles, $allowed_html);
				?>
			</div>
		</div>
		<!-- end User role options -->
		<!-- Start Devices  -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-for-devices" class="ycd-label-of-switch"><?php _e('Show on selected devices', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-for-devices" name="ycd-coming-soon-for-devices" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-for-devices')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="row form-group ycd-accordion-content ycd-hide-content">
			<div class="col-md-6">
				<label for="ycd-coming-soon-devices"><?php _e('Select device(s)', YCD_TEXT_DOMAIN)?></label>
			</div>
			<div class="col-md-6">
				<?php
				$userRoles = HelperFunction::createSelectBox($defaultData['devices'], $this->getOptionValue('ycd-coming-soon-devices'), array('name' => 'ycd-coming-soon-devices[]', 'class' => 'js-ycd-select  ycd-countdowns', 'multiple' => 'multiple', 'id' => 'ycd-coming-soon-user-roles'));
				echo wp_kses($userRoles, $allowed_html);
				?>
			</div>
		</div>
		<!-- End Devices  -->
		<!-- start white list option -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-whitelist-ip" class="ycd-label-of-switch"><?php _e('White list IP address', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-whitelist-ip" name="ycd-coming-soon-whitelist-ip" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-whitelist-ip')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			<div class="col-md-6">
				<label for="ycd-coming-soon-ip-address"><?php _e('IP address(s)', YCD_TEXT_DOMAIN);?></label>
			</div>
			<div class="col-md-6">
				<input type="text" class="form-control" name="ycd-coming-soon-ip-address" placeholder="<?php _e('You can enter multiple IP address, just separate them with comma', YCD_TEXT_DOMAIN)?>" value="<?php echo esc_attr($this->getOptionValue('ycd-coming-soon-ip-address'))?>">
			</div>
		</div>
		<!-- end white list option -->
		<div class="row form-group">
			<div class="col-md-6">
				<label for="ycd-coming-soon-exclude-url-enable" class="ycd-label-of-switch"><?php _e('Exclude URL(s)', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html);  ?></label>
			</div>
			<div class="col-md-6 ycd-circles-width-wrapper ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-coming-soon-exclude-url-enable" name="ycd-coming-soon-exclude-url-enable" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-coming-soon-exclude-url-enable')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			<div class="col-md-6">
				<label for="ycd-coming-soon-exclude-url"><?php _e('URL(s)', YCD_TEXT_DOMAIN);?></label>
			</div>
			<div class="col-md-6">
				<textarea class="form-control" rows="5" name="ycd-coming-soon-exclude-url" id="ycd-coming-soon-exclude-url"><?php esc_attr_e($this->getOptionValue('ycd-coming-soon-exclude-url'))?></textarea>
				<span>Split URLs by comma</span>
			</div>
		</div>
	</div>
</div>