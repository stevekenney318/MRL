<?php
namespace ycd;


class TimerCountdown extends Countdown {
	public function __construct() {
		parent::__construct();
		if(is_admin()) {
			$this->adminConstruct();
		}
	}
	
	public function renderLivePreview() {
		$typeObj = $this;
		require_once YCD_PREVIEW_VIEWS_PATH.'timerPreview.php';
	}
	
	public function adminConstruct() {
		add_filter('ycdGeneralMetaboxes', array($this, 'metaboxes'), 1, 1);
		add_action('add_meta_boxes', array($this, 'mainOptions'));
		add_filter('ycdCountdownDefaultOptions', array($this, 'defaultOptions'), 1, 1);
	}
	
	public function defaultOptions($defaultOptions) {
		$changingOptions = array(
			'ycd-text-font-family' => array('name' => 'ycd-text-font-family', 'type' => 'text', 'defaultValue' => 'Helvetica')
		);
		$defaultOptions = $this->changeDefaultOptionsByNames($defaultOptions, $changingOptions);
		
		return $defaultOptions;
	}

    public function addToContent() {
        add_filter('the_content', array($this, 'getTheContentFilter'),99999999, 1);
    }
	
	public function metaboxes($metaboxes) {
		unset($metaboxes['generalOptions']);
		$metaboxes[YCD_PROGRESS_METABOX_KEY] = array('title' => YCD_PROGRESS_METABOX_TITLE, 'position' => 'normal', 'prioritet' => 'high');
		
		return $metaboxes;
	}
	
	public function mainOptions(){
		parent::mainOptions();
		add_meta_box('ycdTimerOptions', __('Timer options', YCD_TEXT_DOMAIN), array($this, 'mainView'), YCD_COUNTDOWN_POST_TYPE, 'normal', 'high');
	}
	
	public function mainView() {
		$typeObj = $this;
		require_once YCD_VIEWS_MAIN_PATH.'timerMainView.php';
	}
	
	public function getTimerSettings() {
		$options = array();
		$allDataOptions = $this->getDataAllOptions();
	
		$options['id'] = $this->getId();
		$options['days'] = $this->getOptionValue('ycd-timer-days');
		$options['hours'] = $this->getOptionValue('ycd-timer-hours');
		$options['minutes'] = $this->getOptionValue('ycd-timer-minutes');
		$options['seconds'] = $this->getOptionValue('ycd-timer-seconds');
		$options['autoCounting'] = $this->getOptionValue('ycd-timer-auto-counting');
		$options['timerButton'] = $this->getOptionValue('ycd-countdown-timer-button');
		$options['ycd-timer-write-stopped'] = $this->getOptionValue('ycd-timer-write-stopped');

		return array_merge($options, $allDataOptions);
	}
	
	private function contentStyles() {
		$id = $this->getId();
		$fontFamily = $this->getOptionValue('ycd-text-font-family');
		if ($fontFamily === 'customFont') {
			$fontFamily = $this->getOptionValue('ycd-text-font-family-custom');
		}
		$fontSize = $this->getOptionValue('ycd-timer-font-size').'em !important';
		$labelFontSize = (int)$this->getOptionValue('ycd-timer-font-size-label').'px';
		$timerColor = $this->getOptionValue('ycd-timer-color');
		$timerLabelsColor = $this->getOptionValue('ycd-timer-labels-color');
		$timerContentPadding = (int)$this->getOptionValue('ycd-timer-content-padding').'px';
		$imageUrl = $this->getOptionValue('ycd-bg-image-url');
		$bgImageSize = $this->getOptionValue('ycd-bg-image-size');
		$imageRepeat = $this->getOptionValue('ycd-bg-image-repeat');
		$textAlign = $this->getOptionValue('ycd-timer-content-alignment');

		$startStopBgColor = $this->getOptionValue('ycd-timer-stop-bg-color');
		$startStopColor = $this->getOptionValue('ycd-timer-stop-color');

		$resetBgColor = $this->getOptionValue('ycd-timer-reset-bg-color');
		$resetColor = $this->getOptionValue('ycd-timer-reset-color');
		$important = '';
		
		if(!is_admin()) {
			$important = '!important';
		}
		ob_start();
		?>
			<style type="text/css"  id="ycd-digit-font-family-<?php echo esc_attr($id); ?>">
				.ycd-timer-wrapper-<?php echo esc_attr($id); ?> .ycd-timer-unit > span {
					font-family: <?php echo esc_attr($fontFamily) ?>;
				}
			</style>
			<style type="text/css" id="ycd-digit-font-size-<?php echo esc_attr($id); ?>">
				.ycd-timer-time.ycd-timer-wrapper-<?php echo esc_attr($id); ?> {
					font-size: <?php echo esc_attr($fontSize) ?>;
				}
			</style>
			<style type="text/css" id="ycd-timer-content-padding-<?php echo esc_attr($id); ?>">
				.ycd-timer-content-wrapper-<?php echo esc_attr($id); ?> {
					padding: <?php echo esc_attr($timerContentPadding) ?>;
				}
			</style>
			<style type="text/css" id="ycd-timer-content-padding-<?php echo esc_attr($id); ?>">
				.ycd-countdown-wrapper .ycd-timer-start-stop-<?php echo esc_attr($id); ?> {
					background-color: <?php echo esc_attr($startStopBgColor) ?>;
					color: <?php echo esc_attr($startStopColor) ?>;
				}
				.ycd-countdown-wrapper .ycd-timer-reset-<?php echo esc_attr($id); ?> {
					background-color: <?php echo esc_attr($resetBgColor) ?>;
					color: <?php echo esc_attr($resetColor) ?>;
				}
			</style>
			<style type="text/css">
				.ycd-timer-wrapper-<?php echo esc_attr($id); ?> .ycd-timer-box span {
					color: <?php echo esc_attr($timerColor); ?> <?php echo esc_attr($important); ?>;
				}
               <?php if (!ycd_is_free()): ?>
                .ycd-timer-wrapper-<?php echo esc_attr($id); ?> .ycd-timer-box div.ycd-timer-unit-text {
					color: <?php echo esc_attr($timerLabelsColor); ?> <?php echo esc_attr($important); ?>;
				}
                <?php endif; ?>
				.ycd-timer-wrapper-<?php echo esc_attr($id); ?> {
					<?php echo esc_attr('background-image: url('.esc_attr($imageUrl).'); background-repeat: '.esc_attr($imageRepeat).'; background-size: '.esc_attr($bgImageSize).'; '); ?>
					text-align: <?php echo esc_attr($textAlign); ?>;
				}
                .ycd-timer-unit-text {
                    font-size: <?php echo esc_attr($labelFontSize); ?>;
                }
			</style>
		<?php
		$styles = ob_get_contents();
		ob_end_clean();
		
		return $styles;
	}
	
	private function getTimerContent() {
		$id = $this->getId();
		
		$timerButton = $this->getOptionValue('ycd-countdown-timer-button');
		$enableMilliseconds = $this->getOptionValue('ycd-countdown-timer-milliseconds');
		
		$millisecondsClass = 'ycd-hide';
		if (!empty($enableMilliseconds)) {
			$millisecondsClass = '';
		}

		$startTitle = $this->getOptionValue('ycd-timer-button-start-title');
		$stopTitle = $this->getOptionValue('ycd-timer-button-stop-title');
		$autoCounting = (bool)$this->getOptionValue('ycd-timer-auto-counting');
		$resetButton = (bool)$this->getOptionValue('ycd-timer-reset-button');
		$resetButtonLabel = $this->getOptionValue('ycd-timer-reset-button-label');
		$stopButtonClassName = $this->getOptionValue('ycd-timer-button-stop-custom-class');
		$resetButtonClassName = $this->getOptionValue('ycd-timer-reset-button-class-name');
		$timerDays = $this->getOptionValue('ycd-countdown-timer-days');
		$daysLabel = $this->getOptionValue('ycd-timer-label-days');
		$hoursLabel = $this->getOptionValue('ycd-timer-label-hours');
		$minutesLabel = $this->getOptionValue('ycd-timer-label-minutes');
		$secsLabel = $this->getOptionValue('ycd-timer-label-seconds');
		$buttonTitle = ($autoCounting) ? $stopTitle: $startTitle;
		
		$daysClassName = 'ycd-hide';
		if (!empty($timerDays)) {
			$daysClassName = '';
		}
		$hideLabelsCLass = 'ycd-hide-label';
		$isHideLabel = $this->getOptionValue('ycd-countdown-timer-labels');
		if (!empty($isHideLabel)) {
			$hideLabelsCLass = '';
        }
		ob_start();
		?>
		<div class="ycd-countdown-wrapper ycd-timer-content-wrapper-<?php echo esc_attr($id); ?> ycd-countdown-content-wrapper">
			<div class="ycd-timer-time ycd-timer-container ycd-timer-wrapper-<?php echo esc_attr($id); ?>" data-id="<?php echo esc_attr($id); ?>">
				<div class="timer-time-set ycd-timer-box" id="currentTime">
					<div class="ycd-timer-span-wrapper">
						<div class="ycd-timer-unit"><span id="ycdDaysValue" class="ycd-days-value-<?php echo esc_attr($id); ?> ycd-timer-number ycd-days-span <?php echo esc_attr($daysClassName); ?>">00</span><!--
						--><span class="ycd-dots ycd-days-span <?php echo esc_attr($daysClassName); ?>">:</span><div class="ycd-timer-unit-text ycd-timer-unit-text-days <?php echo esc_attr($daysClassName).' '.esc_attr($hideLabelsCLass); ?>"><?php echo esc_attr($daysLabel); ?></div><!--
						--></div><div class="ycd-timer-unit"><span id="ycdHoursValue" class="ycd-hours-value-<?php echo esc_attr($id); ?> ycd-timer-number">00</span><!--
						--><span class="ycd-dots">:</span><div class="ycd-timer-unit-text ycd-timer-unit-text-hours <?php echo esc_attr($hideLabelsCLass); ?>"><?php echo esc_attr($hoursLabel); ?></div><!--
						--></div><div class="ycd-timer-unit"><span id="ycdMinutesValue" class="ycd-minutes-value-<?php echo esc_attr($id); ?> ycd-timer-number">00</span><!--
						--><span class="ycd-dots">:</span><div class="ycd-timer-unit-text ycd-timer-unit-text-minutes <?php echo esc_attr($hideLabelsCLass); ?>"><?php echo esc_attr($minutesLabel); ?></div><!--
						--></div><div class="ycd-timer-unit"><span id="ycdSecondsValue" class="ycd-seconds-value-<?php echo esc_attr($id); ?> ycd-timer-number">00</span><div class="ycd-timer-unit-text ycd-timer-unit-text-seconds <?php echo esc_attr($hideLabelsCLass); ?>"><?php echo esc_attr($secsLabel); ?></div></div><!--
						--><span class="ycd-milliseconds <?php echo esc_attr($millisecondsClass); ?>">.</span><span class="ycd-milliseconds <?php echo esc_attr($millisecondsClass); ?> ycd-milliseconds-value ycd-milliseconds-value-<?php echo esc_attr($id); ?>">000</span>
					</div>
				</div>
				<div class="timer-time-set ycd-timer-box ycd-timer-box-next" id="nextTime" style="opacity: 0;">
					<span id="ycdDaysNext" class="ycd-days-next-value-<?php echo esc_attr($id); ?> ycd-timer-number">00</span><!--
						--><span class="ycd-dots">:</span><!--
						--><span id="ycdHoursNext" class="ycd-hours-next-value-<?php echo esc_attr($id); ?>">00</span><!--
					--><span>:</span><!--
					--><span id="ycdMinutesNext" class="ycd-minutes-next-value-<?php echo esc_attr($id); ?>">00</span><!--
					--><span>:</span><!--
					--><span id="ycdSecondsNext" class="ycd-seconds-next-value-<?php echo esc_attr($id); ?>">00</span>
				</div>
			</div>
			<div class="ycd-timmer-buttons ycd-timmer-buttons-<?php echo esc_attr($id); ?>">
				<?php if (!empty($timerButton)): ?>
					<button class="ycd-timer-start-stop ycd-timer-start-stop-<?php echo esc_attr($id); ?> <?php echo esc_attr($stopButtonClassName); ?>" data-status="<?php echo esc_attr($autoCounting); ?>" data-start="<?php echo esc_attr($startTitle); ?>" data-stop="<?php echo esc_attr($stopTitle);?>"><?php echo esc_attr($buttonTitle); ?></button>
				<?php endif; ?>
				<?php if (!empty($resetButton)): ?>
					<button class="ycd-timer-reset ycd-timer-reset-<?php echo esc_attr($id); ?> <?php echo esc_attr($resetButtonClassName); ?>"><?php echo esc_attr($resetButtonLabel); ?></button>
				<?php endif; ?>
			</div>
			<?php if ($this->getOptionValue('ycd-timer-write-stopped')): ?>
			<div class="ycd-stopped-times ycd-stopped-times-<?php esc_attr_e($id);?>">
				<h3><?php esc_attr_e($this->getOptionValue('ycd-timer-write-stopped-header'));?></h3>
				<ol class="stopped-times-content">

				</ol>
			</div>
			<?php endif;?>
		<?php
		$content = ob_get_contents();
		$content .= $this->contentStyles();
		ob_end_clean();
		$content .= $this->renderSubscriptionForm();
		$content .= $this->renderProgressBar(); 
		$content .= '</div>';
		
		return $content;
	}
	
	public function renderScripts() {
		wp_enqueue_script('jquery');
		if(YCD_PKG_VERSION != YCD_FREE_VERSION) {
			if (!get_option('ycd-disable-google-fonts')) {
				ScriptsIncluder::registerScript('ycdGoogleFonts.js');
				ScriptsIncluder::enqueueScript('ycdGoogleFonts.js');
			}
		}
		$this->includeGeneralScripts();
		$options = $this->getTimerSettings();
		$options = json_encode($options);
		$settings = array();

		ScriptsIncluder::registerScript('ycdTimer.js');
		ScriptsIncluder::localizeScript('ycdTimer.js', 'YcdArgs', array(
			'isAdmin' => is_admin(),
			'options' => $options
		));
		ScriptsIncluder::enqueueScript('ycdTimer.js');
		ScriptsIncluder::registerStyle('timer.css');
		ScriptsIncluder::enqueueStyle('timer.css');
	}
	
	public function getViewContent() {
		$this->renderScripts();
		
		$content = $this->getTimerContent();
		
		return $content;
	}
}