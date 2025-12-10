<?php
namespace ycd;

class Tickbox {
	private $isEditorButton = false;
	private $isLoadedMediaData = false;

	public function __construct($isEditorButton = false, $isLoadedMediaData = false) {

		if (get_option('ycd-hide-editor-media-button')) {
			return false;
		}

		if (isset($isEditorButton)) {
			$this->isEditorButton = $isEditorButton;
		}
		if (isset($isLoadedMediaData)) {
			$this->isLoadedMediaData = $isLoadedMediaData;
		}
		$this->mediaButton();
		if(!$this->isLoadedMediaData) {
			add_action( 'admin_footer', array($this, 'ycdAdminTickBox'));
		}
	}

	private function mediaButton() {
		global $pagenow, $typenow;
		$output = '';

		$allowed_html = AdminHelper::getAllowedTags();
		/** Only run in post/page creation and edit screens */
		if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'download' ) {
			wp_enqueue_script('jquery-ui-dialog');
			wp_register_style('ycd_jQuery_ui', YCD_COUNTDOWN_CSS_URL.'jQueryDialog/jquery-ui.css');
			wp_enqueue_style('ycd_jQuery_ui');

			$output = '<a href="javascript:void(0);" class="button ycd-countdown-thickbox" style="padding-left: .4em;"><span class="wp-media-buttons-icon dashicons dashicons-clock" id="ycd-media-button" style="margin-right: 5px !important;"></span>'  . __('Countdown Builder', YCD_TEXT_DOMAIN) . '</a>';

		}

		if (!$this->isEditorButton) {
			echo wp_kses($output, $allowed_html);
		}
	}


	function ycdAdminTickBox() {
		global $pagenow, $typenow;

		// Only run in post/page creation and edit screens
		if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'download' ) { ?>
			<script type="text/javascript">
				function insertCountdownDownload() {
					var id = jQuery('.ycd-countdowns').val();

					// Return early if no download is selected
					if (!id) {
						alert('<?php _e('Select your countdown', YCD_TEXT_DOMAIN); ?>');
						return;
					}

					function getTextTabSelectionText() // javascript
					{
						// obtain the object reference for the <textarea>
						var txtarea = document.querySelector("textarea[name='content']");
						// obtain the index of the first selected character
						var start = txtarea.selectionStart;
						// obtain the index of the last selected character
						var finish = txtarea.selectionEnd;
						// obtain the selected text
						var sel = txtarea.value.substring(start, finish);
						// do something with the selected content

                        return sel;
					}

					if (tinyMCE.activeEditor == null) {
						var selection = getTextTabSelectionText();
                    }
                    else {
						var selection = tinyMCE.activeEditor.selection.getContent();
                    }

					// Send the shortcode to the editor
					window.send_to_editor('[ycd_countdown id="'+id+'"]'+selection+'[/ycd_countdown]');
					jQuery('#ycd-countdown-dialog').dialog('close')
				}
				jQuery(document).ready(function ($) {
					$('.ycd-countdown-thickbox').bind('click', function(e) {
						e.preventDefault();
						jQuery('#ycd-countdown-dialog').dialog({
                            width: 450,
                            modal: true,
                            title: "Insert the shortcode",
                            dialogClass: "ycd-countdown-builder"
                        });
					});
				});
			</script>
			<?php
			$popups = Countdown::getCountdownsObj();
			$idTitle = Countdown::shapeIdTitleData($popups);

			?>

			<div id="ycd-countdown-dialog" style="display: none;">
				<style>
					.ycd-countdown-builder .ui-dialog-titlebar-close {
						font-size: 0;
						align-items: center;
						margin-right: 4px;
						display: inline-flex;
						justify-content: center;
						margin-right: 10px;
					}
					.ycd-countdown-builder .ui-icon-closethick {
						background-position: -98px -128px !important;
					}
					.ycd-countdown-builder .ui-dialog-titlebar-close:hover {
						padding: 1px!important;
					}
				</style>
				<div class="wrap" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
					<p>
						<label><?php _e('Select countdown', YCD_TEXT_DOMAIN); ?>:</label>
                        <?php if(!empty($idTitle)): ?>
						    <?php
	                            $data = HelperFunction::createSelectBox($idTitle, '', array('name' => 'ycdOption', 'class' => 'ycd-countdowns'));
	                            echo wp_kses($data, AdminHelper::getAllowedTags())
                            ?>
                        <?php else: ?>
                            <a href="<?php echo esc_attr(YCD_ADMIN_URL).'edit.php?post_type='.esc_attr(YCD_COUNTDOWN_POST_TYPE).'&page='.esc_attr(YCD_COUNTDOWN_POST_TYPE); ?>"><?php _e('Add New Countdown', YCD_TEXT_DOMAIN); ?></a>
                        <?php endif; ?>
					</p>
					<p class="submit">
						<input type="button" id="ycd-insert-download" class="button-primary" value="<?php _e('Insert', YCD_TEXT_DOMAIN)?>" onclick="insertCountdownDownload();" />
						<a id="edd-cancel-download-insert" class="button-secondary" onclick="jQuery('#ycd-dialog').dialog('close')();"><?php _e( 'Cancel', 'easy-digital-downloads' ); ?></a>
					</p>
				</div>
			</div>
			<?php
		}
	}
}