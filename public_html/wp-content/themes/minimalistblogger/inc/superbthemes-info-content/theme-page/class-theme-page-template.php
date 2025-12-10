<?php

namespace SuperbThemesThemeInformationContent\ThemePage;

defined("ABSPATH") || exit();

class ThemePageTemplate
{
    private $Theme;
    private $ParentName;
    private $ThemeName;
    private $PremiumText;
    private $Type;

    private $Features;
    private $ThemeLink;
    private $ThemePremiumLink;
    private $DemoLink;
    private $ContactLink;

    public function __construct($data)
    {
        $this->Theme = wp_get_theme();
        $this->ParentName = is_child_theme() ? wp_get_theme($this->Theme->Template) : '';
        $this->ThemeName = is_child_theme() ? sprintf(/* translators: %s are theme names */__("%s and %s", 'minimalistblogger'), $this->Theme, $this->ParentName) : $this->Theme;
        $this->PremiumText = is_child_theme() ? sprintf(/* translators: %s are theme names */__("Unlock all features by upgrading to the premium edition of %s and its parent theme %s.", 'minimalistblogger'), $this->Theme, $this->ParentName) : sprintf(/* translators: %s is a theme name */__("Unlock all features by upgrading to the premium edition of %s.", 'minimalistblogger'), $this->Theme);
        $this->ThemeLink = !empty($data['theme_url']) ? $data['theme_url'] : 'https://superbthemes.com/';
        $this->DemoLink = !empty($data['demo_url']) ? $data['demo_url'] . '?su_source=theme_settings' : 'https://superbthemes.com/';
        $this->ContactLink = 'https://superbthemes.com/contact/?su_source=theme_settings';
        $this->Type = $data['type'];
        $base_features = array(
            array(
                'title' => __("Fully Search Engine Optimized", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-8.png",
                'description' => __("Get free traffic by ranking #1 on Google with the lightning-fast & SEO-optimized premium version.", "minimalistblogger"),
                'source' => 'seo'
            ),
            array(
                'title' => __("Page Speed Optimized", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-6.png",
                'description' => __("Unlock maximum speed with the premium version. It loads in less than 0.3 seconds. ", "minimalistblogger"),
                'source' => 'speed'
            ),
            array(
                'title' => __("Customize Everything", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-7.png",
                'description' => __("Customize the design to fit your brand or style with our easy-to-use customization options.", "minimalistblogger"),
                'source' => 'customization'
            ),
            array(
                'title' => __("E-commerce Compatibility", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-5.png",
                'description' => __("Create your online store easily. The premium version is compatible with all popular e-commerce plugins.", "minimalistblogger"),
                'source' => 'ecommerce'
            ),
            array(
                'title' => __("Customer Support & Documentation", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-4.png",
                'description' => __("Benefit from our comprehensive documentation and dedicated support team, always ready to help.", "minimalistblogger"),
                'source' => 'support'
            ),
            array(
                'title' => __("Works With All Page Builders", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-3.png",
                'description' => __("Brizy, Elementor, Divi Builder, Beaver Builder - you name it. Every page builder plugin is compatible.", "minimalistblogger"),
                'source' => 'page_builders'
            ),
            array(
                'title' => __("1-Click Starter Content Import", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-2.png",
                'description' => __("Get started easily with our one-click demo content import feature. Get your website up and running in seconds.", "minimalistblogger"),
                'source' => 'starter_content'
            ),
            array(
                'title' => __("Premium Designs, Patterns & Layouts", "minimalistblogger"),
                'base' => true,
                'icon' => "img-icon-1.png",
                'description' => __("Access all the premium layouts and designs perfect for any niche or industry.", "minimalistblogger"),
                'source' => 'designs'
            ),
            array(
                'title' => __("Works On All Devices And Browsers", "minimalistblogger"),
                'base' => true,
                'icon' => "devices-duotone.svg",
                'description' => __("The premium version looks perfect everywhere, from desktop to mobile, and in every browser.", "minimalistblogger"),
                'source' => 'devices'
            ),
            array(
                'title' => __("AMP Compatible And Mobile Ready", "minimalistblogger"),
                'base' => true,
                'icon' => "fse_icon_mobile.svg",
                'description' => __("Stay ahead with Accelerated Mobile Pages (AMP) compatibility.", "minimalistblogger"),
                'source' => 'amp'
            ),
            array(
                'title' => __("GDPR Compliant", "minimalistblogger"),
                'base' => true,
                'icon' => "shield-check-duotone.svg",
                'description' => __("Our premium version comes fully compliant, giving you peace of mind about user data protection and privacy.", "minimalistblogger"),
                'source' => 'gdpr'
            ),
            array(
                'title' => __("Frequent Updates", "minimalistblogger"),
                'base' => true,
                'icon' => "arrows-clockwise-duotone.svg",
                'description' => __("Our premium version provides frequent enhancements for security, performance, and features.", "minimalistblogger"),
                'source' => 'updates'
            ),
            array(
                'title' => __("Child Themes", "minimalistblogger"),
                'base' => true,
                'icon' => "img-2.png",
                'description' => __("Use child themes to make modifications without affecting the parent theme's code, ensuring smooth updates.", "minimalistblogger"),
                'source' => 'child_themes'
            ),
            array(
                'title' => __("WordPress blocks", "minimalistblogger"),
                'base' => true,
                'icon' => "stack-duotone.png",
                'description' => __("Use our many custom WordPress Gutenberg blocks for every purpose!", "minimalistblogger"),
                'source' => 'blocks'
            ),
            array(
                'title' => __("WordPress patterns", "minimalistblogger"),
                'base' => true,
                'icon' => "grid-nine-duotone.png",
                'description' => __("Take advantage of the 400+ beautiful patterns for every type of website.", "minimalistblogger"),
                'source' => 'patterns'
            ),
            array(
                'title' => __("Elementor sections", "minimalistblogger"),
                'base' => true,
                'icon' => "img-1.png",
                'description' => __("Access 300+ pre-built Elementor sections and build beautiful sites, fast.", "minimalistblogger"),
                'source' => 'elementor'
            )
        );
        $this->Features = $data['features'] ? array_merge($base_features, $data['features']) : $base_features;

        $this->Render();
    }

    private function Render()
    {
?>
        <div class="wrap">
            <div class="spt-theme-settings-wrapper">
                <div class="spt-theme-settings-wrapper-main-content">

                    <div class="spt-theme-settings-wrapper-main-content-section">
                        <div class="spt-theme-settings-wrapper-main-content-section-top">
                            <span class="spt-theme-settings-headline"><?php esc_html_e("Customize Settings", 'minimalistblogger'); ?></span>
                            <?php if ($this->Type === 'block') : ?>
                                <a class="spt-theme-settings-headline-link" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                            <?php else : ?>
                                <a class="spt-theme-settings-headline-link" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e("Go To Customizer", 'minimalistblogger'); ?></a>
                            <?php endif; ?>
                        </div>

                        <div class="spt-theme-settings-content">

                            <div class="spt-theme-settings-content-getting-started-wrapper">
                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/list-bullets.svg'); ?>" />
                                        <div class="spt-theme-settings-content-item-headline">
                                            <?php esc_html_e("Add Menus", 'minimalistblogger'); ?>
                                        </div>
                                        <p><?php esc_html_e("Add a navigation to your website to improve the user experience.", 'minimalistblogger'); ?></p>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <?php if ($this->Type === 'block') : ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                                        <?php else: ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('nav-menus.php')); ?>"><?php esc_html_e("Go to Menus", "minimalistblogger"); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/squares-four.svg'); ?>" />
                                        <?php if ($this->Type === 'block') : ?>
                                            <div class="spt-theme-settings-content-item-headline">
                                                <?php esc_html_e("Edit Front Page", 'minimalistblogger'); ?>
                                            </div>
                                            <p><?php esc_html_e("Edit and customize your front page design through the site editor.", 'minimalistblogger'); ?></p>
                                        <?php else: ?>
                                            <div class="spt-theme-settings-content-item-headline">
                                                <?php esc_html_e("Add Widgets", 'minimalistblogger'); ?>
                                            </div>
                                            <p><?php esc_html_e("Add and customize widgets in any widget space.", 'minimalistblogger'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <?php if ($this->Type === 'block') : ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                                        <?php else: ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('widgets.php')); ?>"><?php esc_html_e("Go to Widgets", 'minimalistblogger'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/paint-brush.svg'); ?>" />
                                        <div class="spt-theme-settings-content-item-headline">
                                            <?php esc_html_e("Customize Design", 'minimalistblogger'); ?>
                                        </div>
                                        <p><?php esc_html_e("Customize your website design to fit your personality or brand.", 'minimalistblogger'); ?></p>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <?php if ($this->Type === 'block') : ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                                        <?php else: ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e("Go To Customizer", 'minimalistblogger'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/text-a-underline.svg'); ?>" />
                                        <div class="spt-theme-settings-content-item-headline">
                                            <?php esc_html_e("Change Site Title", 'minimalistblogger'); ?>
                                        </div>
                                        <p><?php esc_html_e("Add your website name and tagline to improve the design and SEO.", 'minimalistblogger'); ?></p>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <?php if ($this->Type === 'block') : ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                                        <?php else: ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e("Go To Customizer", 'minimalistblogger'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/image.svg'); ?>" />
                                        <div class="spt-theme-settings-content-item-headline">
                                            <?php esc_html_e("Upload Logo", 'minimalistblogger'); ?>
                                        </div>
                                        <p><?php esc_html_e("Add a custom logo to make your website look more professional.", 'minimalistblogger'); ?></p>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <?php if ($this->Type === 'block') : ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('site-editor.php'))  ?>"><?php esc_html_e("Go To Site Editor", 'minimalistblogger'); ?></a>
                                        <?php else: ?>
                                            <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e("Go To Customizer", 'minimalistblogger'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="spt-theme-settings-content-item">
                                    <div class="spt-theme-settings-content-item-header">
                                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/file.svg'); ?>" />
                                        <div class="spt-theme-settings-content-item-headline">
                                            <?php esc_html_e("Create New Pages", 'minimalistblogger'); ?>
                                        </div>
                                        <p><?php esc_html_e("Start creating your website by adding pages to it.", 'minimalistblogger'); ?></p>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <a class="spt-theme-settings-content-item-button" href="<?php echo esc_url(admin_url('edit.php?post_type=page')) ?>"><?php esc_html_e("Create a new page", 'minimalistblogger'); ?></a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="spt-theme-settings-wrapper-main-content-section">
                        <div class="spt-theme-settings-wrapper-main-content-section-top">
                            <span class="spt-theme-settings-headline"><?php esc_html_e("Premium Features", 'minimalistblogger'); ?></span>
                            <a class="spt-theme-settings-headline-link" href="<?php echo esc_url($this->ThemeLink . "?su_source=theme_settings_unlock_all"); ?>"><?php esc_html_e("Unlock All Features", 'minimalistblogger'); ?></a>
                        </div>
                        <p class="spt-theme-settings-wrapper-main-content-section-top-description">
                            <?php esc_html_e("Create a beautiful website easily, without coding.", 'minimalistblogger'); ?>
                        </p>

                        <div class="spt-theme-settings-content spt-theme-settings-content-us">
                            <?php
                            foreach ($this->Features as $feature) :
                                $source = isset($feature['source']) ? $feature['source'] : 'theme_settings';
                                $icon = isset($feature['icon']) ? $feature['icon'] : 'img-icon-7.png';
                            ?>
                                <a target="_blank" href="<?php echo esc_url($this->ThemeLink . "?su_source=theme_settings_feature_" . $source); ?>" class="spt-theme-settings-content-item spt-theme-settings-content-item-unavailable">
                                    <span class="spt-theme-settings-content-item-unavailable-premium"><?php echo esc_html__("Premium", 'minimalistblogger'); ?></span>
                                    <div class="spt-theme-settings-content-item-header">
                                        <div>
                                            <img height="32" width="32" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/' . $icon); ?>" />
                                        </div>
                                        <span class="spt-theme-settings-content-us-title"><?php echo esc_html($feature["title"]); ?></span></span>
                                        <?php if (isset($feature['description'])) : ?>
                                            <p><?php echo esc_html($feature['description']); ?></p>
                                        <?php else : ?>
                                            <p><?php echo esc_html(sprintf(__("With %s Premium you'll have full access to this feature as well as all the other features listed.", 'minimalistblogger'), $this->ThemeName)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="spt-theme-settings-content-item-content">
                                        <span class="spt-theme-settings-content-us-button-link"><?php esc_html_e("Get Premium Version", 'minimalistblogger'); ?></span>
                                    </div>
                                </a>
                            <?php
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="spt-theme-settings-wrapper-sidebar">
                    <div class="spt-theme-settings-wrapper-sidebar-item">
                        <div class="spt-theme-settings-wrapper-sidebar-item-content">
                            <img class="spt-theme-settings-wrapper-sidebar-item-content-demo-image" src="<?php echo esc_url(get_template_directory_uri() . '/screenshot.png'); ?>" alt="<?php echo esc_attr($this->ThemeName); ?> Preview" />
                            <div class="spt-theme-settings-wrapper-sidebar-item-header"><?php esc_html_e("View Demo", 'minimalistblogger'); ?></div>
                            <p><?php echo esc_html__("Need inspiration? Take a moment to view our theme demo!", 'minimalistblogger') ?></p>
                            <a href="<?php echo esc_url($this->DemoLink); ?>" target="_blank" class="button"><?php esc_html_e("View Demo", 'minimalistblogger'); ?></a>
                        </div>
                    </div>

                    <div class="spt-theme-settings-wrapper-sidebar-item">
                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/color-crown.svg'); ?>" />
                        <div class="spt-theme-settings-wrapper-sidebar-item-header"><?php esc_html_e("Upgrade to premium", 'minimalistblogger'); ?></div>
                        <div class="spt-theme-settings-wrapper-sidebar-item-content">
                            <p><?php echo esc_html($this->PremiumText); ?></p>
                            <a href="<?php echo esc_url($this->ThemeLink . "?su_source=theme_settings_view_premium"); ?>" target="_blank" class="button button-primary"><?php esc_html_e("View Premium Version", 'minimalistblogger'); ?></a>
                        </div>
                    </div>

                    <div class="spt-theme-settings-wrapper-sidebar-item">
                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/chats.svg'); ?>" />
                        <div class="spt-theme-settings-wrapper-sidebar-item-header"><?php esc_html_e("Contact support", 'minimalistblogger'); ?></div>
                        <div class="spt-theme-settings-wrapper-sidebar-item-content">
                            <p><?php echo esc_html(sprintf(__("If you have issues with %s, please send us an email through our website!", 'minimalistblogger'), $this->Theme)); ?></p>
                            <a href="<?php echo esc_url($this->ContactLink); ?>" target="_blank" class="button"><?php esc_html_e("Contact Support", 'minimalistblogger'); ?></a>
                        </div>
                    </div>

                    <div class="spt-theme-settings-wrapper-sidebar-item">
                        <img width="25" height="25" src="<?php echo esc_url(get_template_directory_uri() . '/inc/superbthemes-info-content/icons/shooting-star.svg'); ?>" />
                        <div class="spt-theme-settings-wrapper-sidebar-item-header"><?php esc_html_e("Give us feedback", 'minimalistblogger'); ?></div>
                        <div class="spt-theme-settings-wrapper-sidebar-item-content">
                            <p><?php echo esc_html(sprintf(__("Do you enjoy using %s? Support us by reviewing us on WordPress.org!", 'minimalistblogger'), $this->Theme)); ?></p>
                            <a href="<?php echo esc_url('https://wordpress.org/support/theme/' . get_stylesheet() . '/reviews/#new-post'); ?>" target="_blank" class="button"><?php esc_html_e("Leave a Review", 'minimalistblogger'); ?></a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
<?php
    }
}
