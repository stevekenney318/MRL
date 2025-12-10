<?php
namespace ycd;
use \YcdCountdownOptionsConfig;
use \YcdShowReviewNotice;
use \YcdCountdownConfig;

class Actions {
	public $customPostTypeObj;
	private $isLoadedMediaData = false;

	public function isLoadedMediaData() {
		return $this->isLoadedMediaData;
	}

	public function setIsLoadedMediaData($isLoadedMediaData) {
		$this->isLoadedMediaData = $isLoadedMediaData;
	}

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action('admin_init', array($this, 'userRolesCaps'));
		add_action('init', array($this, 'postTypeInit'));
		add_action('admin_menu', array($this, 'addSubMenu'));
		add_action('save_post', array($this, 'savePost'), 10, 3);
		add_shortcode('ycd_countdown', array($this, 'shortcode'));
		add_action('manage_'.YCD_COUNTDOWN_POST_TYPE.'_posts_custom_column' , array($this, 'tableColumnValues'), 10, 2);
		add_action('add_meta_boxes', array($this, 'generalOptions'));
		add_action('widgets_init', array($this, 'loadWidgets'));
		add_action('media_buttons', array($this, 'ycdMediaButton'), 11);
		add_action('admin_post_ycdSaveSettings', array($this, 'saveSettings'), 10, 1);
		add_action('admin_post_ycdComingSoon', array($this, 'comingSoon'), 10, 1);
		add_action('wp_head', array($this, 'wpHead'), 10, 1);
        add_action('admin_head', array($this, 'adminHead'));
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		add_filter('mce_external_plugins', array($this, 'editorButton'));
		add_action("admin_menu", array($this, 'adminMenu'));
		add_action('admin_action_ycd_duplicate_post_as_draft', array($this, 'duplicatePostSave'));
        add_filter('pll_get_post_types', array($this, 'addCptToPll'), 10, 2);
    }

    public function addCptToPll($postTypes, $hide)
    {
        $postTypes[YCD_COUNTDOWN_POST_TYPE] = YCD_COUNTDOWN_POST_TYPE;

        return $postTypes;
    }

	public function duplicatePostSave() {
		
		global $wpdb;
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
			wp_die('No post to duplicate has been supplied!');
		}
		/*
		 * Nonce verification
		 */
		if ( !isset( $_GET['duplicate_nonce'] ) || !wp_verify_nonce( $_GET['duplicate_nonce'], YCD_COUNTDOWN_POST_TYPE ) )
			return;
		
		/*
		 * get the original post id
		 */
		$post_id = (int)(isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
		/*
		 * and all the original post data then
		 */
		$post = get_post( $post_id );
		
		/*
		 * if you don't want current user to be the new post author,
		 * then change next couple of lines to this: $new_post_author = $post->post_author;
		 */
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID;
		
		/*
		 * if post data exists, create the post duplicate
		 */
		if (isset( $post ) && $post != null) {
			
			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => sanitize_text_field($post->comment_status),
				'ping_status'    => sanitize_text_field($post->ping_status),
				'post_author'    => sanitize_text_field($new_post_author),
				'post_content'   => sanitize_text_field($post->post_content),
				'post_excerpt'   => sanitize_text_field($post->post_excerpt),
				'post_name'      => sanitize_text_field($post->post_name),
				'post_parent'    => sanitize_text_field($post->post_parent),
				'post_password'  => sanitize_text_field($post->post_password),
				'post_status'    => 'publish',
				'post_title'     => sanitize_text_field($post->post_title).'(clone)',
				'post_type'      => sanitize_text_field($post->post_type),
				'to_ping'        => sanitize_text_field($post->to_ping),
				'menu_order'     => sanitize_text_field($post->menu_order)
			);
			
			/*
			 * insert the post by wp_insert_post() function
			 */
			$new_post_id = wp_insert_post( $args );
			
			/*
			 * get all current post terms ad set them to the new post draft
			 */
			$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
				wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}
			
			/*
			 * duplicate all post meta just in two SQL queries
			 */
			$post_meta_infos = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=%d", $post_id));
			if (count($post_meta_infos)!=0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ($post_meta_infos as $meta_info) {
					$meta_key = $meta_info->meta_key;
					if( $meta_key == '_wp_old_slug' ) continue;
					$meta_value = $meta_info->meta_value;
					$sql_query_sel[]= $wpdb->prepare("SELECT %d, %s, %s", $new_post_id, $meta_key, $meta_value);
				}
				$sql_query.= implode(" UNION ALL ", $sql_query_sel);

				$wpdb->query($sql_query);
			}
			
			
			/*
			 * finally, redirect to the edit post screen for the new draft
			 */
			wp_redirect(admin_url('edit.php?post_type=' . YCD_COUNTDOWN_POST_TYPE));
			exit;
		} else {
			wp_die('Post creation failed, could not find original post: ' . esc_attr($post_id));
		}
	}
    
    public function adminMenu() {
	    if (!get_option('ycd-hide-coming-soon-menu')) {
		    add_menu_page(__('Coming Soon', YCD_TEXT_DOMAIN), __('Coming Soon', YCD_TEXT_DOMAIN), 'manage_options', 'ycdComingSoon', array($this, 'comingSoonMenu'), 'dashicons-clock', 11);
	    }
	}
    
    public function comingSoonMenu() {
	    $comingSoonObj = new ComingSoon();
	    $comingSoonObj->adminView();
    }

	public function editorButton($buttons) {
		if (get_option('ycd-hide-editor-media-button')) {
			return $buttons;
		}
		$isLoadedMediaData = $this->isLoadedMediaData();
		new Tickbox(true, $isLoadedMediaData);
		$buttons['countdownBuilder'] = plugins_url('/../assets/js/admin/ycd-tinymce-plugin.js',__FILE__ );

		return $buttons;
	}
	
	public function enqueueScripts() {
        $includeManager = new IncludeManager();
	}
	
	public function adminHead() {
        $script = '';

        $script = "
			<script>jQuery(document).ready(function() {jQuery('#menu-posts-ycdcountdown a[href*=\"page=ycdIdeas\"]').css({color: '#55efc3', 'font-size': '17px'});jQuery('#menu-posts-ycdcountdown a[href*=\"page=ycdIdeas\"]').bind('click', function(e) {e.preventDefault(); window.open('https://wordpress.org/support/plugin/countdown-builder/')}) });</script>";

		$script .= "<script>jQuery(document).ready(function() {jQuery('#menu-posts-ycdcountdown a[href*=\"page=supports\"]').css({color: 'yellow'});jQuery('#menu-posts-ycdcountdown a[href*=\"page=supports\"]').bind('click', function(e) {e.preventDefault(); window.open('https://wordpress.org/support/plugin/countdown-builder/')}) });</script>";
        if (YCD_PKG_VERSION == YCD_FREE_VERSION) {
            $script .=  '<script>';
            $script .= "jQuery(document).ready(function() {
				jQuery('[href*=\"ycdSubscribers\"]').attr(\"href\", '".YCD_COUNTDOWN_PRO_URL."').attr('target', '_blank');
				
			});";
            $script .= '</script>';
        }
		echo wp_kses($script, AdminHelper::getAllowedTags());
	}

	public static function wpHead() {
		///echo esc_html(YcdCountdownConfig::headerScript());
	}

	public function userRolesCaps() {
		YcdCountdownOptionsConfig::init();
		$userSavedRoles = AdminHelper::getCountdownPostAllowedUserRoles();

		foreach ($userSavedRoles as $theRole) {
			$role = get_role($theRole);
			if (!$role) {
				continue;
			}
			$role->add_cap('read');
			$role->add_cap('read_post');
			$role->add_cap('read_private_ycd_countdowns');
			$role->add_cap('edit_ycd_countdown');
			$role->add_cap('edit_ycd_countdowns');
			$role->add_cap('edit_others_ycd_countdowns');
			$role->add_cap('edit_published_ycd_countdowns');
			$role->add_cap('publish_ycd_countdowns');
			$role->add_cap('delete_ycd_countdowns');
			$role->add_cap('delete_published_posts');
			$role->add_cap('delete_others_ycd_countdowns');
			$role->add_cap('delete_private_ycd_countdowns');
			$role->add_cap('delete_private_ycd_countdown');
			$role->add_cap('delete_published_ycd_countdowns');

			// For countdown builder sub-menus and terms
			$role->add_cap('ycd_manage_options');
		}

		return true;
	}


	function ycdMediaButton() {
		$this->setIsLoadedMediaData(true);
		new Tickbox();
	}

	public function loadWidgets() {
		register_widget('ycd_countdown_widget');
	}

	public function postTypeInit() {
		$this->revieNotice();
		$this->customPostTypeObj = new RegisterPostType();
		$currentPostType = AdminHelper::getCurrentPostType();

        if ($currentPostType == YCD_COUNTDOWN_POST_TYPE) {
			YcdCountdownConfig::displaySettings();
		}
		
		if (YCD_PKG_VERSION != YCD_FREE_VERSION) {
			new Updates();
        }
	}

	private function revieNotice() {
		 add_action('admin_notices', array($this, 'showReviewNotice'));
		 add_action('network_admin_notices', array($this, 'showReviewNotice'));
		 add_action('user_admin_notices', array($this, 'showReviewNotice'));
	}

	public function showReviewNotice() {
		$allowedTags = AdminHelper::getAllowedTags();
		echo wp_kses(new YcdShowReviewNotice(), $allowedTags);
	}

	public function addSubMenu() {
		$this->customPostTypeObj->addSubMenu();
	}

	public function savePost($postId, $post, $update) {
		if(!$update) {
			return false;
		}
		
		$postData = Countdown::parseCountdownDataFromData($_POST);
		$postData = apply_filters('ycdSavedData', $postData);
		
		if(empty($postData)) {
			return false;
		}
		$postData['ycd-post-id'] = $postId;

		if (!empty($postData['ycd-type'])) {
			$type = sanitize_text_field($postData['ycd-type']);
			$allowedTypes = Countdown::countdownTypes();
			if (!in_array($type, $allowedTypes, true)) {
				return false;
			}
			$typePath = Countdown::getTypePathFormCountdownType($type);
			$className = Countdown::getClassNameCountdownType($type);
		
			require_once($typePath.$className.'.php');
			$className = __NAMESPACE__.'\\'.esc_attr($className);

			if (class_exists($className) && method_exists($className, 'create')) {
            	$className::create($postData);
       		}
		}

		return true;
	}

	public function shortcode($args, $content) {
		YcdCountdownOptionsConfig::optionsValues();

		if(empty($args['id'])) {
			return '';
		}
		$id = $args['id'];
		$typeObj = Countdown::find($id);
		$isActive = Countdown::isActivePost($id);

		if (empty($typeObj) || !$isActive) {
			return '';
		}

		if(!$typeObj->allowOpen()) {
			return '';
		}

		$typeObj->setShortCodeArgs($args);
		$typeObj->setShortCodeContent($content);

		if(YCD_PKG_VERSION > YCD_FREE_VERSION) {
			if(!CheckerPro::allowToShow($typeObj)) {
				return '';
			}
		}
		ob_start();
		$typeObj->chanegSavedDataFromArgs();

		if(!empty($content)) {
			echo "<a href='javascript:void(0)' class='ycd-circle-popup' data-id=".esc_attr($id).">".esc_attr($content)."</a>";
		}
		$content = ob_get_contents();
		ob_get_clean();
		$content .= $typeObj->renderView();

		return apply_filters('ycdCountdownContent', $content, $typeObj);
	}

	public function tableColumnValues($column, $postId) {
		$countdownObj = Countdown::find($postId);

		if ($column == 'shortcode') {
			echo '<div class="ycd-tooltip">
                    <span class="ycd-tooltiptext" id="ycd-tooltip-'.esc_attr($postId).'">'.__('Copy to clipboard', YCD_TEXT_DOMAIN).'</span><input type="text" data-id="'.esc_attr($postId).'" onfocus="this.select();" readonly id="ycd-shortcode-input-'.esc_attr($postId).'" value="[ycd_countdown id='.esc_attr($postId).']" class="large-text code countdown-shortcode"></div>';
		}
		if ($column == 'type') {
			$title = '';
			if (!empty($countdownObj)) {
				$title = $countdownObj->getTypeTitle();
			}
			_e($title);
			if(!empty($countdownObj) && is_object($countdownObj)) {
                $allowToShowExpiration = $countdownObj->allowToShowExpiration();
                if(is_object($countdownObj) && $countdownObj->getIsCountdown() && $allowToShowExpiration) {
                    if($countdownObj->isExpired()) {
                        _e('<div>Date: Expired</div>');
                    }
                    else {
                        $dateString = $countdownObj->getExpireDate();

                        _e('<div>Expires after '.esc_attr($dateString).'</div>');
                    }
                }
            }
		}
		if ($column == 'onof') {
			$checked = '';
			$isActive = Countdown::isActivePost($postId);

			if ($isActive) {
				$checked = 'checked';
			}
			?>
			<label class="ycd-switch">
				<input type="checkbox" data-id="<?php echo esc_attr($postId); ?>" name="ycd-countdown-show-mobile" class="ycd-accordion-checkbox ycd-countdown-enable" <?php echo esc_attr($checked); ?> >
				<span class="ycd-slider ycd-round"></span>
			</label>
			<?php
		}
	}

	public function generalOptions() {
        add_meta_box('ycdFeatureRequest', __('Feature Request', YCD_TEXT_DOMAIN), array($this, 'ycdFeatureRequest'), YCD_COUNTDOWN_POST_TYPE, 'side', 'high');
		if(YCD_PKG_VERSION == YCD_FREE_VERSION) {
			add_meta_box('ycdUpgrade', __('Upgrade', YCD_TEXT_DOMAIN), array($this, 'upgradeToPro'), YCD_COUNTDOWN_POST_TYPE, 'side', 'high');
		}
        add_meta_box('ycdSupport', __('Support', YCD_TEXT_DOMAIN), array($this, 'support'), YCD_COUNTDOWN_POST_TYPE, 'side');
        add_meta_box('ycdShortcodeMetabox', __('Info', YCD_TEXT_DOMAIN), array($this, 'shortcodeMetabox'), YCD_COUNTDOWN_POST_TYPE, 'side');
    }

    public function ycdFeatureRequest() {
        require_once(YCD_ADMIN_VIEWS_PATH."moreIdeas.php");
    }

	public function upgradeToPro() {
		require_once(YCD_VIEWS_PATH.'upgrade.php');
	}

	public function support() {
		require_once(YCD_VIEWS_PATH.'supportMetabox.php');
	}
	
	public function shortcodeMetabox() {
		require_once(YCD_ADMIN_VIEWS_PATH.'shortcodeMetabox.php');
	}

	public function saveSettings() {

		if(
			!isset($_POST[YCD_ADMIN_POST_NONCE])
			|| !wp_verify_nonce($_POST[YCD_ADMIN_POST_NONCE], 'YCD_ADMIN_POST_NONCE')
		) {
			_e('Sorry, your nonce did not verify.', YCD_TEXT_DOMAIN);die();
		}

		$options = array(
			'ycd-print-scripts-to-page',
			'ycd-hide-coming-soon-menu',
			'ycd-delete-data',
			'ycd-user-roles',
			'ycd-hide-editor-media-button',
			'ycd-disable-google-fonts'
		);

		foreach ($options as $option) {
			$current = '';
			if (!empty($_POST[$option])) {
				if (is_array($_POST[$option])) {
					$current = $this->sanitize($_POST[$option]);
				}
				else {
					$current = sanitize_text_field($_POST[$option]);
				}
			}
			update_option($option, $current);
		}

		wp_redirect(admin_url().'edit.php?post_type='.YCD_COUNTDOWN_POST_TYPE.'&page='.YCD_COUNTDOWN_SETTINGS.'&saved=1');
	}

	public function sanitize( $input ) {

		// Initialize the new array that will hold the sanitize values
		$new_input = array();
	
		// Loop through the input and sanitize each of the values
		foreach ( $input as $key => $val ) {
			$new_input[ $key ] = sanitize_text_field( $val );
		}
	
		return $new_input;
	
	}
	
	public function comingSoon() {
		check_admin_referer('ycdSaveComingSoon');
	
		ComingSoon::saveComingSoonSettings();
		$adminUrl = admin_url().'/edit.php';
		
		$url = add_query_arg( array(
			'post_type' => YCD_COUNTDOWN_POST_TYPE,
			'page' => YCD_COUNTDOWN_COMING_SOON,
			'saved' => 1
		), $adminUrl);
		
		wp_redirect($url);
    }
}
