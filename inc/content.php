<?php
/* RNSTYLE gallery 1.1.0 */
if (!defined('ABSPATH')) { exit; }
add_action('init', function () {
    foreach (['rn_work'=>['制作実績','works','dashicons-portfolio'], 'rn_service'=>['サービス','services','dashicons-layout'], 'rn_product'=>['自社プロダクト','products','dashicons-products']] as $type=>$v) {
        register_post_type($type, ['labels'=>['name'=>$v[0], 'singular_name'=>$v[0], 'add_new_item'=>$v[0].'を追加', 'edit_item'=>$v[0].'を編集'], 'public'=>true, 'has_archive'=>false, 'show_in_rest'=>true, 'rewrite'=>['slug'=>$v[1], 'with_front'=>false], 'menu_icon'=>$v[2], 'supports'=>['title','editor','excerpt','thumbnail','page-attributes','custom-fields']]);
    }
    register_taxonomy('work_category', 'rn_work', ['label'=>'実績カテゴリー', 'public'=>true, 'hierarchical'=>true, 'show_in_rest'=>true, 'rewrite'=>['slug'=>'work-category']]);
    foreach (['subtitle','lead','tags','price','client','role','year','external_url','gallery','challenge','solution','result'] as $key) {
        register_post_meta('', '_rn_'.$key, ['type'=>'string','single'=>true,'sanitize_callback'=> $key==='external_url' ? 'esc_url_raw' : 'sanitize_textarea_field', 'show_in_rest'=>false, 'auth_callback'=>function(){return current_user_can('edit_posts');}]);
    }
});
function rn_services() {
    $posts = get_posts(['post_type'=>'rn_service','numberposts'=>-1,'orderby'=>['menu_order'=>'ASC','ID'=>'ASC']]);
    if (!$posts) { return rn_defaults()['services']; }
    return array_map(function($p) { $a=['slug'=>$p->post_name,'title'=>get_the_title($p),'excerpt'=>$p->post_excerpt,'body'=>$p->post_content]; foreach(['subtitle','lead','tags','price'] as $k) {$a[$k]=get_post_meta($p->ID,'_rn_'.$k,true);} return $a; }, $posts);
}
function rn_work_card($post) {
    $id=$post->ID; $label=implode(' / ',array_values(rn_gallery_tags($id)));
    echo '<article class="work-card"><a href="'.esc_url(get_permalink($id)).'">';
    $image=rn_case_image_id($id,'card_image') ?: get_post_thumbnail_id($id) ?: rn_case_image_id($id,'hero_image');
    echo '<div class="work-image">'; if($image){echo wp_get_attachment_image($image,'rnstyle-work',false,['loading'=>'lazy']);}else{echo '<img src="'.esc_url(rn_asset('1f63c.png')).'" alt="" width="1144" height="750" loading="lazy"/>';}
    if ($label !== '') { echo '<span class="work-label">'.esc_html($label).'</span>'; }
    echo '</div><h3>'.esc_html(get_the_title($id)).'</h3><p class="work-meta">'.esc_html(rn_case_value($id,'role','role')).'<span>'.esc_html(rn_case_value($id,'year','year')).'</span></p></a></article>';
}
function rn_works($limit=4, $category='') {
    $args=['post_type'=>'rn_work','posts_per_page'=>$limit,'orderby'=>['menu_order'=>'ASC','date'=>'DESC']];
    if($category){$args['tax_query']=[['taxonomy'=>'work_category','field'=>'slug','terms'=>$category]];}
    return new WP_Query($args);
}
function rn_page_hero($label,$title,$body='') { echo '<section class="page-hero"><div class="container"><p class="eyebrow">'.rn_eyebrow_art($label).'</p><h1>'.nl2br(esc_html($title)).'</h1>'; if($body){echo '<p>'.nl2br(esc_html($body)).'</p>';} echo '</div></section>'; }

/** Optional case-study sections. All fields work with ACF Free. */
function rn_case_sections() {
    return [
        'context'=>['CONTEXT','背景・プロジェクト概要'],
        'research'=>['RESEARCH','調査・インタビュー'],
        'starting_point'=>['THE STARTING POINT','着手時の状況'],
        'problem'=>['THE PROBLEM','課題','challenge'],
        'flowchart'=>['FLOWCHART','導線・フローの比較'],
        'decisions'=>['WHAT WE CUT AND WHY','方針・判断の理由','solution'],
        'trust'=>['TRUST & CREDIBILITY','信頼を高める設計'],
        'prototypes'=>['PROTOTYPES','プロトタイプ'],
        'find_coaches'=>['DESIGN IMPROVEMENT 01','改善例 1'],
        'coach_page'=>['DESIGN IMPROVEMENT 02','改善例 2'],
        'profile_page'=>['DESIGN IMPROVEMENT 03','改善例 3'],
        'search'=>['SEARCH EXPERIENCE','改善例 4'],
        'in_motion'=>['IN MOTION','動きのある画面'],
        'promo'=>['PROMO','展開・プロモーション'],
        'design_kit'=>['DESIGN KIT','デザインシステム'],
        'outcomes'=>['OUTCOMES','成果','result'],
        'goal'=>['GOAL','目指した体験'],
        'reflection'=>['REFLECTION','振り返り'],
        'next_steps'=>["WHAT I'D DO DIFFERENTLY",'今後の改善'],
    ];
}

/** Read ACF's persisted values even while the plugin is inactive. No data migration. */
function rn_case_value($id, $key, $legacy = '') {
    $name = 'rn_case_' . $key;
    $value = metadata_exists('post', $id, $name) ? get_post_meta($id, $name, true)
        : ($legacy ? get_post_meta($id, '_rn_' . $legacy, true) : '');
    return is_scalar($value) ? (string) $value : '';
}
/** Per-project background: accept only a valid hex color, never arbitrary CSS. */
function rn_case_background_color($id) {
    return sanitize_hex_color(rn_case_value($id, 'background_color')) ?: '';
}
function rn_case_text_color($id) {
    return sanitize_hex_color(rn_case_value($id, 'text_color')) ?: '';
}
/** WCAG sRGB relative luminance for validated opaque hex colors. */
function rn_case_luminance($hex) {
    $hex = ltrim(sanitize_hex_color($hex) ?: '#fffefb', '#');
    if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
    $rgb = array_map(static function ($value) {
        $channel = hexdec($value) / 255;
        return $channel <= .04045 ? $channel / 12.92 : pow(($channel + .055) / 1.055, 2.4);
    }, str_split($hex, 2));
    return .2126*$rgb[0] + .7152*$rgb[1] + .0722*$rgb[2];
}
function rn_case_contrast_ratio($foreground, $background) {
    $first = rn_case_luminance($foreground); $second = rn_case_luminance($background);
    return (max($first,$second)+.05) / (min($first,$second)+.05);
}
/** Keep brand blue when readable; otherwise mix toward the safer light/dark end. */
function rn_case_accent_color($background) {
    $background = sanitize_hex_color($background) ?: '#fffefb';
    $brand = '#00668c';
    if (rn_case_contrast_ratio($brand,$background) >= 4.5) { return $brand; }
    $endpoint = rn_case_contrast_ratio('#ffffff',$background) > rn_case_contrast_ratio('#000000',$background) ? 255 : 0;
    $channels = [0,102,140];
    for ($step=1;$step<=255;$step++) {
        $mix = array_map(static function ($channel) use ($endpoint,$step) { return (int)round($channel+($endpoint-$channel)*$step/255); },$channels);
        $candidate = sprintf('#%02x%02x%02x',...$mix);
        // Test the final rounded color, without rounding the contrast ratio.
        if (rn_case_contrast_ratio($candidate,$background) >= 4.5) { return $candidate; }
    }
    return $endpoint ? '#ffffff' : '#000000';
}

add_action('wp_enqueue_scripts', function () {
    if (!is_singular('rn_work')) { return; }
    $color = rn_case_background_color(get_queried_object_id());
    if ($color) { wp_add_inline_style('rnstyle', 'body.single-rn_work{--case-background:'.$color.';}'); }
    $accent = rn_case_accent_color($color);
    wp_add_inline_style('rnstyle', 'body.single-rn_work .case-study{--deep-accent:'.$accent.';}');
    $text = rn_case_text_color(get_queried_object_id());
    if ($text) { wp_add_inline_style('rnstyle', 'body.single-rn_work .case-study{--main-text:'.$text.';--secondary-text:'.$text.';color:'.$text.';}'); }
}, 20);

function rn_case_image_id($id, $key) {
    $image = absint(rn_case_value($id, $key));
    return $image && wp_attachment_is_image($image) ? $image : 0;
}
/** Legacy image slot names now accept image/video attachment IDs. */
function rn_case_media_id($id, $key) {
    $media=absint(rn_case_value($id,$key));
    return $media && (wp_attachment_is_image($media) || in_array(get_post_mime_type($media),['video/mp4','video/webm'],true)) ? $media : 0;
}
function rn_case_media_order($id,$section) {
    $order=[];
    foreach (explode(',',rn_case_value($id,$section.'_media_order')) as $slot) {
        if (preg_match('/^[1-5]$/',trim($slot))) { $order[]=(int)$slot; }
    }
    return array_values(array_unique(array_merge($order,[1,2,3,4,5])));
}
function rn_case_media_figure($media) {
    if (wp_attachment_is_image($media)) { rn_case_figure($media); return; }
    if (!in_array(get_post_mime_type($media),['video/mp4','video/webm'],true)) { return; }
    $url=wp_get_attachment_url($media);if (!$url) { return; }
    $meta=wp_get_attachment_metadata($media) ?: [];
    $poster=get_post_thumbnail_id($media);
    echo '<figure class="case-figure case-figure--video"><video controls playsinline preload="metadata" aria-label="'.esc_attr(get_the_title($media)).'"';
    if (!empty($meta['width']) && !empty($meta['height'])) { echo ' width="'.absint($meta['width']).'" height="'.absint($meta['height']).'"'; }
    if ($poster) { echo ' poster="'.esc_url(wp_get_attachment_image_url($poster,'large')).'"'; }
    echo '><source src="'.esc_url($url).'" type="'.esc_attr(get_post_mime_type($media)).'"><a href="'.esc_url($url).'">動画を開く</a></video>';
    $caption=wp_get_attachment_caption($media);if ($caption) { echo '<figcaption>'.esc_html($caption).'</figcaption>'; }
    echo '</figure>';
}
function rn_case_figure($image, $eager = false) {
    if (!$image) { return; }
    echo '<figure class="case-figure">' . wp_get_attachment_image($image, 'full', false, [
        'loading'=>$eager ? 'eager' : 'lazy', 'decoding'=>'async',
        'sizes'=>'(max-width: 767px) calc(100vw - 48px), (max-width: 996px) calc(100vw - 96px), 900px',
    ]);
    $caption = wp_get_attachment_caption($image);
    if ($caption) { echo '<figcaption>' . esc_html($caption) . '</figcaption>'; }
    echo '</figure>';
}
function rn_case_metrics($id, $section) {
    $metrics = [];
    if (!in_array($section, ['problem','outcomes'], true)) { return $metrics; }
    for ($i=1; $i<=3; $i++) {
        $value = rn_case_value($id, $section . '_metric_' . $i);
        $label = rn_case_value($id, $section . '_metric_label_' . $i);
        if (trim($value) !== '' && trim($label) !== '') { $metrics[] = [$value, $label]; }
    }
    return $metrics;
}
function rn_case_render_media($images) {
    if (!$images) { return; }
    $images = array_slice(array_values($images), 0, 5);
    $count = count($images);
    if ($count === 1) { echo '<div class="case-media">'; rn_case_media_figure($images[0]); echo '</div>'; return; }
    $track_id = wp_unique_id('case-media-track-');
    echo '<div class="case-media case-media--scroll" data-case-media><div class="case-media-track" id="'.esc_attr($track_id).'" tabindex="0" role="region" aria-roledescription="カルーセル" aria-label="セクションのメディア。横スクロールで閲覧できます">';
    foreach ($images as $image) { rn_case_media_figure($image); }
    echo '</div><div class="case-media-dots" role="group" aria-label="表示するメディアを選択">';
    for ($i=0; $i<$count; $i++) {
        echo '<button type="button" class="case-media-dot" aria-label="メディア'.($i+1).' / '.$count.'を表示" aria-controls="'.esc_attr($track_id).'" aria-current="'.($i===0?'true':'false').'"></button>';
    }
    echo '</div></div>';
}
function rn_case_render_sections($id) {
    $visual_sections = ['flowchart','find_coaches','coach_page','profile_page','search','in_motion','promo','design_kit'];
    foreach (rn_case_sections() as $key=>$definition) {
        $has_new_body = metadata_exists('post', $id, 'rn_case_' . $key . '_body');
        $body = rn_case_value($id, $key . '_body', $definition[2] ?? '');
        $images = [];
        foreach (rn_case_media_order($id,$key) as $i) { $media=rn_case_media_id($id,$key.'_image_'.$i); if ($media) { $images[]=$media; } }
        $metrics = rn_case_metrics($id, $key);
        if (trim($body) === '' && !$images && !$metrics) { continue; }
        $title = rn_case_value($id, $key . '_title');
        if (trim($title) === '') { $title = !$has_new_body && !empty($definition[2]) && $body !== '' ? strtoupper($definition[2]) : $definition[0]; }
        $media_first = in_array($key, $visual_sections, true);
        $position=rn_case_value($id,$key.'_media_position');
        if ($position==='before') { $media_first=true; } elseif ($position==='after') { $media_first=false; }
        $evidence = $key === 'problem' && $metrics && count($images) === 1 && trim($body) !== '';
        $layout = $metrics ? 'metrics' : ($media_first ? 'visual' : 'narrative');
        $body_html = $has_new_body ? wp_kses_post(wpautop($body)) : wpautop(esc_html($body));
        echo '<section class="case-section case-section--' . esc_attr($layout) . '" id="case-' . esc_attr($key) . '" aria-labelledby="case-heading-' . esc_attr($key) . '"><div class="case-section-inner"><h2 class="case-section-title" id="case-heading-' . esc_attr($key) . '">' . esc_html($title) . '</h2>';
        if ($metrics) {
            echo '<dl class="case-metrics">';
            foreach ($metrics as $metric) { echo '<div><dt>' . esc_html($metric[1]) . '</dt><dd>' . esc_html($metric[0]) . '</dd></div>'; }
            echo '</dl>';
        }
        if ($evidence) { echo '<div class="case-evidence">'; }
        if ($media_first || $evidence) { rn_case_render_media($images); }
        if (trim($body) !== '') { echo '<div class="case-richtext editor-content">' . $body_html . '</div>'; }
        if (!$media_first && !$evidence) { rn_case_render_media($images); }
        if ($evidence) { echo '</div>'; }
        echo '</div></section>';
    }
}

// Field definitions are stored in ACF database groups. Import config/acf-field-groups.json on a new installation.
// Display old values in the new controls without writing or copying metadata.
foreach (['client','role','year','external_url','problem_body','decisions_body','outcomes_body'] as $rn_case_field) {
    add_filter('acf/load_value/name=rn_case_' . $rn_case_field, function ($value, $post_id, $field) {
        if (!is_numeric($post_id) || get_post_type((int)$post_id) !== 'rn_work' || metadata_exists('post', (int)$post_id, $field['name'])) { return $value; }
        $legacy = ['problem_body'=>'challenge','decisions_body'=>'solution','outcomes_body'=>'result'];
        $key = substr($field['name'], strlen('rn_case_'));
        $old = get_post_meta((int)$post_id, '_rn_' . ($legacy[$key] ?? $key), true);
        return $old !== '' ? $old : $value;
    }, 10, 3);
}
unset($rn_case_field);
add_action('add_meta_boxes', function () {
    if (function_exists('acf_add_local_field_group')) { remove_meta_box('rnstyle-details','rn_work','normal'); }
}, 20);

/** One published project becomes one gallery card; ACF remains the source of truth. */
function rn_gallery_tags($id) {
    if (metadata_exists('post', $id, 'rn_case_gallery_tags')) {
        $labels = preg_split('/[,、\r\n]+/u', rn_case_value($id, 'gallery_tags'));
    } else {
        $terms = get_the_terms($id, 'work_category');
        $labels = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'name') : [];
    }
    $tags = [];
    foreach ($labels as $label) {
        $label = trim(sanitize_text_field($label));
        if ($label === '') { continue; }
        $canonical = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
        $key = substr(hash('sha256', $canonical), 0, 12);
        $tags[$key] = $label;
    }
    return $tags;
}
function rn_gallery_image_id($id) {
    return rn_case_image_id($id, 'orbit_image') ?: rn_case_image_id($id, 'card_image') ?: get_post_thumbnail_id($id) ?: rn_case_image_id($id, 'hero_image');
}
function rn_gallery_projects() {
    return get_posts(['post_type'=>'rn_work', 'post_status'=>'publish', 'numberposts'=>-1,
        'orderby'=>['menu_order'=>'ASC','date'=>'DESC'], 'suppress_filters'=>false]);
}

/** Initial DB definition only. Never registered as a local/read-only ACF group. */
function rn_gallery_acf_definition() {
    return [
        'key'=>'group_rn_gallery', 'title'=>'RNSTYLE 一覧・回転ギャラリー', 'active'=>true,
        'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'rn_work']]],
        'position'=>'normal', 'style'=>'default', 'menu_order'=>-20, 'label_placement'=>'top',
        'fields'=>[
            ['key'=>'field_rn_gallery_guide','label'=>'一覧への掲載','name'=>'','type'=>'message','message'=>'公開した制作実績1件につき、Worksに1枚表示します。画像・タグは回転表示／グリッド／ポップアップで共通です。','required'=>0],
            ['key'=>'field_rn_case_orbit_image','label'=>'回転・グリッド用サムネイル','name'=>'rn_case_orbit_image','type'=>'image','required'=>0,'return_format'=>'id','preview_size'=>'medium','library'=>'all','instructions'=>'1件につき1枚。未設定時は基本情報のカード画像、アイキャッチ、メイン画像の順で表示します。横長（4:3程度）を推奨。'],
            ['key'=>'field_rn_case_gallery_tags','label'=>'絞り込みタグ','name'=>'rn_case_gallery_tags','type'=>'textarea','required'=>0,'rows'=>2,'max_length'=>400,'new_lines'=>'','instructions'=>'カンマまたは改行で区切って入力。例 Web, UI/UX, WordPress。同じタグは一覧で1つにまとめられます。'],
            ['key'=>'field_rn_case_preview_intro','label'=>'ポップアップの紹介文','name'=>'rn_case_preview_intro','type'=>'textarea','required'=>0,'rows'=>3,'max_length'=>240,'new_lines'=>'','instructions'=>'空欄なら基本情報の紹介文／抜粋を表示します。'],
            ['key'=>'field_rn_case_is_sample','label'=>'仮実績として表示','name'=>'rn_case_is_sample','type'=>'true_false','required'=>0,'default_value'=>0,'ui'=>1,'ui_on_text'=>'仮実績','ui_off_text'=>'通常','instructions'=>'仮登録の間はオン。グリッドとポップアップにSAMPLEラベルを表示します。'],
        ],
    ];
}
function rn_gallery_install_fields() {
    if (!function_exists('acf_import_field_group')) { return new WP_Error('acf_missing', 'ACFを有効にしてください。'); }
    $existing = get_posts(['post_type'=>'acf-field-group','post_status'=>['publish','draft','acf-disabled','trash'], 'name'=>'group_rn_gallery', 'numberposts'=>1]);
    if ($existing) {
        if ($existing[0]->post_status === 'trash') { return new WP_Error('acf_trashed', '一覧グループがゴミ箱にあります。ACF画面から復元してください。'); }
        return $existing[0]->ID;
    }
    $group = acf_import_field_group(rn_gallery_acf_definition());
    return !empty($group['ID']) ? $group['ID'] : new WP_Error('acf_import', 'フィールドグループを追加できませんでした。');
}

// Setup is a deliberate, nonce-protected admin action, never an automatic frontend write.
add_action('rnstyle_gallery_admin', function () {
    echo '<section style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin:20px 0"><h2>実績ギャラリーの設定</h2><p>ACFに画像・絞り込みタグ・紹介文の編集欄を追加します。既存グループの設定は上書きしません。</p>';
    if (isset($_GET['gallery_setup'])) { echo '<div class="notice notice-success inline"><p>ギャラリー設定を保存しました。制作実績の編集画面で確認できます。</p></div>'; }
    if (!empty($_GET['gallery_error'])) { echo '<div class="notice notice-error inline"><p>'.esc_html(sanitize_text_field(wp_unslash($_GET['gallery_error']))).'</p></div>'; }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('rnstyle_gallery_setup');
    echo '<input type="hidden" name="action" value="rnstyle_gallery_setup"><button class="button button-secondary" name="gallery_task" value="fields">ACFの一覧編集欄を追加</button><hr><h3>仮実績の登録</h3><p>本サイトを登録し、公開実績が合計7件になるまで表示確認用の仮実績を追加します。既存の実績は削除・上書きしません。追加分にはSAMPLEラベルを付けます。</p><label>本サイトのキャプチャ画像ID（空欄は付属キャプチャ） <input type="number" min="1" name="gallery_site_image" class="small-text"></label><p><button class="button button-secondary" name="gallery_task" value="samples">本サイトと不足分の仮実績を登録</button></p></form></section>';
});
add_action('admin_post_rnstyle_gallery_setup', function () {
    if (!current_user_can('manage_options')) { wp_die('権限がありません。', '', ['response'=>403]); }
    check_admin_referer('rnstyle_gallery_setup');
    $result = rn_gallery_install_fields();
    if (!is_wp_error($result) && isset($_POST['gallery_task']) && $_POST['gallery_task'] === 'samples') {
        $image = isset($_POST['gallery_site_image']) ? absint($_POST['gallery_site_image']) : 0;
        $result = rn_gallery_seed_examples($image);
    }
    $args = is_wp_error($result) ? ['gallery_error'=>$result->get_error_message()] : ['gallery_setup'=>1];
    wp_safe_redirect(add_query_arg($args, admin_url('themes.php?page=rnstyle')));
    exit;
});

/** Import a bundled, fixed sample image into the media library once. */
function rn_gallery_sample_attachment($file, $post_id) {
    $existing = get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'fields'=>'ids','meta_key'=>'_rn_gallery_sample_asset','meta_value'=>$file]);
    if ($existing) { return $existing[0]; }
    $path = get_template_directory().'/assets/images/'.basename($file);
    if (!is_readable($path)) { return new WP_Error('sample_asset', '仮実績用画像がありません。'); }
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $tmp = wp_tempnam($file);
    if (!$tmp || !copy($path, $tmp)) { return new WP_Error('sample_asset', '画像の準備に失敗しました。'); }
    $image = media_handle_sideload(['name'=>'rnstyle-sample-'.basename($file),'tmp_name'=>$tmp], $post_id);
    if (is_wp_error($image)) { if (is_file($tmp)) { unlink($tmp); } return $image; }
    update_post_meta($image, '_rn_gallery_sample_asset', $file);
    return $image;
}
function rn_gallery_seed_examples($site_image) {
    if (!$site_image && is_readable(get_template_directory().'/assets/images/rnstyle-site-capture.jpg')) {
        $site_image = rn_gallery_sample_attachment('rnstyle-site-capture.jpg', 0);
        if (is_wp_error($site_image)) { return $site_image; }
    }
    if (!$site_image || !wp_attachment_is_image($site_image)) { return new WP_Error('site_capture', '本サイトのキャプチャをメディアに登録し、画像IDを入力してください。'); }
    if (!add_option('rnstyle_gallery_import_lock', time(), '', 'no')) {
        $started = (int) get_option('rnstyle_gallery_import_lock');
        if ($started && $started < time()-300) { delete_option('rnstyle_gallery_import_lock'); }
        return new WP_Error('sample_busy', '仮実績の登録処理中です。少し待ってからご確認ください。');
    }
    try {
    $count = (int) wp_count_posts('rn_work')->publish;
    $examples = [
        ['rnstyle-site','RNSTYLE コーポレートサイト','Web, UI/UX, WordPress',$site_image,'本サイトのデザインとWordPressオリジナルテーマ実装。実績をACFで管理し、回転ギャラリー・タグ絞り込み・グリッド表示を備えています。'],
        ['sample-brand','ブランドサイト（仮実績）','Web, Brand','28681.png','ブランドの世界観を伝えるWebサイトの表示確認用サンプル。'],
        ['sample-commerce','ECサイト（仮実績）','Web, EC','6b5b0.png','商品との出会いから購入までの導線を想定した表示確認用サンプル。'],
        ['sample-product','プロダクトUI（仮実績）','UI/UX, Product','29c75.png','プロダクトの操作画面を想定した表示確認用サンプル。'],
        ['sample-mobile','モバイル体験（仮実績）','UI/UX, Mobile','43018.png','スマートフォンでの使いやすさを想定した表示確認用サンプル。'],
        ['sample-graphic','グラフィックデザイン（仮実績）','Graphic, Brand','53476.png','紙面やビジュアル表現を想定した表示確認用サンプル。'],
        ['sample-corporate','コーポレートサイト（仮実績）','Web, WordPress','ed6fb.png','企業情報を伝えるWebサイトの表示確認用サンプル。'],
    ];
    foreach ($examples as $index=>$example) {
        [$key,$title,$tags,$asset,$intro] = $example;
        $found = get_posts(['post_type'=>'rn_work','post_status'=>['publish','draft','pending','private'],'numberposts'=>1,'meta_key'=>'_rn_gallery_example','meta_value'=>$key]);
        if ($found) { continue; }
        if ($index > 0 && $count >= 7) { break; }
        $content = $index === 0 ? '本サイトの制作・実装内容を仮登録した実績です。' : '一覧や絞り込み、詳細ページの表示を確認するための仮実績です。実際の受託案件を示すものではありません。';
        $id = wp_insert_post(['post_type'=>'rn_work','post_status'=>'draft','post_name'=>$key,'post_title'=>$title,'post_content'=>'<!-- wp:paragraph --><p>'.esc_html($content).'</p><!-- /wp:paragraph -->','post_excerpt'=>$intro,'menu_order'=>$index+10,'meta_input'=>['_rn_gallery_example'=>$key]], true);
        if (is_wp_error($id)) { return $id; }
        $image = is_int($asset) ? $asset : rn_gallery_sample_attachment($asset, $id);
        if (is_wp_error($image)) { return $image; }
        set_post_thumbnail($id, $image);
        $values = ['orbit_image'=>$image,'card_image'=>$image,'hero_image'=>$image,'gallery_tags'=>$tags,'preview_intro'=>$intro,'intro'=>$intro,'is_sample'=>1,'client'=>$index===0?'RNSTYLE':'表示確認用サンプル','role'=>$index===0?'Design / WordPress Development':'表示確認用サンプル','year'=>'2026','context_body'=>$content];
        if ($index === 0) { $values['external_url']=home_url('/'); }
        foreach ($values as $key=>$value) {
            update_post_meta($id, 'rn_case_'.$key, $value);
            update_post_meta($id, '_rn_case_'.$key, 'field_rn_case_'.$key);
        }
        $published = wp_update_post(['ID'=>$id,'post_status'=>'publish'], true);
        if (is_wp_error($published)) { return $published; }
        $count++;
    }
    return true;
    } finally { delete_option('rnstyle_gallery_import_lock'); }
}

// Keep typed section labels and their standard placeholders visually identical.
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'post' && $screen->post_type === 'rn_work') {
        wp_enqueue_style('rnstyle-case-admin', get_theme_file_uri('/assets/css/case-admin.css'), [], RNSTYLE_VERSION);
    }
});
