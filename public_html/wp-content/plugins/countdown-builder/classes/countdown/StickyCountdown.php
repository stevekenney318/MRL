<?php
namespace ycd;

class StickyCountdown extends Countdown {

	public function __construct() {
		parent::__construct();
		add_action('add_meta_boxes', array($this, 'mainOptions'));
		add_filter('ycdCountdownDefaultOptions', array($this, 'defaultOptions'), 1, 1);
		add_action('ycdGeneralMetaboxes', array($this, 'metaboxes'), 10, 1);
	}

	public function metaboxes($metaboxes) {
		$metaboxes[YCD_PROGRESS_METABOX_KEY] = array('title' => YCD_PROGRESS_METABOX_TITLE, 'position' => 'normal', 'prioritet' => 'high');
	   
		return $metaboxes;
	}

	public function defaultOptions($options) {

		return $options;
	}

	private function filterTranslations($prepareOptions) {
	    $serverLang = '';
	    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		    $serverLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	    }
	    $lang = substr($serverLang, 0, 2);
	    $translations = $this->getOptionValue('ycd-tr');
	    $currentTranslation = null;
	    if (empty($translations)) {
	    	return  $prepareOptions;
	    }
	    foreach ($translations as $translation) {
	    	$isCode = substr($translation['language'], 0, 2);
	    	if ($lang === $isCode) {
			    $currentTranslation = $translation;
		    }
	    }
	    if ($currentTranslation) {

	    	unset($currentTranslation['language']);
		    foreach ($currentTranslation as $unite => $value) {
		    	if (empty($value)) {
		    		continue;
			    }
		    	$prepareOptions[strtolower($unite)] = $value;
		    }
	    }

	    return $prepareOptions;
    }

	public function includeStyles() {
		$this->includeGeneralScripts();
		$data = array(
			'days' => esc_attr( $this->getOptionValue('ycd-sticky-countdown-days')),
			'hours' => esc_attr($this->getOptionValue('ycd-sticky-countdown-hours')),
			'minutes' => esc_attr($this->getOptionValue('ycd-sticky-countdown-minutes')),
			'seconds' => esc_attr($this->getOptionValue('ycd-sticky-countdown-seconds')),
			'double' => esc_attr($this->getOptionValue('ycd-sticky-enable-double-digits'))
		);
		$data = $this->filterTranslations($data);

		ScriptsIncluder::registerScript('CountdownProFunctionality.js', array( 'dep' => array('jquery')));
		ScriptsIncluder::enqueueScript('CountdownProFunctionality.js');
		ScriptsIncluder::registerScript('Sticky.js', array('dirUrl' => YCD_COUNTDOWN_JS_URL, 'dep' => array('jquery')));
		ScriptsIncluder::localizeScript('Sticky.js', 'YCD_STICKY_ARGS', $data);
		ScriptsIncluder::enqueueScript('Sticky.js');
	}

	public function mainOptions(){
		parent::mainOptions();
		add_meta_box('ycdMainOptions', __('Sticky countdown options', YCD_TEXT_DOMAIN), array($this, 'mainView'), YCD_COUNTDOWN_POST_TYPE, 'normal', 'high');
	}

	public function mainView() {
		$typeObj = $this;
		require_once YCD_VIEWS_MAIN_PATH.'stickyMainView.php';
	}

	public function renderLivePreview() {
		$typeObj = $this;
		require_once YCD_PREVIEW_VIEWS_PATH.'circlePreview.php';
	}

	private function renderStyles() {
		$id = $this->getId();
		$important = ' !important';
		
		if(is_admin()) {
			$important = '';
		}
		$paddingEnable = $this->getOptionValue('ycd-sticky-button-padding-enable');
		$buttonPadding = $this->getOptionValue('ycd-sticky-button-padding');
		$inputBgColor = $this->getOptionValue('ycd-sticky-bg-color');
		$inputColor = $this->getOptionValue('ycd-sticky-button-color');
		$stickyTextColor = $this->getOptionValue('ycd-sticky-text-color');
		$stickyBgColor = $this->getOptionValue('ycd-sticky-text-background-color');
		$stickyCountdownColor = $this->getOptionValue('ycd-sticky-countdown-text-color');
		$countdownSize = (int)$this->getOptionValue('ycd-stick-countdown-font-size');
		$countdownWeight = $this->getOptionValue('ycd-stick-countdown-font-weight');
		
		$enableBorder = $this->getOptionValue('ycd-sticky-button-border-enable');
		$borderWidth = $this->getOptionValue('ycd-sticky-button-border-width');
		$borderRadius = $this->getOptionValue('ycd-sticky-button-border-radius');
		$borderColor = $this->getOptionValue('ycd-sticky-button-border-color');

		$sectionsOrder = $this->getOptionValue('ycd-sticky-countdown-sections');
		$sectionsOrder = apply_filters('ycdStictySectionsOrder', $sectionsOrder, $this);
		$sectionsOrderArray = explode('-', $sectionsOrder);
		$countCols = count($sectionsOrderArray);
		$sectionsWidth = '33';

		if ($countCols == 1) {
			$sectionsWidth = '100';
		}
		else if ($countCols == 2) {
			$sectionsWidth = '49';
		}
		ob_start();
		?>
		<style type="text/css">
			.ycd-hide-banner {
				visibility: hidden;
				height: 0px;
				overflow: hidden;
			}
			.ycd-sticky-header-countdown {
				color: <?php echo esc_attr($stickyCountdownColor); ?>;
				font-size: <?php echo esc_attr($countdownSize); ?>px;
				font-weight: <?php echo esc_attr($countdownWeight); ?>;
			}
			/* Style the header */
			.ycd-sticky-header {
				padding: 10px 16px;
				background: <?php echo esc_attr($stickyBgColor); ?>;
				color: <?php echo esc_attr($stickyTextColor); ?>;
				position: relative;
			}
			
			.ycd-sticky-header-child {
				width: <?php echo esc_attr($sectionsWidth); ?>%;
				display: inline-block;
				text-align: center;
				vertical-align: middle;
			}

			/* Page content */
			.ycd-sticky-content {
				padding: 16px;
			}

			/* The sticky class is added to the header with JS when it reaches its scroll position */
			.ycd-sticky {
				position: fixed;
				top: 0;
				width: 100%;
				z-index: 9999999999999999999999999999999999999999;
			}
			.ycd-sticky-footer {
				position: fixed;
				bottom: 0 !important;
				width: 100%;
				z-index: 9999999999999999999999999999999999999999;
			}
			/* Add some top padding to the page content to prevent sudden quick movement (as the header gets a new position at the top of the page (position:fixed and top:0) */
			.ycd-sticky + .ycd-sticky-content {
				padding-top: 102px;
			}
			.ycd-sticky-button {
				background-color: <?php echo esc_attr($inputBgColor).esc_attr($important); ?>;
				color: <?php echo esc_attr($inputColor).esc_attr($important); ?>;
			}
			<?php if (!empty($paddingEnable)): ?>
				.ycd-sticky-button {
					padding: <?php echo esc_attr($buttonPadding).' '.esc_attr($important); ?>;
				}
			<?php endif; ?>
			<?php if (!empty($enableBorder)): ?>
				.ycd-sticky-button {
					border: <?php echo esc_attr($borderWidth).'  solid '.esc_attr($borderColor).' '.esc_attr($important); ?>;
					border-radius: <?php echo esc_attr($borderRadius).' '.esc_attr($important); ?>;
			<?php endif; ?>
		</style>
		<?php
		$styles = ob_get_contents();
		ob_get_clean();

		return $styles;
	}

	private function renderCountdown() {
		$type = $this->getOptionValue('ycd-sticky-countdown-mode');

		if ($type == 'stickyCountdownDefault') {
			return '<div class="ycd-sticky-clock"></div>';
		}
		$id = $this->getOptionValue('ycd-sticky-countdown');
		$content = do_shortcode('[ycd_countdown id='.esc_attr($id).']');

		return $content;
	}
	
	private function getCloseSectionHTML() {
		$allowCloseSection = $this->getOptionValue('ycd-sticky-enable-close');
		
		if (empty($allowCloseSection)) {
			return '';
		}
		$id = $this->getId();
		$text = $this->getOptionValue('ycd-sticky-close-text');
		$closePosition = $this->getOptionValue('ycd-sticky-close-position');
		ob_start();
		?>
		<div class="ycd-sticky-close-wrapper ycd-sticky-close-wrapper-<?php echo esc_attr($id); ?> " data-id="<?php echo esc_attr($id); ?>">
			<span class="ycd-sticky-close-text ycd-sticky-close-text-<?php echo esc_attr($id); ?>"><?php echo esc_attr($text); ?></span>
		</div>
		<style type="text/css">
			.ycd-sticky-close-wrapper {
				display: inline-block;
				position: absolute;
				line-height: 1;
			}
			.ycd-sticky-close-text {
				cursor: pointer;
			}
		</style>
		<?php if ($closePosition == 'top_right'): ?>
		<style>
			.ycd-sticky-close-wrapper {
			top: 8px;
			right: 8px;
			}
		</style>
		<?php endif;?>
		<?php if ($closePosition == 'top_left'): ?>
		<style>
			.ycd-sticky-close-wrapper {
			top: 8px;
			left: 8px;
			}
		</style>
		<?php endif;?>
		<?php if ($closePosition == 'bottom_right'): ?>
		<style>
			.ycd-sticky-close-wrapper {
			bottom: 8px;
			right: 8px;
			}
		</style>
		<?php endif;?>
		<?php if ($closePosition == 'bottom_left'): ?>
		<style>
			.ycd-sticky-close-wrapper {
			bottom: 8px;
			left: 8px;
			}
		</style>
		<?php endif;?>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	private function getStickyContent() {
		$id = $this->getId();
		
		// time setting
		$settings = array();
		$endDate = $this->getOptionValue('ycd-date-time-picker');
		$timeZone = $this->getOptionValue('ycd-circle-time-zone');
		$settings['endDate'] = $endDate;
		$settings['timeZone'] = $timeZone;
		$settings['ycd-countdown-end-sound'] = $this->getOptionValue('ycd-countdown-end-sound');
		$settings['ycd-countdown-end-sound-url'] = $this->getOptionValue('ycd-countdown-end-sound-url');
		$settings['ycd-countdown-expire-behavior'] = $this->getOptionValue('ycd-countdown-expire-behavior');
		$settings['ycd-expire-text'] = $this->getOptionValue('ycd-expire-text');
		$settings['ycd-expire-url'] = $this->getOptionValue('ycd-expire-url');
		$settings['id'] = $id;
		$settings['ycd-countdown-date-type'] = $this->getOptionValue('ycd-countdown-date-type');
		// end behavior
		$settings['ycd-sticky-expire-behavior'] = $this->getOptionValue('ycd-sticky-expire-behavior');
		$settings['ycd-sticky-url'] = $this->getOptionValue('ycd-sticky-url');
		$settings['ycd-sticky-url-new-tab'] = $this->getOptionValue('ycd-sticky-button-redirect-new-tab');
		$settings['ycd-sticky-button-copy'] = $this->getOptionValue('ycd-sticky-button-copy');
		$settings['ycd-sticky-copy-alert'] = $this->getOptionValue('ycd-sticky-copy-alert');
		$settings['ycd-sticky-alert-text'] = $this->getOptionValue('ycd-sticky-alert-text');
		$settings += $this->getAllSavedOptions();
		$settings += $this->generalOptionsData();
	  
		$settings = json_encode($settings);

		$stickyFooter = $this->getOptionValue('ycd-sticky-enable-footer');
		$footerClass = '';
		if (!empty($stickyFooter)) {
			$footerClass = 'ycd-sticky-footer';
		}
		$textContent = $this->getOptionValue('ycd-sticky-text');
		$buttonText = $this->getOptionValue('ycd-sticky-button-text');
		$sectionsOrder = $this->getOptionValue('ycd-sticky-countdown-sections');
		$sectionsOrder = apply_filters('ycdStictySectionsOrder', $sectionsOrder, $this);
		$sectionsOrderArray = explode('-', $sectionsOrder);
		
		$closeSectionHtml = $this->getCloseSectionHTML();

		$sectionsHtml = array();
		$htmlContent = '<div class="ycd-sticky-header-child ycd-sticky-header-text">';
		$htmlContent .= $textContent;
		$htmlContent .= '</div>';

		$sectionsHtml['Text']  = $htmlContent;

		$contentCountdown = '<div class="ycd-sticky-header-child ycd-sticky-header-countdown">';
		$contentCountdown .= $this->renderCountdown();
		$contentCountdown .= $this->renderProgressBar();
		$contentCountdown .= '</div>';

		$sectionsHtml['Countdown']  = $contentCountdown;

		$contentButton = '<div class="ycd-sticky-header-child ycd-sticky-header-button">';
		$contentButton .= '<input type="button" data-id = "'.esc_attr($id).'" class="ycd-sticky-button" value="'.esc_attr($buttonText).'">';
		$contentButton .= '</div>';	

		$sectionsHtml['Button']  = $contentButton;

		$content = '<div class="ycd-sticky-header ycd-sticky-header-'.esc_attr($id).' '.esc_attr($footerClass).'" data-footer="'.esc_attr($stickyFooter).'" data-id="'.esc_attr($id).'" data-settings="'.esc_attr($settings).'">';
		$content .= $closeSectionHtml;
		foreach ($sectionsOrderArray as $sectionKey) {
			$content .= $sectionsHtml[$sectionKey];
		}
		$content .= '<div>'.esc_attr($this->renderSubscriptionForm()).'</div>';
		$content .= '</div>';
	
		$content .= $this->renderStyles();
		
		return $content;
	}

	public function addToContent() {
		return $this->getViewContent();
	}

	public function getViewContent() {
		$this->includeStyles();
		$content = $this->getStickyContent();
		
		return $content;
	}
}