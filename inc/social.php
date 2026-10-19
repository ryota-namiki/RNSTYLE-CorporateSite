<?php
/** Shared link-preview image. AIOSEO retains control of page titles and descriptions. */
if (!defined('ABSPATH')) { exit; }

function rn_social_image_url() {
    return esc_url_raw(get_theme_file_uri('/assets/images/ogp-rnstyle.png'));
}

/** Update AIOSEO's existing tags instead of outputting a second set. */
function rn_social_opengraph_image($tags) {
    $tags['og:image'] = rn_social_image_url();
    $tags['og:image:width'] = '3600';
    $tags['og:image:height'] = '1890';
    $tags['og:image:type'] = 'image/png';
    $tags['og:image:alt'] = 'RNSTYLE';
    if (isset($tags['og:image:secure_url'])) { $tags['og:image:secure_url'] = rn_social_image_url(); }
    if (isset($tags['og:image:url'])) { $tags['og:image:url'] = rn_social_image_url(); }
    return $tags;
}
add_filter('aioseo_facebook_tags', 'rn_social_opengraph_image', 99);

function rn_social_twitter_image($tags) {
    $tags['twitter:card'] = 'summary_large_image';
    $tags['twitter:image'] = rn_social_image_url();
    $tags['twitter:image:alt'] = 'RNSTYLE';
    if (isset($tags['twitter:image:src'])) { $tags['twitter:image:src'] = rn_social_image_url(); }
    return $tags;
}
add_filter('aioseo_twitter_tags', 'rn_social_twitter_image', 99);
