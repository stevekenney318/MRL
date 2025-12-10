<?php
/**
 * MinimalistBlogger-child functions.php
 */

/* -------------------------------------------------
 * 1) Load parent theme stylesheet
 * ------------------------------------------------- */
add_action('wp_enqueue_scripts', 'minimalistblogger_child_enqueue_styles');
function minimalistblogger_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

/* -------------------------------------------------
 * 2) [cut] marker + 50-character fallback excerpt (PHP 7.0 compatible)
 *    - If manual excerpt exists, use it as-is
 *    - If [cut] is present, excerpt stops there and adds …
 *    - If not, auto-excerpt trims to 50 characters (no mid-word cut) and adds …
 * ------------------------------------------------- */
add_shortcode('cut', '__return_empty_string');

add_filter('get_the_excerpt', function ($excerpt, $post) {
    if (is_admin()) {
        return $excerpt;
    }

    if (!$post) {
        $post = get_post();
    }

    // Case 0: honor manual excerpt if set
    if (has_excerpt($post->ID)) {
        return $excerpt; // WordPress already stored the manual excerpt
    }

    $content = isset($post->post_content) ? $post->post_content : '';

    // Case 1: honor [cut]
    if ($content && strpos($content, '[cut]') !== false) {
        $parts   = explode('[cut]', $content, 2);
        $before  = $parts[0];

        $rendered = do_shortcode($before);
        $plain    = wp_strip_all_tags($rendered);

        return trim($plain) . ' …';
    }

    // Case 2: fallback to 50-character excerpt (no mid-word cut)
    $rendered = do_shortcode($content);
    $plain    = trim(wp_strip_all_tags($rendered));

    $limit = 50; // character limit
    if (mb_strlen($plain) > $limit) {
        $trunc = mb_substr($plain, 0, $limit);

        // backtrack to last space so we don't cut mid-word
        $spacePos = mb_strrpos($trunc, ' ');
        if ($spacePos !== false) {
            $trunc = mb_substr($trunc, 0, $spacePos);
        }

        return rtrim($trunc) . ' …';
    }

    return $plain;
}, 10, 2);

// Remove [cut] from full post views
add_filter('the_content', function ($content) {
    return str_replace('[cut]', '', $content);
});
