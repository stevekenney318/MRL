<?php
namespace ycd;

class Floating {
	private $typeObj;
	private $content;

	public function __construct($typeObj, $content) {
		$this->typeObj = $typeObj;
		$this->content = $content;

	}

	public function __toString()
	{
		$typeObj = $this->typeObj;
		$allowedTags = AdminHelper::getAllowedTags();
		$position = $typeObj->getOptionValue('ycd-countdown-floating-position');
		$explode = explode('_', $position);
		$top = 'auto';
		$right = 'auto';
		$bottom = 'auto';
		$left = 'auto';
		$additionalStyles = '';
		$fontSize = 'inherit';
		$fontWeight = 'inherit';
		$contentBgColor = 'inherit';

		if(in_array('top', $explode)) {
			$top = $typeObj->getOptionValue('ycd-countdown-floating-position-top');
			$additionalStyles = 'display: block;';
		}
		if(in_array('right', $explode)) {
			$right = $typeObj->getOptionValue('ycd-countdown-floating-position-right');
		}
		if(in_array('bottom', $explode)) {
			$additionalStyles = 'display: block;';
			$bottom = $typeObj->getOptionValue('ycd-countdown-floating-position-bottom');
		}
		if(in_array('left', $explode)) {
			$left = $typeObj->getOptionValue('ycd-countdown-floating-position-left');
		}
		if(in_array('center', $explode) && (in_array($position, array('top_center', 'bottom_center')))) {
			$left = 0;
			$right = 0;
			$additionalStyles = 'display: block;';
		}
		if(in_array('center', $explode) && (in_array($position, array('right_center', 'left_center')))) {
			$top = "50%";
		}
		if ($position == 'right_center' || $position == 'left_center') {
			$additionalStyles = 'transform: translate(0, -50%);';
		}

		if ($typeObj->getOptionValue('ycd-countdown-floating-text-size')) {
			$fontSize = $typeObj->getOptionValue('ycd-countdown-floating-text-size');
		}
		if ($typeObj->getOptionValue('ycd-countdown-floating-font-weight')) {
			$fontWeight = $typeObj->getOptionValue('ycd-countdown-floating-font-weight');
		}
		if ($typeObj->getOptionValue('ycd-countdown-floating-text-content-bg-color')) {
			$contentBgColor = $typeObj->getOptionValue('ycd-countdown-floating-text-content-bg-color');
		}

		$color = $typeObj->getOptionValue('ycd-countdown-floating-text-color');

		$paddingTop = $typeObj->getOptionValue('ycd-countdown-floating-padding-top');
		$paddingRight = $typeObj->getOptionValue('ycd-countdown-floating-padding-right');
		$paddingBottom = $typeObj->getOptionValue('ycd-countdown-floating-padding-bottom');
		$paddingLeft = $typeObj->getOptionValue('ycd-countdown-floating-padding-left');
		
		$textPaddingTop = $typeObj->getOptionValue('ycd-countdown-floating-text-padding-top');
		$textPaddingRight = $typeObj->getOptionValue('ycd-countdown-floating-text-padding-right');
		$textPaddingBottom = $typeObj->getOptionValue('ycd-countdown-floating-text-padding-bottom');
		$textPaddingLeft = $typeObj->getOptionValue('ycd-countdown-floating-text-padding-left');

		$textBgColor = $typeObj->getOptionValue('ycd-countdown-floating-text-bg-color');

		$expandButton = "<div 
			style='
				text-align: center; 
				cursor: pointer;
				font-size: ".esc_attr($fontSize).";
				font-weight: ".esc_attr($fontWeight).";
				color: ".esc_attr($color)."; 
		'>
			<span 
				class='ycd-floating-toggle' 
				data-change-status='".esc_attr($typeObj->getOptionValue('ycd-countdown-floating-close-text-status'))."'
				data-close-text='".esc_attr($typeObj->getOptionValue('ycd-countdown-floating-close-text'))."'
				data-text='".esc_attr($typeObj->getOptionValue('ycd-countdown-floating-text'))."'
				data-sound-status='".esc_attr($typeObj->getOptionValue('ycd-countdown-enable-float-sound'))."'
				data-sound-url='".esc_attr($typeObj->getOptionValue('ycd-countdown-enable-float-sound-url'))."'
			>".esc_attr($typeObj->getOptionValue('ycd-countdown-floating-text'))."</span></div>";

		$all = '<div class="ycd-floating-wrapper hidden-floating" style="
			position: fixed; 
			display: flex;
			align-items: center; 
			 transition: height 3s ease;
			top: '.esc_attr($top).'; 
			right: '.esc_attr($right).'; 
			bottom: '.esc_attr($bottom).'; 
			left: '.esc_attr($left).'; '.esc_attr($additionalStyles).';
			z-index: 9999;">';
		if(in_array('bottom', $explode) || $position == 'right_center') {
			$all .= $expandButton;
		}
		$all .= '<div class="ycd-floating-content">';
		$all .= wp_kses($this->content, $allowedTags);

		$all .= '</div>';
		if((in_array('top', $explode) || !in_array('bottom', $explode) || $position == 'left_center') && !($position == 'right_center')) {
			$all .= $expandButton;
		}
		$all .= '</div>';
		$all .= "<style>
			.hidden-floating .ycd-countdown-content-wrapper,
			.hidden-floating .ycd-circle-wrapper canvas {height: 0;display: none; }
			.time_circles > div {height: 0;}
			.ycd-floating-content {
				background-color: ".esc_attr($contentBgColor).";
				padding: ".esc_attr($paddingTop)." ".esc_attr($paddingRight)." ".esc_attr($paddingBottom)."  ".esc_attr($paddingLeft).";
			}
			.ycd-floating-toggle {
				background-color: ".esc_attr($textBgColor).";
				padding: ".esc_attr($textPaddingTop)." ".esc_attr($textPaddingRight)." ".esc_attr($textPaddingBottom)."  ".esc_attr($textPaddingLeft).";
			}
			.hidden-floating .ycd-floating-content { padding: 0 !important;}
		</style>";

		return $all;
	}
}