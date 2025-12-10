<?php
namespace ycd;

abstract class AbstractTranslationManager {
	abstract function render();
	private $prefix = '';
	protected $translations = [];

	public function __construct($optionsPrefix) {
		$this->prefix = $optionsPrefix;

	}

	public function setTranslations($translations) {
		if (!empty($translations['{translationId}'])) {
			unset($translations['{translationId}']);
		}
		if (!empty($translations)) {
			$this->translations = $translations;
		}
	}

	public static function init($optionsPrefix = 'ycd-tr') {
		return new static($optionsPrefix );
	}

	public function translationRow($translation) {
		$labelName = array(
			'Years' => 'Years',
			'Months' => 'Months',
			'Days' => 'Days',
			'Hours' => 'Hours',
			'Minutes' => 'Minutes',
			'Seconds' => 'Seconds'
		);
		$language = AdminHelper::getLanguageIsoCodeList();
		$id = '{translationId}';
		if (!empty($translation['id'])) {
			$id = $translation['id'];
		}
		$isoCode = '{isoCode}';
		if (!empty($translation['language'])) {
			$isoCode = $language[$translation['language']];
		}
		ob_start();
		?>
		<div class="ycd-translation-wrapper ycd-translation-wrapper-<?php echo esc_attr($id); ?>" data-id="<?php echo esc_attr($id); ?>">
			<div class="ycd-translation-header">Language <span class="tab-language-tab"><?php echo  esc_attr($isoCode); ?></span><span class="delete-item" data-id="<?php echo esc_attr($id); ?>">X</span></div>
			<div class="ycd-translation-body ycd-hide">
				<div class="row">
					<div class="col-md-12">
						<div>
							<label><?php _e('Language', YCD_TEXT_DOMAIN)?></label>
						</div>
						<div>
							<?php echo AdminHelper::selectBox($language, esc_attr(@$translation['language']), array('name' => "$this->prefix[$id][language]", 'class' => 'ycd-select2 ycd-tr-language', 'data-id' => $id))?>
						</div>
					</div>
				</div>
				<div class="row">
					<?php foreach ($labelName as $label => $name): ?>
						<div class="col-md-2">
							<label for="<?php echo "$this->prefix[$id][$name]"; ?>"><?php _e($label, YCD_TEXT_DOMAIN)?></label>
							<input name="<?php echo "$this->prefix[$id][$name]"?>" id="<?php echo "$this->prefix[$id][$name]"; ?>" class="form-control" value="<?php echo esc_attr(@$translation[$name]); ?>">
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	public function translationTemplate($translation = array()) {

		ob_start();
		?>
		<div id="template-wrapper">
			<?php echo $this->translationRow($translation); ?>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	protected function addTranslationButton() {
		ob_start();
		?>
		<div class="row">
			<div class="col-md-12">
				<button class="btn btn-primary ycd-add-translation">Add Translation</button>
			</div>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}