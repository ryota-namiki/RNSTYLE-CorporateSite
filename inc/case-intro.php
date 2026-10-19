<?php
/** Project entry animation and its editable ACF database group. */
if (!defined('ABSPATH')) { exit; }

function rn_case_entry_definition() {
    $file = get_template_directory() . '/config/acf-intro-field-group.json';
    return is_readable($file) ? json_decode(file_get_contents($file), true) : [];
}

function rn_case_entry_group() {
    $groups = get_posts([
        'post_type'=>'acf-field-group', 'post_status'=>['publish','draft','acf-disabled','trash'],
        'post_name__in'=>['group_rn_case_entry','group_rn_case_entry__trashed'], 'numberposts'=>1,
    ]);
    return $groups ? $groups[0] : null;
}

function rn_case_entry_install() {
    if (!function_exists('acf_import_field_group')) { return new WP_Error('acf_missing', 'ACFを有効にしてください。'); }
    $existing = rn_case_entry_group();
    if ($existing) {
        return $existing->post_status === 'trash'
            ? new WP_Error('acf_trashed', '導入アニメーションのグループをACF画面のゴミ箱から復元してください。')
            : $existing->ID;
    }
    $definition = rn_case_entry_definition();
    if (empty($definition['fields'])) { return new WP_Error('acf_definition', '導入アニメーションのフィールド定義がありません。'); }
    $group = acf_import_field_group($definition);
    return !empty($group['ID']) ? $group['ID'] : new WP_Error('acf_import', 'フィールドグループを追加できませんでした。');
}

add_action('rnstyle_gallery_admin', function () {
    if (!current_user_can('manage_options')) { return; }
    echo '<h2>実績詳細の導入アニメーション</h2>';
    if (rn_case_entry_group()) {
        echo '<p>ACFの「RNSTYLE 導入アニメーション」で設定を管理できます。各実績で導入用タイトル・文字色・ON／OFFを編集してください。</p>';
        return;
    }
    echo '<p>専用のACFフィールドグループを1回だけ追加します。既存グループ・実績内容は上書きしません。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('rnstyle_case_entry_install');
    echo '<input type="hidden" name="action" value="rnstyle_case_entry_install">';
    submit_button('導入アニメーションのACF欄を追加', 'secondary');
    echo '</form>';
}, 30);

add_action('admin_post_rnstyle_case_entry_install', function () {
    if (!current_user_can('manage_options')) { wp_die('権限がありません。', 403); }
    check_admin_referer('rnstyle_case_entry_install');
    $result = rn_case_entry_install();
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
    wp_safe_redirect(admin_url('themes.php?page=rnstyle&case-entry=installed'));
    exit;
});

function rn_case_entry_default_color($background) {
    $luminance = static function ($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $channels = array_map(static function ($channel) {
            $value = hexdec($channel) / 255;
            return $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }, str_split($hex, 2));
        return $channels[0]*0.2126 + $channels[1]*0.7152 + $channels[2]*0.0722;
    };
    $background_value = $luminance($background);
    $colors = ['#00668c', '#fffefb'];
    $contrast = static function ($color) use ($luminance, $background_value) {
        $value = $luminance($color);
        return (max($value, $background_value) + 0.05) / (min($value, $background_value) + 0.05);
    };
    return $contrast($colors[0]) >= $contrast($colors[1]) ? $colors[0] : $colors[1];
}

function rn_case_entry_options($id) {
    $title = trim(wp_strip_all_tags(rn_case_value($id, 'entry_title')));
    if ($title === '') { $title = wp_strip_all_tags(html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8')); }
    $background = rn_case_background_color($id) ?: '#fffefb';
    return [
        'enabled'=>!metadata_exists('post', $id, 'rn_case_entry_enabled') || rn_case_value($id, 'entry_enabled') === '1',
        'title'=>$title,
        'color'=>sanitize_hex_color(rn_case_value($id, 'entry_color')) ?: rn_case_entry_default_color($background),
        'background'=>$background,
    ];
}

function rn_case_entry_active() {
    return is_singular('rn_work') && rn_case_entry_options(get_queried_object_id())['enabled'];
}

add_action('wp_enqueue_scripts', function () {
    if (!is_singular('rn_work')) { return; }
    wp_enqueue_style('rnstyle-case-entry', get_theme_file_uri('/assets/css/case-intro.css'), ['rnstyle'], RNSTYLE_VERSION);
    $dependencies = [];
    if (rn_case_entry_active()) {
        wp_enqueue_script('rnstyle-gsap', get_theme_file_uri('/assets/vendor/gsap/gsap.min.js'), [], '3.15.0', ['strategy'=>'defer','in_footer'=>true]);
        $dependencies[] = 'rnstyle-gsap';
    }
    wp_enqueue_script('rnstyle-case-entry', get_theme_file_uri('/assets/js/case-intro.js'), $dependencies, RNSTYLE_VERSION, ['strategy'=>'defer','in_footer'=>true]);
}, 20);

add_action('wp_head', function () {
    if (!rn_case_entry_active()) { return; }
    $script = get_template_directory() . '/assets/js/case-intro-boot.js';
    if (is_readable($script)) { wp_print_inline_script_tag(file_get_contents($script), ['id'=>'rnstyle-case-entry-boot']); }
}, 2);

function rn_case_entry_title($id) {
    $settings = rn_case_entry_options($id);
    $length = function_exists('mb_strlen') ? mb_strlen($settings['title'], 'UTF-8') : preg_match_all('/./us', $settings['title']);
    $fit_chars = max(1, $length * 1.15);
    echo '<h1 class="case-title case-entry" data-case-entry data-entry-enabled="'.($settings['enabled']?'1':'0').'" aria-label="'.esc_attr($settings['title']).'" style="--case-entry-color:'.esc_attr($settings['color']).';--case-entry-fit-chars:'.esc_attr((string)$fit_chars).'">';
    echo '<span class="case-entry-word" data-case-entry-word><span class="case-entry-outline" aria-hidden="true">'.esc_html($settings['title']).'</span><span class="case-entry-fill" aria-hidden="true">'.esc_html($settings['title']).'</span></span></h1>';
}
