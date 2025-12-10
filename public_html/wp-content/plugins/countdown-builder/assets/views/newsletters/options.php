<?php
use ycd\Countdown;
use ycd\AdminHelper;
$countdowns = ycd\Countdown::getCountdownsObj();
$idTitle = Countdown::shapeIdTitleData($countdowns);
$adminEmail = get_option('admin_email');
$savedOptions = array(
	'emails-per-minute' => YCD_FILTER_REPEAT_INTERVAL,
	'from-email' => $adminEmail,
	'ycd-newsletter-text' => '<p>Hello,</p>
	<p>Super excited to have you on board, we know you’ll just love us.</p>
	<p>Sincerely,</p>
	<p>[Blog name]</p>',
	'title' => '',
	'subject' => ''

);

if (!empty($_GET['postId'])) {
	$savedOptionsData = apply_filters('ycd_get_saved_newslatter', $_GET['postId']);
	$options = json_decode($savedOptionsData['options'], true);

	$savedOptions = array_merge($savedOptions, $options);
}


$subscriptionIdTitle = array();
?>
<div class="ycd-bootstrap-wrapper ycd-newsletter">
	<form method="POST" action="<?php echo admin_url();?>admin-post.php?action=save_newslatters">
	<?php
		if(function_exists('wp_nonce_field')) {
			wp_nonce_field('ycd_save_newslatters');
		}
	?>
	<?php if(!empty($_GET['saved'])) : ?>
		<div id="default-message" class="updated notice notice-success is-dismissible">
			<p><?php echo _e('Settings saved.', YCD_TEXT_DOMAIN);?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo _e('Dismiss this notice.', YCD_TEXT_DOMAIN);?></span></button>
		</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-md-6">
			<h3 style="margin-top: 0;">Save settings</h3>
		</div>
		<div class="col-md-6" Align="right">
			<input type="submit" class="button-primary yrm-button-primary" value="<?php echo 'Save Changes'; ?>">
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<input name="title" class="form-control" placeholder="Title" value="<?php echo esc_attr($savedOptions['title'])?>">
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-2" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div class="postbox">
							<div class="handlediv js-special-title" title="Click to toggle"><br></div>
							<h3 class="hndle ui-sortable-handle js-special-title">
								<span><?php _e('Newsletter Settings', YCD_TEXT_DOMAIN); ?></span>
							</h3>
							<div class="ycd-options-content">
								<div class="ycd-alert ycd-newsletter-notice ycd-alert-info fade in ycd-hide">
									<span><?php _e('You will receive an email notification after all emails are sent', YCD_TEXT_DOMAIN); ?>.</span>
								</div>
								<div class="row form-group">
									<label class="col-md-6 ycd-label-align-center-sm">
										<?php _e('Choose the countdown', YCD_TEXT_DOMAIN); ?>
									</label>
									<div class="col-md-6">
										<?php echo  AdminHelper::selectBox($idTitle, '', array('name' => 'ycd-countdowns', 'class' => 'js-ycd-select js-ycd-newsletter-forms')); ; ?>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="ycd-hide ycd-newsletter-validation ycd-newsletter-error"><?php _e('Select a countdown', YCD_TEXT_DOMAIN); ?>.</div>
									</div>
								</div>
								<div class="row form-group">
									<label class="col-md-6 ycd-label-align-center-sm" for="ycd-emails-in-flow">
										<?php _e('Emails to send in one flow per 1 minute', YCD_TEXT_DOMAIN); ?>
									</label>
									<div class="col-md-6">
										<input type="number" name="emails-per-minute" id="ycd-emails-in-flow" class="ycd-emails-in-flow form-control input-sm" value="<?php echo esc_attr($savedOptions['emails-per-minute']) ?>">
									</div>
								</div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="ycd-hide ycd-validation-error ycd-emails-in-flow-error"><?php _e('This field is required', YCD_TEXT_DOMAIN); ?>.</div>
                                    </div>
                                </div>
								<div class="row form-group">
									<label class="col-md-6 ycd-label-align-center-sm" for="ycd-newsletter-from-email">
										<?php _e('From email', YCD_TEXT_DOMAIN); ?>
									</label>
									<div class="col-md-6">
										<input type="email" id="ycd-newsletter-from-email" name="from-email" class="ycd-newsletter-from-email form-control input-sm" value="<?php echo esc_attr($savedOptions['from-email'])?>">
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="ycd-hide ycd-validation-error ycd-newsletter-validation ycd-newsletter-from-email-error"><?php _e('Please enter a valid email', YCD_TEXT_DOMAIN); ?>.</div>
									</div>
								</div>
								<div class="row form-group">
									<label class="col-md-6 ycd-label-align-center-sm" for="ycd-newsletter-subject">
										<?php _e('Email\'s subject', YCD_TEXT_DOMAIN); ?>
									</label>
									<div class="col-md-6">
										<input type="text" id="ycd-newsletter-subject" name="subject" class="ycd-newsletter-subject form-control input-sm" placeholder="Your subject here" value="<?php echo esc_attr($savedOptions['subject'])?>">
									</div>
								</div>
								<div class="row form-group">
									<label class="col-md-12 ycd-label-align-center-sm">
										<?php _e('Enter newsletter email template below', YCD_TEXT_DOMAIN); ?>
									</label>
								</div>
								<div class="row form-group">
									<div class="col-md-12">
										<?php
										$editorId = 'ycd-newsletter-text';
										$content = $savedOptions['ycd-newsletter-text'];
										$settings = array(
											'wpautop' => false,
											'tinymce' => array(
												'width' => '100%'
											),
											'textarea_rows' => '18',
											'media_buttons' => true
										);
										wp_editor($content, $editorId, $settings);
										?>
									</div>
								</div>
								<!-- <div class="row form-group">
									<div class="col-md-12">
										<input type="submit" class="btn btn-primary btn-sm js-send-newsletter" value="<?php _e('Send newsletter', YCD_TEXT_DOMAIN)?>">
										<img src="<?php echo YCD_COUNTDOWN_IMG_URL.'ajax.gif'; ?>" width="20px" class="ycd-hide ycd-js-newsletter-spinner">
									</div>
								</div> -->
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div id="post-body" class="metabox-holder">
				<div id="postbox-container-2" class="postbox-container ycd-float-none">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div class="postbox">
							<div class="handlediv js-special-title" title="Click to toggle"><br></div>
							<h3 class="hndle ui-sortable-handle js-special-title">
								<span><?php _e('Newsletter Shortcodes', YCD_TEXT_DOMAIN); ?></span>
							</h3>
							<div class="ycd-options-content">
								<div class="row form-group">
									<div class="col-md-6">
										<span><?php _e('[Blog name]', YCD_TEXT_DOMAIN); ?></span>
									</div>
									<div class="col-md-6">
										<?php _e('Your blog name', YCD_TEXT_DOMAIN); ?>
									</div>
								</div>
								<div class="row form-group">
									<div class="col-md-6">
										<span><?php _e('[User name]', YCD_TEXT_DOMAIN); ?></span>
									</div>
									<div class="col-md-6">
										<?php _e('Your user name', YCD_TEXT_DOMAIN); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if (!empty($_GET['postId'])): ?>
		<input type="hidden" name="id" value="<?php echo esc_attr($_GET['postId']);?>"/>
	<?php endif; ?>
	</form>
</div>