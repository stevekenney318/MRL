<div class="row form-group">
	<div class="col-md-12">
		<?php
		$editorId = 'ycd-sticky-text';
		$settings = array(
			'textarea_rows' => '18',
		);
		$content = $this->getOptionValue($editorId);
		?>
		<?php wp_editor($content, $editorId, $settings); ?>
	</div>
</div>
<div class="row form-group">
	<div class="col-md-6">
		<label class="ycd-label-of-input" for="ycd-sticky-text-color"><?php _e('Color', YCD_TEXT_DOMAIN); echo wp_kses($proSpan, $allowed_html); ?></label>
	</div>
	<div class="col-md-5 ycd-option-wrapper<?php echo esc_attr($isPro); ?>">
		<div class="minicolors minicolors-theme-default minicolors-position-bottom minicolors-position-left">
			<input type="text" id="ycd-sticky-text-color" placeholder="<?php _e('Select color', YCD_TEXT_DOMAIN)?>" name="ycd-sticky-text-color" class="minicolors-input form-control js-ycd-sticky-color" value="<?php echo esc_attr($this->getOptionValue('ycd-sticky-text-color')); ?>">
		</div>
	</div>
</div>