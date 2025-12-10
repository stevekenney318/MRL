<?php
namespace ycd;

class DisplayRuleChecker {
    private $typeObj;

    public function setTypeObj($typeObj) {
        $this->typeObj = $typeObj;
    }

    public function getTypeObj() {
        return $this->typeObj;
    }

    public static function isAllow($countdownObj) {
        $obj = new self();
        $obj->setTypeObj($countdownObj);
        $isDisplayOn = $countdownObj->getOptionValue('ycd-countdown-display-on');

        if(!$isDisplayOn) {
            return $isDisplayOn;
        }

        $status = $obj->checkDisplaySettings();

        return $status;
    }

    private function checkDisplaySettings() {
        $countdownObj = $this->getTypeObj();

        $settings = $countdownObj->getOptionValue('ycd-display-settings');

        if(empty($settings)) {
            return false;
        }
        $status = array();

        foreach ($settings as $setting) {

            if($setting['key1'] == 'everywhere') {
                return true;
            }

            $isAllowSettings = $this->checkSetting($setting);
            $status[] = $isAllowSettings;
        }

        return (in_array('is1', $status) && !in_array('isnot1', $status));
    }

    private function checkSetting($setting) {
        global $post;
        if (empty($post)) {
        	return false;
        }
        $postId = $post->ID;
        $post_type = get_post_type($postId);
        $key1 = $setting['key1'];
        $key2 = @$setting['key2'];
        $key3 = @$setting['key3'];

        if('selected_'.esc_attr($post_type) == $key1) {

            if(in_array($post->ID, array_keys($key3))) {
                return ($key2.'1');
            }
            return '';
        }
        else if (strpos($key1, 'categories_') === 0 ) {
	        $values = array();

	        if (!empty($key3)) {
		        $values = array_values($key3);
	        }

	        global $post;
	        // get current all taxonomies of the current post
	        $taxonomies = get_post_taxonomies($post);

	        foreach ($taxonomies as $taxonomy) {
		        // get current post all categories
		        $terms = get_the_terms($post->ID, $taxonomy);

		        if (!empty($terms)) {
			        foreach ($terms as $term) {
				        if (empty($term)) {
					        continue;
				        }
				        if (in_array($term->term_id, $values)) {
					        return ($key2.'1');
				        }
			        }
		        }
	        }
        }
        elseif ($key1 === "type_page") {
	        $pageTypes = $key3;
	        foreach ($pageTypes as $pageType) {

		        if ($pageType == 'is_home_page') {
			        if (is_front_page() && is_home()) {
				        // default homepage
				        return ($key2.'1');
			        }
			        else if (is_front_page()) {
				        // static homepage
				        return ($key2.'1');
			        }
		        }
		        else if (function_exists($pageType)) {
			        if ($pageType == 'is_home') {
				        return ($key2.'1');
			        }
			        else if ($pageType == 'is_search') {
				        return ($key2.'1');
			        }
			        else if ($pageType == 'is_shop') {
				        return ($key2.'1');
			        }
		        }

		        return  '';
	        }
        }
        else if ($key1 == 'all_tags') {
	        if (has_tag()) {
		        return ($key2.'1');
	        }
        }
        else if ($key1 == 'selected_tags') {
	        $tagsObj = wp_get_post_tags($postId);
	        $selectedTags = array_values($key3);

	        foreach ($tagsObj as $tagObj) {
		        if (in_array($tagObj->slug, $selectedTags)) {
			        return ($key2.'1');
		        }
	        }
        }
        else if ($key1 == 'shop_page') {
	        if (function_exists('is_shop') && is_shop()) {
		        return ($key2.'1');
	        }
        }
        else if ($key1 == 'cart_page') {
	        if (function_exists('is_cart') && is_cart()) {
		        return ($key2.'1');
	        }
        }
        else if ($key1 == 'account_page') {
	        if (function_exists('is_account_page') && is_account_page()) {
		        return ($key2.'1');
	        }
        }

        if('all_'.esc_attr($post_type) == $key1) {
            return ($key2.'1');
        }

        return '';
    }
}