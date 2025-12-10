<?php
use ycd\TranslationManager;
use ycd\AdminHelper;
require_once (YCD_CLASSES_PATH.'/translation/TranslationManager.php');

$allowed_html = AdminHelper::getAllowedTags();
$translation = TranslationManager::init();
$translation->setTranslations($this->getOptionValue('ycd-tr'));

echo wp_kses($translation->translationTemplate(), $allowed_html);
echo wp_kses($translation->render(), $allowed_html);

