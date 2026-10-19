<?php
/** RNSTYLE theme bootstrap. */
if (!defined('ABSPATH')) { exit; }
define('RNSTYLE_VERSION', '1.3.20');
foreach (['content', 'admin', 'contact', 'case-intro', 'case-media', 'opening', 'social', 'eyebrow'] as $module) { require_once get_template_directory() . '/inc/' . $module . '.php'; }
add_action('after_setup_theme', function () {
    load_theme_textdomain('rnstyle', get_template_directory() . '/languages');
    add_theme_support('title-tag'); add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 60, 'width' => 240, 'flex-width' => true, 'flex-height' => true]);
    add_theme_support('editor-styles'); add_editor_style('assets/css/editor.css');
    register_nav_menus(['primary' => 'ヘッダーナビゲーション', 'footer' => 'フッターナビゲーション']);
    add_image_size('rnstyle-work', 1144, 750, true);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('rnstyle-font', get_theme_file_uri('/assets/css/fonts.css'), [], RNSTYLE_VERSION);
    wp_enqueue_style('rnstyle', get_theme_file_uri('/assets/css/main.css'), ['rnstyle-font'], RNSTYLE_VERSION);
    wp_enqueue_style('rnstyle-adobe-service-font', 'https://use.typekit.net/vbt5lzk.css', [], null);
    wp_enqueue_style('rnstyle-latin-type', get_theme_file_uri('/assets/css/latin-type.css'), ['rnstyle', 'rnstyle-adobe-service-font'], RNSTYLE_VERSION);
    wp_enqueue_script('rnstyle-latin-type', get_theme_file_uri('/assets/js/latin-type.js'), [], RNSTYLE_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
    if (is_page('service') || is_page_template('page-service.php') || get_query_var('pagename') === 'service') {
        wp_enqueue_style('rnstyle-service-type', get_theme_file_uri('/assets/css/service-type.css'), ['rnstyle', 'rnstyle-adobe-service-font'], RNSTYLE_VERSION);
    }
    if (is_page('contact') || is_page_template('page-contact.php') || !empty($GLOBALS['rn_contact_state'])) { wp_enqueue_style('rnstyle-contact', get_theme_file_uri('/assets/css/contact.css'), ['rnstyle'], RNSTYLE_VERSION); }
    if(is_page('about') || is_page('privacy-policy') || is_privacy_policy() || is_page_template('page-privacy-policy.php')){wp_enqueue_style('rnstyle-about-privacy',get_theme_file_uri('/assets/css/about-privacy.css'),['rnstyle'],RNSTYLE_VERSION);}
    wp_enqueue_script('rnstyle', get_theme_file_uri('/assets/js/main.js'), ['rnstyle-latin-type'], RNSTYLE_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
});
function rn_asset($file) { return get_theme_file_uri('/assets/images/' . $file); }
function rn_url($slug = '') { $page = $slug ? get_page_by_path($slug) : null; return $page ? get_permalink($page) : home_url('/' . ($slug ? $slug . '/' : '')); }
function rn_defaults() { static $data; if (!$data) { $data = json_decode(file_get_contents(__DIR__ . '/inc/defaults.json'), true); } return $data; }
function rn_option($key) { $o = get_option('rnstyle_options', []); return $o[$key] ?? (rn_defaults()['options'][$key] ?? ''); }
function rn_text($key) { echo nl2br(esc_html(rn_option($key))); }
function rn_lines($key) { return array_values(array_filter(array_map('trim', explode("\n", rn_option($key))))); }
function rn_button($label = '相談する', $url = '', $secondary = false) { printf('<a class="button%s" href="%s">%s</a>', $secondary ? ' button--outline' : '', esc_url($url ?: rn_url('contact')), esc_html($label)); }
function rn_section_heading($label, $title) { echo '<div class="section-heading"><p class="eyebrow">' . rn_eyebrow_art($label) . '</p><h2>' . wp_kses_post($title) . '</h2></div>'; }
/** Header logo selected for this theme. */
function rn_header_logo() {
    echo '<a class="brand brand--header" href="'.esc_url(home_url('/')).'" aria-label="RNSTYLE ホーム"><img class="brand-header-image" src="'.esc_url(rn_asset('logo-black.svg')).'" alt="RNSTYLE" width="108" height="21"></a>';
}
function rn_logo($light = false) {
    if (has_custom_logo()) { the_custom_logo(); return; }
    echo '<a class="brand" href="' . esc_url(home_url('/')) . '" aria-label="RNSTYLE ホーム">';
    echo '<span class="brand-mark"><img src="' . esc_url(rn_asset($light ? 'e4378.svg' : 'b457f.svg')) . '" alt=""/><img src="' . esc_url(rn_asset($light ? 'c615b.svg' : 'ace86.svg')) . '" alt=""/></span>';
    echo '<img class="brand-word" src="' . esc_url(rn_asset($light ? '1c047.svg' : 'c42c6.svg')) . '" alt="RNSTYLE"/></a>';
}
function rn_nav($footer = false) {
    $location = $footer ? 'footer' : 'primary';
    if (has_nav_menu($location)) { wp_nav_menu(['theme_location' => $location, 'container' => false, 'menu_class' => 'nav-list', 'depth' => 1]); return; }
    echo '<ul class="nav-list">'; foreach (['service'=>'Service','works'=>'Works','about'=>'About','owned-products'=>'Owned Products'] as $slug=>$label) { echo '<li><a href="' . esc_url(rn_url($slug)) . '"' . (is_page($slug) || ($slug === 'works' && is_singular('rn_work')) ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a></li>'; }
    if ($footer) { echo '<li><a href="' . esc_url(rn_url('contact')) . '">Contact</a></li>'; } echo '</ul>';
}
add_filter('body_class', function($classes) { $classes[] = 'rnstyle'; return $classes; });

function rn_process_steps() { $steps=[]; foreach(rn_lines('process_steps') as $i=>$line){$parts=explode('|',$line,3);if(count($parts)===3){$parts[]=rn_defaults()['process'][$i%5][3];$steps[]=$parts;}} return $steps; }

function rn_hero_src($file,$device) { foreach(rn_lines('hero_'.$device.'_images') as $line){$pair=explode('|',$line,2);if(count($pair)===2&&trim($pair[0])===$file){$url=wp_get_attachment_image_url(absint($pair[1]),'full');if($url){return $url;}}}return rn_asset($file); }

/**
 * Preview built-in pages before the optional initial-content import.
 * Scoped to authenticated Customizer previews; never creates or publishes data.
 */
function rnstyle_preview_missing_page() {
    if (is_admin() || !is_customize_preview() || !is_404()) { return; }
    global $wp, $wp_query;
    $routes = [
        'home' => ['Home', 'front-page.php'],
        'service' => ['Service', 'page-service.php'],
        'works' => ['Works', 'page-works.php'],
        'about' => ['About', 'page-about.php'],
        'contact' => ['Contact', 'page-contact.php'],
        'news' => ['News', 'page-news.php'],
        'privacy-policy' => ['プライバシーポリシー', 'page-privacy-policy.php'],
        'owned-products' => ['Owned Products', 'page-owned-products.php'],
    ];
    $slug = trim((string) $wp->request, '/');
    if ($slug === '') { $slug = 'home'; }
    if (!isset($routes[$slug])) { return; }
    $route = $routes[$slug];
    $template = locate_template($route[1]);
    if (!$template && $slug === 'privacy-policy') { $template = locate_template('page.php'); }
    if (!$template) { return; }
    $content = '';
    if ($slug === 'privacy-policy') {
        $file = get_template_directory() . '/inc/privacy-content.html';
        if (is_readable($file)) { $content = file_get_contents($file); }
    }
    $page = new WP_Post((object) [
        'ID' => -9000 - array_search($slug, array_keys($routes), true),
        'post_type' => 'page', 'post_status' => 'publish',
        'post_name' => $slug, 'post_title' => $route[0],
        'post_content' => $content, 'post_excerpt' => '',
        'post_parent' => 0, 'post_author' => get_current_user_id(), 'filter' => 'raw',
        'post_date' => '2026-01-01 00:00:00',
        'post_date_gmt' => '2026-01-01 00:00:00',
        'comment_status' => 'closed', 'ping_status' => 'closed',
    ]);
    $wp_query->posts = [$page];
    $wp_query->post = $page;
    $wp_query->post_count = 1;
    $wp_query->current_post = -1;
    $wp_query->found_posts = 1;
    $wp_query->max_num_pages = 1;
    $wp_query->is_404 = false;
    $wp_query->is_page = true;
    $wp_query->is_singular = true;
    $wp_query->is_single = false;
    $wp_query->is_home = false;
    $wp_query->is_archive = false;
    $wp_query->queried_object = $page;
    $wp_query->queried_object_id = $page->ID;
    $wp_query->set('pagename', $slug);
    $wp_query->set('fields', 'all');
    $GLOBALS['post'] = $page;
    status_header(200);
    nocache_headers();
    add_filter('template_include', static function ($original) use ($template) { return $template; }, 99);
}
add_action('template_redirect', 'rnstyle_preview_missing_page', 0);
