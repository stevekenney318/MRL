<?php
use ycd\MultipleChoiceButton;
use ycd\AdminHelper;
use ycd\AdminHelperPro;
use \ycd\ycd_info as ycd_info_fun;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$proSpan = '';
$isPro = '';
if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('Pro', YCD_TEXT_DOMAIN).'</span>';
}
$defaultData = AdminHelper::defaultData();
$dueDate = $this->getOptionValue('ycd-date-time-picker');

$couponsInfo = array();
if (class_exists('ycd\AdminHelperPro')) {
	$couponsInfo = AdminHelperPro::getWooCommerceCouponsInfo();
}
$idAndDates = array();
if (!empty($couponsInfo['idAndDates'])) {
	$idAndDates = $couponsInfo['idAndDates'];
}
?>
<div class="ycd-bootstrap-wrapper">
	<div class="row">
		<div class="col-md-12">
			<label><?php _e('Show Countdown Date Condition', YCD_TEXT_DOMAIN)?></label>
		</div>
	</div>
	<div class="ycd-sub-options-wrapper">
	    <div class="ycd-multichoice-wrapper">
	    <?php
	        $multipleChoiceButton = new MultipleChoiceButton($defaultData['countdown-date-type'], esc_attr($this->getOptionValue('ycd-countdown-date-type')));
	        $allowed_html = AdminHelper::getAllowedTags();
	        echo wp_kses($multipleChoiceButton, $allowed_html);
	    ?>
	    </div>
	    <div id="ycd-date-wooCoupon" class="ycd-countdown-show-text ycd-hide">
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-woo-time-zone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
					    <?php
	                        $timeZoneSelectBox = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-woo-time-zone')), array('name' => 'ycd-woo-time-zone', 'class' => 'js-ycd-select js-ycd-woo-time-zone'));
	                       echo wp_kses($timeZoneSelectBox, $allowed_html);
	                    ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-schedule-time-picker" class="ycd-label-of-input"><?php _e('Coupon', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                    <?php
	                    $savedWooCouponId = $this->getOptionValue('ycd-woo-coupon');
	                    ?>
					    <?php
	                        $idTitle = AdminHelper::selectBox(@$couponsInfo['idAndTitle'], esc_attr($savedWooCouponId), array('name' => 'ycd-woo-coupon', 'class' => 'js-ycd-select js-ycd-woo-coupon'));
	                        echo wp_kses($idTitle, $allowed_html);
	                    ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-schedule-time-picker" class="ycd-label-of-input"><?php _e('Date', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                    <input type="text" readonly class="form-control ycd-woo-coupon-date" name="ycd-woo-coupon-date" data-dates=<?php echo esc_attr(json_encode($idAndDates)); ?> value="<?php echo esc_attr($idAndDates[$savedWooCouponId]); ?>">
	                </div>
	            </div>
	        </div>
	    </div>
	    <div id="ycd-countdown-due-date" class="ycd-countdown-show-text ycd-hide1">
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-date-time-picker" class="ycd-label-of-input"></label>
	            </div>
	            <div class="col-md-6">
	                <input type="text" id="ycd-date-time-picker" class="form-control ycd-date-time-picker" name="ycd-date-time-picker" value="<?php echo esc_attr($dueDate); ?>">
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-date-time-picker" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                <?php
	                    $timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-circle-time-zone')), array('name' => 'ycd-circle-time-zone', 'class' => 'js-ycd-select js-circle-time-zone'));
	                    echo wp_kses($timeZone, $allowed_html);
	                ?>
	                </div>
	            </div>
	        </div>
	    </div>
	    <div id="ycd-date-duration" class="ycd-countdown-show-text ycd-hide">
	        <div class="row">
	            <div class="col-md-6">
	                <label for="ycd-countdown-save-duration" class="ycd-label-of-switch"><?php _e('Save Duration', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <label class="ycd-switch">
	                    <input type="checkbox" id="ycd-countdown-save-duration" class="ycd-accordion-checkbox" name="ycd-countdown-save-duration" <?php echo esc_attr($this->getOptionValue('ycd-countdown-save-duration')); ?>>
	                    <span class="ycd-slider ycd-round"></span>
	                </label>
	            </div>
	        </div>
	        <div class="ycd-accordion-content ycd-hide-content">
	            <div class="row">
	                <div class="col-md-6">
	                    <label for="ycd-countdown-save-duration-each-user" class="ycd-label-of-switch"><?php _e('Save For Each User', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	                </div>
	                <div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
	                    <label class="ycd-switch">
	                        <input type="checkbox" id="ycd-countdown-save-duration-each-user" class="" name="ycd-countdown-save-duration-each-user" <?php echo esc_attr($this->getOptionValue('ycd-countdown-save-duration-each-user')); ?>>
	                        <span class="ycd-slider ycd-round"></span>
	                    </label>
	                </div>
	            </div>
	            <div class="row">
	                <div class="col-md-6">
	                    <label for="ycd-countdown-restart" class="ycd-label-of-switch"><?php _e('Restart', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	                </div>
	                <div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
	                    <label class="ycd-switch">
	                        <input type="checkbox" id="ycd-countdown-restart" class="ycd-accordion-checkbox" name="ycd-countdown-restart" <?php echo esc_attr($this->getOptionValue('ycd-countdown-restart')); ?>>
	                        <span class="ycd-slider ycd-round"></span>
	                    </label>
	                </div>
	            </div>
	            <div class="ycd-accordion-content ycd-hide-content">
	                <div class="row form-group">
	                    <div class="col-md-6">
	                        <label for="ycd-countdown-save-duration-restart" class="ycd-label-of-switch"><?php _e('Every', YCD_TEXT_DOMAIN);  ?></label>
	                    </div>
	                    <div class="col-md-2 ycd-option-wrapper">
	                        <input type="number" class="form-control" name="ycd-countdown-restart-hour" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-restart-hour')); ?>">
	                    </div>
	                    <div class="col-md-1">
	                        <label><?php _e('Hour(s)', YCD_TEXT_DOMAIN); ?></label>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-4">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeHours"><?php _e('Days', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-duration-days" id="ycdCountdownTimeDays" min="0" class="form-control ycd-timer-time-settings" data-type="days" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-duration-days'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeHours"><?php _e('Hrs', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-duration-hours" id="ycdCountdownTimeHours" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="hours" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-duration-hours'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeMinutes"><?php _e('Mins', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-duration-minutes" id="ycdCountdownTimeMinutes" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="minutes" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-duration-minutes'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeSeconds"><?php _e('Secs', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-duration-seconds" id="ycdCountdownTimeSeconds" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-duration-seconds'))?>">
	            </div>
	        </div>
	    </div>
	    <div id="ycd-date-schedule" class="ycd-countdown-show-text ycd-hide">
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-schedule-time-picker" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                    <?php
	                        $timeZone = $timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-schedule-time-zone')), array('name' => 'ycd-schedule-time-zone', 'class' => 'js-ycd-select js-ycd-schedule-time-zone'));
	                        echo wp_kses($timeZone, $allowed_html);
	                    ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label><?php _e('Start', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label><?php _e('Week day', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                <?php
	                    $weekDay = AdminHelper::selectBox(
	                    @$defaultData['week-days'],
	                    esc_attr($this->getOptionValue('ycd-schedule-start-day')),
	                    array(
	                        'name' => 'ycd-schedule-start-day',
	                        'data-week-number-key' => 'startDayNumber',
	                        'class' => 'js-ycd-select ycd-date-week-day js-ycd-schedule-start-day'
	                    ));

	                    echo wp_kses($weekDay, $allowed_html);
	                ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	            </div>
	            <div class="col-md-2">
	                <label class="ycd-label-of-input"><?php _e('from', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-4">
	                <input type="text" name="ycd-schedule-start-from" class="form-control js-datetimepicker-seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-schedule-start-from')); ?>" autocomplete="off">
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label><?php _e('End', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label><?php _e('Week day', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
	                <?php
	                    $weekDays = AdminHelper::selectBox(@$defaultData['week-days'],
	                    esc_attr($this->getOptionValue('ycd-schedule-end-day')),
	                    array(
	                        'name' => 'ycd-schedule-end-day',
	                        'data-week-number-key' => 'endDayNumber',
	                        'class' => 'js-ycd-select ycd-date-week-day js-ycd-schedule-end-day'
	                    )
	                );
	                echo wp_kses($weekDays, $allowed_html);
	                ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	            </div>
	            <div class="col-md-2">
	                <label class="ycd-label-of-input"><?php _e('to', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-4">
	                <input type="text" name="ycd-schedule-end-to" class="form-control js-datetimepicker-seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-schedule-end-to')); ?>" autocomplete="off">
	            </div>
	        </div>
	    </div>
	    <div id="ycd-date-schedule2" class="ycd-countdown-show-text ycd-hide">
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label for="ycd-schedule2-time-zone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
					    <?php
	                        $timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-schedule2-time-zone')), array('name' => 'ycd-schedule2-time-zone', 'class' => 'js-ycd-select js-ycd-schedule-time-zone'));
	                        echo wp_kses($timeZone, $allowed_html);
	                    ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	                <label><?php _e('Week day(s)', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-6">
	                <div class="ycd-select-wrapper">
					    <?php
	                        $schedule = AdminHelper::selectBox(
						    @$defaultData['week-days'],
						    $this->getOptionValue('ycd-schedule2-day'),
						    array(
							    'name' => 'ycd-schedule2-day[]',
	                            'multiple' => 'multiple',
							    'data-week-number-key' => 'startDayNumber',
							    'class' => 'js-ycd-select ycd-date-week-day js-ycd-schedule-start-day'
						    ));

	                        echo wp_kses($schedule, $allowed_html);
	                    ?>
	                </div>
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	            </div>
	            <div class="col-md-2">
	                <label class="ycd-label-of-input" for="ycd-schedule2-from"><?php _e('from', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-4">
	                <input type="text" name="ycd-schedule2-from" id="ycd-schedule2-from" class="form-control js-datetimepicker-seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-schedule2-from')); ?>" autocomplete="off">
	            </div>
	        </div>
	        <div class="row form-group">
	            <div class="col-md-6">
	            </div>
	            <div class="col-md-2">
	                <label class="ycd-label-of-input" for="ycd-schedule2-to"><?php _e('to', YCD_TEXT_DOMAIN); ?></label>
	            </div>
	            <div class="col-md-4">
	                <input type="text" name="ycd-schedule2-to" id="ycd-schedule2-to" class="form-control js-datetimepicker-seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-schedule2-to')); ?>" autocomplete="off">
	            </div>
	        </div>
	    </div>
		<div id="ycd-date-evergreen" class="ycd-countdown-show-text ycd-hide">
	        <div class="row form-group">
	            <div class="col-md-4">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownEvergreenDays"><?php _e('Days', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-evergreen-days" id="ycdCountdownEvergreenDays" min="0" class="form-control ycd-timer-time-settings" data-type="days" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-evergreen-days'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownEvergreenHours"><?php _e('Hrs', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-evergreen-hours" id="ycdCountdownEvergreenHours" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="hours" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-evergreen-hours'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeMinutes"><?php _e('Mins', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-evergreen-minutes" id="ycdCountdownEvergreenMinutes" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="minutes" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-evergreen-minutes'))?>">
	            </div>
	            <div class="col-md-2">
	                <label for="ycdCountdownTimeSeconds"><?php _e('Secs', YCD_TEXT_DOMAIN); ?></label>
	                <input type="number" name="ycd-countdown-evergreen-seconds" id="ycdCountdownEvergreenSeconds" min="0" max="60" class="form-control ycd-timer-time-settings" data-type="seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-evergreen-seconds'))?>">
	            </div>
	        </div>
	    </div>
		<div id="ycd-date-schedule3" class="ycd-countdown-show-text ycd-hide">
			<div class="row form-group">
				<div class="col-md-6">
					<label for="ycd-schedule3-time-zone" class="ycd-label-of-input"><?php _e('Time Zone', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<div class="ycd-select-wrapper">
						<?php
	                        $timeZone = $timeZone = AdminHelper::selectBox($defaultData['time-zone'], esc_attr($this->getOptionValue('ycd-schedule3-time-zone')), array('name' => 'ycd-schedule3-time-zone', 'class' => 'js-ycd-select js-ycd-schedule-time-zone'));
	                        echo wp_kses($timeZone, $allowed_html);
	                    ?>
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label><?php _e('Week day', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-6">
					<div class="ycd-select-wrapper">
						<?php
	                        $schedule = AdminHelper::selectBox(
							@$defaultData['week-days'],
							$this->getOptionValue('ycd-schedule3-day'),
							array(
								'name' => 'ycd-schedule3-day',
								'data-week-number-key' => 'endDayNumber',
								'class' => 'js-ycd-select ycd-date-week-day js-ycd-schedule-start-day'
							));

	                        echo wp_kses($schedule, $allowed_html);
	                    ?>
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
				</div>
				<div class="col-md-2">
					<label class="ycd-label-of-input" for="ycd-schedule3-time"><?php _e('time', YCD_TEXT_DOMAIN); ?></label>
				</div>
				<div class="col-md-4">
					<input type="text" name="ycd-schedule3-time" id="ycd-schedule3-time" class="form-control js-datetimepicker-seconds" value="<?php echo esc_attr($this->getOptionValue('ycd-schedule3-time')); ?>" autocomplete="off">
				</div>
			</div>
		</div>
	</div>
	<!-- Woo condition -->
	<!-- Woo condition-->
	<div class="ycd-sub-options-wrapper">
		<div class="row">
			<div class="col-md-6">
				<label for="ycd-countdown-enable-woo-condition"><?php _e('Countdown Expiration WooCommerce Condition', YCD_TEXT_DOMAIN)?></label>
				<?php echo wp_kses(ycd\ycd_info('The countdown needs to be inserted inside a WooCommerce post, and if the conditions are satisfied, the countdown will stop showing.'), $allowed_html) ?>
			</div>
			<div class="col-md-6">
				<label class="ycd-switch">
					<input type="checkbox" id="ycd-countdown-enable-woo-condition" name="ycd-countdown-enable-woo-condition" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-countdown-enable-woo-condition')); ?>>
					<span class="ycd-slider ycd-round"></span>
				</label>
			</div>
		</div>
		<div class="ycd-accordion-content ycd-hide-content">
			<div class="ycd-multichoice-wrapper">
				<?php
				$multipleChoiceButton = new MultipleChoiceButton($defaultData['countdown-woo-conditions'], esc_attr($this->getOptionValue('ycd-woo-condition')));
				$allowed_html = AdminHelper::getAllowedTags();
				echo wp_kses($multipleChoiceButton, $allowed_html);
				?>
			</div>
		</div>
		<?php if(YCD_PKG_VERSION >= YCD_GOLD_VERSION ): ?>
			<?php require_once(YCD_ADMIN_VIEWS_PRO_PATH."/wooExpirationAdvancedFeatures.php") ?>
		<?php endif; ?>
	</div>
<div class="row">
	<div class="col-md-6">
		<label for="ycd-countdown-enable-start-date" class="ycd-label-of-switch"><?php _e('Enable start date', YCD_TEXT_DOMAIN); echo wp_kses(ycd\ycd_info('Specify the date when start the countdown'), $allowed_html); ?></label>
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
<div class="row">
	<div class="col-md-6">
		<label for="ycd-scroll-to-countdown" class="ycd-label-of-switch"><?php _e('Scroll to the countdown', YCD_TEXT_DOMAIN); echo wp_kses(ycd\ycd_info("The browser automatically will scroll to the countdown"), $allowed_html); ?></label>
	</div>
	<div class="col-md-6">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-scroll-to-countdown" name="ycd-scroll-to-countdown" <?php echo esc_attr($this->getOptionValue('ycd-scroll-to-countdown')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="ycd-countdown-end-sound" class="ycd-label-of-switch"><?php _e('Timer End Sound', YCD_TEXT_DOMAIN); ?></label>
    </div>
    <div class="col-md-6">
        <label class="ycd-switch">
            <input type="checkbox" id="ycd-countdown-end-sound" name="ycd-countdown-end-sound" class="ycd-accordion-checkbox" <?php echo esc_attr($this->getOptionValue('ycd-countdown-end-sound')); ?>>
            <span class="ycd-slider ycd-round"></span>
        </label>
    </div>
</div>
<div class="ycd-accordion-content ycd-hide-content">
    <div class="row form-group">
        <div class="col-md-2">
            <input id="js-upload-countdown-end-sound" class="btn btn-sm" type="button" value="<?php _e('Change sound', YCD_TEXT_DOMAIN); ?>">
        </div>
        <div class="col-md-2">
            <input type="button" data-default-song="<?php echo esc_attr($this->getDefaultValue('ycd-countdown-end-sound-url')); ?>" id="js-reset-to-default-song" class="btn btn-sm btn-danger" value="<?php _e('Reset', YCD_TEXT_DOMAIN); ?>">
        </div>
        <div class="col-md-5">
            <input type="text" id="js-sound-open-url" readonly="" class="form-control input-sm" name="ycd-countdown-end-sound-url" value="<?php echo esc_attr($this->getOptionValue('ycd-countdown-end-sound-url')); ?>">
        </div>
        <div class="col-md-1">
            <span class="dashicons dashicons-controls-volumeon js-preview-sound"></span>
        </div>
    </div>
</div>
<div class="row">
	<div class="col-md-6">
		<label for="ycd-countdown-stop-inactive" class="ycd-label-of-switch"><?php _e('Stop inactive tab countdown', YCD_TEXT_DOMAIN); ?><?php  echo wp_kses($proSpan, $allowed_html); echo wp_kses(ycd\ycd_info('The countdown timer on the web page will pause when the tab becomes inactive and resume when the tab becomes active again.'), $allowed_html); ?></label>
	</div>
	<div class="col-md-6 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<label class="ycd-switch">
			<input type="checkbox" id="ycd-countdown-stop-inactive" name="ycd-countdown-stop-inactive" <?php echo esc_attr($this->getOptionValue('ycd-countdown-stop-inactive')); ?>>
			<span class="ycd-slider ycd-round"></span>
		</label>
	</div>
</div>
<div class="row">
    <div class="col-md-12">
        <div>
            <label for="ycd-edtitor-css" class="ycd-label-of-switch"><?php _e('Custom CSS', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <textarea id="ycd-edtitor-css" id="ycd-edtitor-css" rows="5" name="ycd-custom-css" class="widefat textarea"><?php echo esc_attr($this->getOptionValue('ycd-custom-css')); ?></textarea>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div>
            <label for="ycd-edtitor-js" class="ycd-label-of-switch"><?php _e('Custom JS', YCD_TEXT_DOMAIN); ?></label>
        </div>
        <textarea id="ycd-edtitor-js" id="ycd-edtitor-js" rows="5" name="ycd-custom-js" class="widefat textarea"><?php echo esc_attr($this->getOptionValue('ycd-custom-js')); ?></textarea>
    </div>
</div>
</div>

<style type="text/css">
    .edit-post-status,
    .edit-visibility,
    .edit-timestamp {
        display: none;
    }
</style>
