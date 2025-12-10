<?php
namespace ycd;
use \YcdCountdownConfig;
use \DateTime;

class Ajax {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action('wp_ajax_ycd-switch', array($this, 'switchCountdown'));

		// review panel
		add_action('wp_ajax_ycd_dont_show_review_notice', array($this, 'dontShowReview'));
		add_action('wp_ajax_ycd_change_review_show_period', array($this, 'changeReviewPeriod'));

		// conditions builder
		add_action('wp_ajax_ycd_select2_search_data', array($this, 'select2Ajax'));
		add_action('wp_ajax_ycd_edit_conditions_row', array($this, 'conditionsRow'));
		add_action('wp_ajax_ycd_add_conditions_row', array($this, 'conditionsRow'));

	}

	public function changeReviewPeriod() {
		check_ajax_referer('ycdReviewNotice', 'ajaxNonce');
		$messageType = sanitize_text_field($_POST['messageType']);

		$timeDate = new DateTime('now');
		$timeDate->modify('+'.YCD_SHOW_REVIEW_PERIOD.' day');

		$timeNow = strtotime($timeDate->format('Y-m-d H:i:s'));
		update_option('YcdShowNextTime', $timeNow);
		$usageDays = get_option('YcdUsageDays');
		$usageDays += YCD_SHOW_REVIEW_PERIOD;
		update_option('YcdUsageDays', sanitize_text_field($usageDays));

		echo 1;
		wp_die();
	}

	public function dontShowReview() {
		check_ajax_referer('ycdReviewNotice', 'ajaxNonce');
		update_option('YcdDontShowReviewNotice', 1);

		echo 1;
		wp_die();
	}

	public function switchCountdown() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action.');
            wp_die();
        }
		check_ajax_referer('ycd_ajax_nonce', 'nonce');
		$postId = (int)$_POST['id'];
		$checked = $_POST['checked'] == 'true' ? '' : true;
		update_post_meta($postId, 'ycd_enable', $checked);
		wp_die();
	}

	 public function select2Ajax() {
		check_ajax_referer('ycd_ajax_nonce', 'nonce_ajax');
		YcdCountdownConfig::displaySettings();
		$postTypeName = sanitize_text_field($_POST['postType']);
		$search = sanitize_text_field($_POST['searchTerm']);
		$value = ! empty( $_REQUEST['include'] ) ? array_map( 'intval', $_REQUEST['include'] ) : null;

		$args	 = array(
			's'			 => $search,
			'post__in'		=> $value,
			'page'		 => ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : null,
			'posts_per_page' => 100,
			'post_type'	 => $postTypeName
		);

		$searchResults = AdminHelper::getPostTypeData($args);

		if (empty($searchResults)) {
			$results['items'] = array();
		}

		/*Selected custom post type convert for select2 format*/
		foreach ($searchResults as $id => $name) {
			$results['items'][] = array(
				'id'	=> $id,
				'text' => $name
			);
		}

		echo json_encode($results);
		wp_die();
	 }

	 public function conditionsRow() {
         if (!current_user_can('manage_options')) {
             wp_send_json_error('You do not have permission to perform this action.');
             wp_die();
         }
		check_ajax_referer('ycd_ajax_nonce', 'nonce');
		YcdCountdownConfig::displaySettings();
		$allowed_html = AdminHelper::getAllowedTags();
		$selectedParams = sanitize_text_field($_POST['selectedParams']);
		$conditionId = (int)$_POST['conditionId'];
		$childClassName = sanitize_text_field($_POST['conditionsClassName']);
        $listOfConditions = array('DisplayConditionBuilder');
        if (!in_array($childClassName, $listOfConditions)) {
            wp_die();
        }
		$childClassName = __NAMESPACE__.'\\'.esc_attr($childClassName);
		$obj = new $childClassName();

		$content =  $obj->renderConditionRowFromParam($selectedParams, $conditionId);

		echo wp_kses($content, $allowed_html);
		wp_die();
	 }
}

new Ajax();