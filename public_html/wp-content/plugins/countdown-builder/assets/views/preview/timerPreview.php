<?php
use ycd\AdminHelper;
$allowed_html = AdminHelper::getAllowedTags();
	$constnet =  $typeObj->getViewContent();
	echo wp_kses($constnet, $allowed_html);