<!DOCTYPE html>
<html lang="en">
<head>
	<?php $allowed_html = ycd\AdminHelper::getAllowedTags();?>
    <meta charset="utf-8">
    <title><?php echo wp_kses(apply_filters('YcdComingSoonPageTitle', ''), $allowed_html); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php echo wp_kses(apply_filters('YcdComingSoonPageHeaderContent', ''), $allowed_html); ?>
    <?php wp_head(); ?>
</head>
<body class="ycd-body">
<div style="text-align: center">
    <div class="ycd-coming-soon-before-header"><?php echo wp_kses(apply_filters('YcdComingSoonPageBeforeHeader', '', $comingSoonThis), $allowed_html); ?></div>
    <div class="ycd-coming-soon-header"><?php echo wp_kses(apply_filters('YcdComingSoonPageHeader', '', $comingSoonThis), $allowed_html); ?></div>
    <div class="ycd-coming-soon-after-header"><?php echo wp_kses(apply_filters('YcdComingSoonPageAfterHeader', '', $comingSoonThis), $allowed_html); ?></div>
    <div class="ycd-coming-soon-before-message"><?php echo wp_kses(apply_filters('YcdComingSoonPageBeforeMessage', '', $comingSoonThis), $allowed_html); ?></div>
    <div class="ycd-coming-soon-message"><?php echo wp_kses(apply_filters('YcdComingSoonPageMessage', '', $comingSoonThis), $allowed_html); ?></div>
    <div class="ycd-coming-soon-after-message"><?php echo wp_kses(apply_filters('YcdComingSoonPageAfterMessage', '', $comingSoonThis), $allowed_html); ?></div>
</div>
<?php wp_footer(); ?>
</body>
</html>
