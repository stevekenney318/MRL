<?php
if (!empty($_GET['ycdNewslatter'])) {
    require_once(dirname(__FILE__).'/options.php');
}
else {
    require_once(dirname(__FILE__).'/list.php');
}
