<?php
use ycd\AdminHelper;
$ajaxNonce = wp_create_nonce("ycdNonce");

$pagenum = isset( $_GET['ycd-pagenum'] ) ? absint( $_GET['ycd-pagenum'] ) : 1;
global $wpdb;

$orderStatus = true;
$arrowVisibility = ' ycd-visibility-hidden';
$rotateClass = '';
$orderSql = 'desc';
$orderBySql = 'Order by id';
if (!empty($_GET['order'])) {
	$orderSql = esc_attr($_GET['order']);
	$arrowVisibility = '';
	if ( $_GET['order'] == 'asc') {
		$orderStatus = false;
		$rotateClass = 'ycd-rotate-180';
	}
}

if (!empty($_GET['orderby'])) {
	$orderBySql = 'ORDER BY '.esc_attr($_GET['orderby']);
}

$limit = YCD_NUMBER_PAGES; // number of rows in page
$offset = ($pagenum - 1) * $limit;
$total = 0;
$isFree = YCD_PKG_VERSION == YCD_FREE_VERSION;

if (!$isFree) {
	$total = $wpdb->get_var("SELECT COUNT(`id`) FROM {$wpdb->prefix}".YCD_COUNTDOWN_NEWSLETTER_TABLE);
}

$numOfPages = ceil($total/$limit);
$results = array();

if (!$isFree) {
	$query = $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}" . YCD_COUNTDOWN_NEWSLETTER_TABLE . " ORDER BY %s %s LIMIT %d, %d",
		$orderBySql,
		$orderSql,
		$offset,
		$limit
	);

	$results = $wpdb->get_results($query, ARRAY_A);
}

$base_url = admin_url('edit.php');
    
// Add query parameters
$query_params = array(
    'post_type' => 'ycdcountdown',
    'page' => 'ycdNewsletter',
    'ycdNewslatter' => 'create'
);
    
// Generate the full URL
$url = add_query_arg($query_params, $base_url);
$allowed_html = AdminHelper::getAllowedTags();
$proSpan = '';
$isPro = '';
if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
	$isPro = '-pro';
	$proSpan = '<span class="ycd-pro-span">'.__('pro', YCD_TEXT_DOMAIN).'</span>';
}

?>
<div class="ycd-bootstrap-wrapper">
	<div class="wrap">
		<h2 class="add-new-buttons">
            <?php _e('Newsletters', 'ycdCountdown'); ?>
			<div class="ycd-option-wrapper">
            	<a href="<?php echo ($isPro? YCD_COUNTDOWN_PRO_URL : esc_attr($url)) ?>" class="add-new-h2"><?php echo _e('Add New ', 'ycdCountdown'); echo wp_kses($proSpan, $allowed_html); ?></a>
            	<a href="<?php echo ($isPro? YCD_COUNTDOWN_PRO_URL : "#")?>" id="ycd-send-news-latter" class="add-new-h2"><?php echo _e('Send newslatter', 'ycdCountdown'); echo wp_kses($proSpan, $allowed_html); ?></a>
			</div>
            <?php // echo ReadMoreAdminHelper::reportIssueButton(); ?>
        </h2>
	</div>
    <div class="ycd-send-newslatter-wrapper  ycd-hide">
		<div class="form-group">
			<label for="ycd-newsletters-list">Select Newsletter</label>
			<div class="select-and-button">
				<?php if (!empty($results)): ?>
					<select id="ycd-newsletters-list" class="form-control">
						<?php foreach($results as $result): ?>
							<option value="<?php echo esc_attr($result['id']); ?>"><?php echo esc_attr($result['title']); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="submit" class="btn btn-primary btn-sm js-send-newsletter"
						value="<?php _e('Send Newsletter', YCD_TEXT_DOMAIN); ?>">
				<?php else: ?>
					<a href="<?php echo esc_attr($url); ?>" class="btn btn-link"><?php echo __('Create Newsletter', 'ycdCountdown'); ?></a>
				<?php endif; ?>
				<img src="<?php echo YCD_COUNTDOWN_IMG_URL . 'ajax.gif'; ?>" width="20px"
					class="ycd-hide ycd-js-newsletter-spinner" alt="Loading...">
			</div>
		</div>
	</div>
	<div class="expm-wrapper">
		<?php if(YCD_PKG_VERSION == YCD_FREE_VERSION): ?>
			<div class="main-view-upgrade main-upgreade-wrapper">
				<?php // echo ReadMoreAdminHelper::upgradeButton(); ?>
			</div>
		<?php endif;?>
		<table class="table table-bordered expm-table">
			<tr>
				<td class="manage-column column-id sortable"><span>Id <span class="ycd-sorting-indicator <?php echo esc_attr($rotateClass).esc_attr($arrowVisibility); ?>" data-orderby="id" data-order="<?php echo esc_attr($orderStatus); ?>"></span></span></td>
				<td><?php _e('Title', 'ycdCountdown')?></td>
				<td><?php _e('Options', 'ycdCountdown')?></td>
			</tr>

			<?php if(empty($results)) { ?>
				<tr>
					<td colspan="4"><?php _e('No Data', 'ycdCountdown')?></td>
				</tr>
			<?php }
			else {
				foreach ($results as $result) { ?>
                
					<?php
                  
					$id = (int)$result['id'];
                    $url .= '&postId='.$id;
					$title = esc_attr($result['title']);
					if (empty($title)) {
						$title = __('(no title)');
					}
					$type = "far";

					?>
					<tr>
						<td><?php echo esc_attr($id); ?></td>
						<td><a href="<?php echo esc_attr( $url); ?>"><?php echo esc_attr($title); ?></a></td>
						<td class="">
							<a class="ycd-crud ycd-edit glyphicon glyphicon-edit" href="<?php echo esc_attr($url); ?>"></a>
							<a class="ycd-crud ycd-type-delete-link glyphicon glyphicon-remove" data-type="far" data-id="<?php echo esc_attr($id);?>" href="<?php echo admin_url()."admin-post.php?action=ycd_typeDelete&newsletter=".esc_attr($id).""?>"></a>
						</td>
					</tr>
				<?php } ?>

			<?php } ?>
			<tr>
				<td>Id</td>
				<td><?php _e('Title', 'ycdCountdown')?></td>
				<td><?php _e('Options', 'ycdCountdown')?></td>
			</tr>
		</table>
		<?php
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'ycd-pagenum', '%#%' ),
			'format' => '',
			'prev_text' => __( '&laquo;', 'text-domain' ),
			'next_text' => __( '&raquo;', 'text-domain' ),
			'total' => $numOfPages,
			'current' => $pagenum
		) );

		if ( $page_links ) {
			echo '<div class="ycd-tablenav"><div class="ycd-tablenav-pages">' . wp_kses($page_links, ReadMoreAdminHelper::getAllowedTags()) . '</div></div>';
		}
		?>
	</div>
</div>