<?php
/** Home opening: approved backward roll, then live-page reveal. */
if (!defined('ABSPATH')) { exit; }

function rn_opening_enabled() {
    return is_front_page() && !is_paged() && !is_feed() && !is_embed() && !is_customize_preview()
        && is_readable(get_theme_file_path('/assets/media/opening-backward-desktop.mp4'));
}
add_action('wp_enqueue_scripts', function () {
    if (!rn_opening_enabled()) { return; }
    wp_enqueue_style('rnstyle-opening',get_theme_file_uri('/assets/css/opening.css'),['rnstyle'],RNSTYLE_VERSION);
    wp_enqueue_script('rnstyle-opening',get_theme_file_uri('/assets/js/opening.js'),[],RNSTYLE_VERSION,['strategy'=>'defer','in_footer'=>true]);
}, 20);
add_action('wp_head', function () {
    if (!rn_opening_enabled()) { return; }
    // Critical boot styles prevent a flash of the page before deferred JS starts.
    echo '<style id="rnstyle-opening-critical">html.rn-opening-active,html.rn-opening-active body{background:#fffefb}html.rn-opening-active{overflow:hidden}html.rn-opening-active:not(.rn-opening-reveal) body>:not(.site-opening):not(script):not(style):not(link){visibility:hidden}</style>';
    $boot=get_theme_file_path('/assets/js/opening-boot.js');
    if (is_readable($boot)) { wp_print_inline_script_tag(file_get_contents($boot),['id'=>'rnstyle-opening-boot']); }
}, 1);
add_action('wp_body_open', function () {
    if (!rn_opening_enabled()) { return; }
    echo '<div class="site-opening" data-site-opening data-state="pending" role="dialog" aria-modal="true" aria-label="RNSTYLE オープニング" hidden>';
    echo '<video class="site-opening-video" muted playsinline preload="none" disablepictureinpicture disableremoteplayback aria-hidden="true" tabindex="-1">';
    if (is_readable(get_theme_file_path('/assets/media/opening-backward-mobile.mp4'))) {
        echo '<source data-src="'.esc_url(get_theme_file_uri('/assets/media/opening-backward-mobile.mp4')).'" media="(max-width:767px)" type="video/mp4">';
    }
    echo '<source data-src="'.esc_url(get_theme_file_uri('/assets/media/opening-backward-desktop.mp4')).'" type="video/mp4"></video>';
    echo '<button class="site-opening-skip" type="button">オープニングをスキップ <span aria-hidden="true">↗</span></button></div>';
}, 1);
