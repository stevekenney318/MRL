<?php
namespace ycd;

class SimpleCountdown extends Countdown
{
    private $mode = 'textUnderCountdown';
    private $options = array();
    private $timeUnites = array('years', 'months', 'days', 'hours', 'minutes', 'seconds');

    public function __construct()
    {
        parent::__construct();
        if(is_admin()) {
            $this->adminConstruct();
        }
    }

    public function setMode($mode)
    {
        return $this->mode;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getTimeUnites()
    {
        return $this->timeUnites;
    }

    public function adminConstruct()
    {
        add_filter('ycdGeneralMetaboxes', array($this, 'metaboxes'), 1, 1);
        add_action('add_meta_boxes', array($this, 'mainOptions'));
        add_filter('ycdCountdownDefaultOptions', array($this, 'defaultOptions'), 1, 1);
    }

    public function metaboxes($metaboxes) {
     //   $metaboxes[YCD_PROGRESS_METABOX_KEY] = array('title' => YCD_PROGRESS_METABOX_TITLE, 'position' => 'normal', 'prioritet' => 'high');

        return $metaboxes;
    }

    public function mainOptions()
    {
        parent::mainOptions();
        add_meta_box('ycdSimpleOptions', __('Main options', YCD_TEXT_DOMAIN), array($this, 'mainView'), YCD_COUNTDOWN_POST_TYPE, 'normal', 'high');
    }

    public function mainView()
    {
        $typeObj = $this;
        require_once YCD_VIEWS_MAIN_PATH.'simpleMainView.php';
    }

    public function defaultOptions($defaultOptions)
    {
        return $defaultOptions;
    }

    public function renderLivePreview() {
        $typeObj = $this;
        require_once YCD_PREVIEW_VIEWS_PATH.'timerPreview.php';
    }

    private function timeUnitNumber($unit)
    {
        $default = '0';
        return '<div class="ycd-simple-countdown-time ycd-simple-countdown-number ycd-simple-countdown-'.esc_attr($unit).'-time">'.esc_attr($default).'</div>';
    }

    private function timeUnitText($unit)
    {
    	$uniteValue = 'ycd-simple-'.esc_attr($unit).'-text';
        $unitLabel = !empty($this->options[$uniteValue]) ?$this->options[$uniteValue] : $this->getOptionValue($uniteValue);
        return '<div class="ycd-simple-countdown-time  ycd-simple-countdown-label ycd-simple-countdown-'.esc_attr($unit).'-label">'.esc_attr($unitLabel).'</div>';
    }

    private function getStyles()
    {
        $style = '';
        $id = $this->getId();
        $numberFontSize = $this->getOptionValue('ycd-simple-numbers-font-size');
        $labelSize = $this->getOptionValue('ycd-simple-text-font-size');

		$textMarginTop = $this->getOptionValue('ycd-simple-text-margin-top');
	    $textMarginRight = $this->getOptionValue('ycd-simple-text-margin-right');
	    $textMarginBottom = $this->getOptionValue('ycd-simple-text-margin-bottom');
	    $textMarginLeft = $this->getOptionValue('ycd-simple-text-margin-left');

	    $numbersMarginTop = $this->getOptionValue('ycd-simple-numbers-margin-top');
	    $numbersMarginRight = $this->getOptionValue('ycd-simple-numbers-margin-right');
	    $numbersMarginBottom = $this->getOptionValue('ycd-simple-numbers-margin-bottom');
	    $numbersMarginLeft = $this->getOptionValue('ycd-simple-numbers-margin-left');

	    $horizontalAlign = $this->getOptionValue('ycd-simple-timer-horizontal-align');

        $enableBorder = $this->getOptionValue('ycd-simple-enable-unite-border');
        $borderWidth = $this->getOptionValue('ycd-simple-unite-border-width');
        $borderRadius = $this->getOptionValue('ycd-simple-unite-border-radius');
        $borderType = $this->getOptionValue('ycd-simple-unite-border-type');
        $borderColor = $this->getOptionValue('ycd-simple-unite-border-color');
        $uniteWidth = $this->getOptionValue('ycd-simple-unite-width');

        ob_start();
        ?>
        <style>
            .ycd-simple-content-wrapper-<?php echo (int)$id; ?> .ycd-simple-countdown-number,
            .ycd-simple-content-wrapper-<?php echo (int)$id; ?> .ycd-simple-timer-dots {
                font-size: <?php echo esc_attr($numberFontSize); ?>;
            }
            .ycd-simple-content-wrapper-<?php echo (int)$id; ?> .ycd-simple-countdown-number {
	            margin: <?php echo esc_attr($numbersMarginTop).' '.esc_attr($numbersMarginRight).' '.esc_attr($numbersMarginBottom).' '.esc_attr($numbersMarginLeft);?>
            }
            .ycd-simple-content-wrapper-<?php echo (int)$id; ?> .ycd-simple-countdown-label {
                font-size: <?php echo esc_attr($labelSize); ?>;
	            margin: <?php echo esc_attr($textMarginTop).' '.esc_attr($textMarginRight).' '.esc_attr($textMarginBottom).' '.esc_attr($textMarginLeft);?>
            }
            .ycd-simple-content-wrapper-<?php echo (int)$id; ?> {
	            text-align: <?php esc_attr_e($horizontalAlign);?>;
            }
            <?php if ($enableBorder): ?>
                .ycd-simple-current-unite {width: 100%;}
                .ycd-simple-content-wrapper-<?php echo (int)$id; ?> .ycd-simple-current-unite-wrapper {
                    border: <?php echo esc_attr( $borderWidth);?> <?php echo esc_attr($borderType);?> <?php echo esc_attr( $borderColor);?>;
                    border-radius: <?php echo esc_attr($borderRadius); ?>;
                    <?php if (!empty($uniteWidth)): ?>
                    width: <?php echo esc_attr($uniteWidth); ?>;    
                    <?php endif?>
                }
            <?php endif; ?>
        </style>
        <?php
        $style .= ob_get_contents();
        ob_end_clean();

        return apply_filters('YcdSimpleCountdownStyles', $style, $this);
    }

    private function render()
    {
        $unites = $this->getTimeUnites();
        $availableUnites = array_filter($unites, function($unite) {
            return $this->getOptionValue('ycd-simple-enable-'.esc_attr($unite));
        });
        $allowed_html = AdminHelper::getAllowedTags();
		$textToTop = $this->getOptionValue('ycd-text-to-top');
        $this->renderScripts();
        $lastUnite = end($availableUnites);
        $mode = $this->getMode();
        $id = $this->getId();
        $dotes = $this->getOptionValue('ycd-simple-timer-dotes');
        ob_start();
        ?>
        <div class="ycd-simple-mode-<?php echo esc_attr($mode); ?> ycd-simple-mode-<?php echo esc_attr($mode).'-'.esc_attr($id); ?>"><--
            --><?php foreach($unites as $key => $unite): ?>
                <?php
                    $hideDotsClassName = '';
                    $hideUnite = '';
                    if ($unite == $lastUnite) {
                        $hideDotsClassName = 'ycd-hide';
                    }
                    if (!in_array($unite, $availableUnites)) {
                        $hideUnite = 'ycd-hide';
                    }
                ?>
                <div class="ycd-simple-current-unite-wrapper ycd-simple-current-unite-<?php echo esc_attr($unite); ?> <?php echo esc_attr($hideUnite)?>">
                    <div class="ycd-simple-current-unite"><!-- -->
	                    <?php echo (esc_attr($textToTop) ? wp_kses($this->timeUnitText($unite), $allowed_html): ''); ?>
                        <?php echo wp_kses($this->timeUnitNumber($unite), $allowed_html); ?><!--
                        --><?php echo (!esc_attr($textToTop) ? wp_kses($this->timeUnitText($unite), $allowed_html): ''); ?>
                    </div><?php if ($unite != 'seconds'): ?> <?php if (!empty($dotes)): ?><div class="ycd-simple-timer-dots <?php echo esc_attr($hideDotsClassName); ?>"><?php echo esc_attr($dotes); ?></div> <?php endif; ?><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function renderScripts()
    {
	    if(YCD_PKG_VERSION > YCD_FREE_VERSION) {
		    if (!get_option('ycd-disable-google-fonts')) {
			    ScriptsIncluder::registerScript('ycdGoogleFonts.js');
			    ScriptsIncluder::enqueueScript('ycdGoogleFonts.js');
		    }
		    ScriptsIncluder::registerScript('CountdownProFunctionality.js');
		    ScriptsIncluder::enqueueScript('CountdownProFunctionality.js');
	    }
        $this->includeGeneralScripts();
        wp_enqueue_script('jquery');
        ScriptsIncluder::loadStyle('simpleCountdown.css');
        ScriptsIncluder::registerScript('YcdSimpleCountdown.js');
        ScriptsIncluder::localizeScript('YcdSimpleCountdown.js', 'YcdArgs', array('isAdmin' => is_admin()));
        ScriptsIncluder::enqueueScript('YcdSimpleCountdown.js');
    }

    private function getAllOptions()
    {
        $options = array();
        $optionNames = array('ycd-enable-simple-double-digits');
        $allDataOptions = $this->getAllSavedOptions();
        $generalOptionsData = $this->generalOptionsData();
        $unites = $this->getTimeUnites();

        foreach ($unites as $unite) {
            $enableName = 'ycd-simple-enable-'.esc_attr($unite);
            $labelName = 'ycd-simple-'.esc_attr($unite).'-text';
            $options[$enableName] = $this->getOptionValue($enableName);
            $options[$labelName] = $this->getOptionValue($labelName);
        }
        $options += $allDataOptions;
        $options += $generalOptionsData;

        foreach ($optionNames as $optionName) {
	        $options[$optionName] = $this->getOptionValue($optionName);
        }

        return $options;
    }

    public function addToContent()
    {
        add_filter('the_content', array($this, 'getTheContentFilter'),99999999, 1);
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
				$prepareOptions['ycd-simple-'.esc_attr(strtolower($unite)).'-text'] = $value;
			}
		}

		return $prepareOptions;
	}

    public function getViewContent()
    {

        $id = $this->getId();
        $options = $this->getAllOptions();
	    $options = $this->filterTranslations($options);
	    $this->options = $options;
        $options = json_encode($options);
        $allowed_html = AdminHelper::getAllowedTags();

        ob_start();
        ?>
        <div class="ycd-countdown-wrapper ycd-simple-content-wrapper ycd-simple-content-wrapper-<?php echo esc_attr($id); ?>">
            <div class="ycd-simple-time ycd-simple-container ycd-countdown-content-wrapper ycd-simple-wrapper-<?php echo esc_attr($id); ?>" data-options='<?php echo esc_attr($options); ?>' data-id="<?php echo esc_attr($id); ?>">
                <div class="ycd-simple-before-countdown"><?php echo wp_kses($this->getOptionValue('ycd-simple-countdown-before-countdown'), $allowed_html); ?></div>
                <?php echo wp_kses($this->render(), $allowed_html); ?>
                <div class="ycd-simple-after-countdown"><?php echo wp_kses($this->getOptionValue('ycd-simple-countdown-after-countdown'), $allowed_html); ?></div>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
	    $content .= $this->additionalFunctionality();
        $content .= $this->getStyles();

        return $content;
    }
}