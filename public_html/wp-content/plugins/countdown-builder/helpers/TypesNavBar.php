<?php

namespace ycd;

class TypesNavBar
{
	public static function render()
	{
		ob_start();
		?>
		<div id="crontrol-header-ycd-groups">
			<ul class="nav nav-tab-wrapper">
				<?php
					$allowed_html = AdminHelper::getAllowedTags();
					$options = self::renderOptions();
					echo wp_kses($options, $allowed_html);
				?>
			</ul>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	private static function renderOptions()
	{
		global $YCD_TYPES;
		$groups = $YCD_TYPES['typesGroupList'];
		$activeGroupName = self::getActiveGroupName();
		$url = admin_url('edit.php?post_type='.esc_attr(YCD_COUNTDOWN_POST_TYPE).'&page='.esc_attr(YCD_COUNTDOWN_POST_TYPE));
		$urls = '';
		foreach ($groups as $groupKey => $groupTitle) {
			$activeClass = '';

			if ($activeGroupName == $groupKey) {
				$activeClass = 'nav-tab-active';
			}
			$urls .= '<a href="'.esc_attr($url).'&ycd_group_name='.esc_attr($groupKey).'" class="nav-tab '.esc_attr($activeClass).'">'.esc_attr($groupTitle).'</a>';
		}

		return $urls;
	}

	private static function getActiveGroupName()
	{
		$groupName = 'all';
		if (!empty($_GET['ycd_group_name'])) {
			$groupName = sanitize_text_field($_GET['ycd_group_name']);
		}

		return $groupName;
	}
}