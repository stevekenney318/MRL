<?php
use ycd\AdminHelper;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin data in an array for scalability and maintainability
$plugins = [
    [
        'name' => __('Random Numbers – WordPress Random Numbers Builder Plugin', 'text-domain'),
        'desc' => __('Random numbers builder plugin allows the visitor to create random numbers on the page.', 'text-domain'),
        'url' => 'https://wordpress.org/plugins/random-numbers-builder/',
        'icon_id' => 'plugin-icon-random-numbers',
        'install_url' => AdminHelper::getPluginActivationUrl('random-numbers-builder'),
    ],
    [
        'name' => __('Read More', 'text-domain'),
        'desc' => __('The best WordPress "Read more" plugin to help you show or hide your long content.', 'text-domain'),
        'url' => 'https://wordpress.org/plugins/expand-maker/',
        'icon_id' => 'plugin-icon-readmore',
        'install_url' => AdminHelper::getPluginActivationUrl('expand-maker'),
    ]
];
?>

<div class="plugin-group" id="ycd-plugins-wrapper">
    <?php foreach ($plugins as $plugin): ?>
        <div class="plugin-card">
            <div class="plugin-card-top">
                <!-- Plugin Icon -->
                <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank" class="plugin-icon">
                    <div class="plugin-icon" id="<?php echo esc_attr($plugin['icon_id']); ?>"></div>
                </a>

                <!-- Plugin Name and Action Links -->
                <div class="name column-name">
                    <h4>
                        <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank">
                            <?php echo esc_html($plugin['name']); ?>
                        </a>
                        <div class="action-links">
                            <span class="plugin-action-buttons">
                                <a class="install-now button" href="<?php echo esc_url($plugin['install_url']); ?>">
                                    <?php esc_html_e('Install Now', 'text-domain'); ?>
                                </a>
                            </span>
                        </div>
                    </h4>
                </div>

                <!-- Plugin Description and Compatibility -->
                <div class="desc column-description">
                    <p><?php echo esc_html($plugin['desc']); ?></p>
                    <div class="column-compatibility">
                        <span class="compatibility-compatible">
                            <strong><?php esc_html_e('Compatible', 'text-domain'); ?></strong>
                            <?php esc_html_e('with your version of WordPress', 'text-domain'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
