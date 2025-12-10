<?php
use ycd\AdminHelper;
$allowed_html = AdminHelper::getAllowedTags();
	$constnet =  $typeObj->getViewContent();
	echo __($constnet);
?>