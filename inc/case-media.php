<?php
/** Case-study image slots and native horizontal galleries (ACF Free). */
if (!defined('ABSPATH')) { exit; }

function rn_case_media_group() {
    $groups = get_posts(['post_type'=>'acf-field-group','post_status'=>['publish','draft','acf-disabled'],'post_name__in'=>['group_rn_case_story'],'numberposts'=>1]);
    return $groups ? $groups[0] : null;
}

function rn_case_media_install() {
    if (!function_exists('acf_get_fields') || !function_exists('acf_update_field')) { return new WP_Error('acf_missing','ACFを有効にしてください。'); }
    $group = rn_case_media_group();
    if (!$group) { return new WP_Error('acf_group_missing','ケーススタディのACFグループが見つかりません。'); }
    $fields = acf_get_fields($group->ID) ?: [];
    $by_name = array_column($fields, null, 'name');
    $sections = array_keys(rn_case_sections());
    foreach ($sections as $section) {
        if (empty($by_name['rn_case_'.$section.'_image_2'])) { return new WP_Error('acf_slot_missing','既存のメディア欄が見つかりません。'); }
        foreach (['_image_3','_image_4','_image_5','_media_order','_media_position'] as $suffix) {
            $existing = acf_get_field('field_rn_case_'.$section.$suffix);
            if ($existing && (int)$existing['parent'] !== (int)$group->ID) { return new WP_Error('acf_key_conflict','フィールドのキーが別グループで使用されています。'); }
        }
    }
    $ordered = [];
    $media_field = static function ($field, $name, $number) use ($group) {
        $field = $field ?: ['key'=>'field_'.$name,'name'=>$name,'label'=>'メディア '.$number,'required'=>0,'wrapper'=>['width'=>'50'],'parent'=>$group->ID];
        if (preg_match('/^画像\s*[1-5]$/u',$field['label'])) { $field['label'] = 'メディア '.$number; }
        $field['type'] = 'file'; $field['return_format'] = 'id'; $field['library'] = 'all';
        $field['mime_types'] = 'jpg,jpeg,png,webp,gif,avif,mp4,webm';
        $field['instructions'] = '画像または動画（MP4／WebM）。ハンドルをドラッグして表示順を変更できます。';
        return $field;
    };
    foreach ($fields as $field) {
        if (preg_match('/^rn_case_('.implode('|',$sections).')_(image_[345]|media_order|media_position)$/',$field['name'])) { continue; }
        if (preg_match('/^rn_case_('.implode('|',$sections).')_image_([12])$/',$field['name'],$match)) {
            $field = $media_field($field,$field['name'],(int)$match[2]);
        }
        $ordered[] = $field;
        if (!preg_match('/^rn_case_('.implode('|',$sections).')_image_2$/',$field['name'],$match)) { continue; }
        $prefix = 'rn_case_'.$match[1];
        for ($i=3;$i<=5;$i++) {
            $name=$prefix.'_image_'.$i;
            $ordered[]=$media_field($by_name[$name]??null,$name,$i);
        }
        $name=$prefix.'_media_order';
        $ordered[]=$by_name[$name]??['key'=>'field_'.$name,'name'=>$name,'label'=>'メディア表示順','type'=>'text','required'=>0,'default_value'=>'','wrapper'=>['class'=>'rn-case-media-order'],'parent'=>$group->ID];
        $name=$prefix.'_media_position';
        $ordered[]=$by_name[$name]??['key'=>'field_'.$name,'name'=>$name,'label'=>'メディアの配置','type'=>'select','required'=>0,'choices'=>['default'=>'標準の配置','before'=>'本文の前','after'=>'本文の後'],'default_value'=>'default','return_format'=>'value','parent'=>$group->ID];
    }
    foreach ($ordered as $order=>$field) {
        $field['menu_order']=$order;
        if (isset($by_name[$field['name']]) && $by_name[$field['name']]==$field) { continue; }
        if (!acf_update_field($field)) { return new WP_Error('acf_save','メディア欄を保存できませんでした。再実行できます。'); }
    }
    return $group->ID;
}

add_action('rnstyle_gallery_admin', function () {
    if (!current_user_can('manage_options')) { return; }
    echo '<h2>実績セクションのメディア管理</h2><p>画像・動画を最大5件まで登録し、ドラッグで並び替えられるACF欄へ更新します。既存の画像・本文・表示順は保持します。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('rnstyle_case_media_install');
    echo '<input type="hidden" name="action" value="rnstyle_case_media_install">';
    submit_button('実績メディアを更新（画像・動画・並び替え）', 'secondary');
    echo '</form>';
}, 40);

add_action('admin_post_rnstyle_case_media_install', function () {
    if (!current_user_can('manage_options')) { wp_die('権限がありません。',403); }
    check_admin_referer('rnstyle_case_media_install');
    $result = rn_case_media_install();
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
    wp_safe_redirect(admin_url('themes.php?page=rnstyle&case-media=installed'));
    exit;
});

/** Attach one library item without overwriting another editor's post content. */
function rn_case_media_attach($post_id,$section,$attachment,$position='after') {
    if (get_post_type($post_id)!=='rn_work' || !isset(rn_case_sections()[$section])) { return new WP_Error('invalid_section','制作実績とセクションを確認してください。'); }
    if (!wp_attachment_is_image($attachment) && !in_array(get_post_mime_type($attachment),['video/mp4','video/webm'],true)) { return new WP_Error('invalid_media','画像またはMP4／WebMを選択してください。'); }
    if (!function_exists('update_field')) { return new WP_Error('acf_missing','ACFを有効にしてください。'); }
    $slot=0;$empty=0;
    for ($i=1;$i<=5;$i++) {
        $value=absint(rn_case_value($post_id,$section.'_image_'.$i));
        if ($value===$attachment) { $slot=$i; }
        if (!$value && !$empty) { $empty=$i; }
    }
    $slot=$slot?:$empty;
    if (!$slot) { return new WP_Error('media_full','このセクションは5件登録済みです。既存メディアを外してから追加してください。'); }
    update_field('field_rn_case_'.$section.'_image_'.$slot,$attachment,$post_id);
    $order=array_values(array_filter(array_diff(rn_case_media_order($post_id,$section),[$slot]),static function($number) use($post_id,$section){ return rn_case_media_id($post_id,$section.'_image_'.$number)>0; }));$order[]=$slot;
    $order=array_merge($order,array_values(array_diff([1,2,3,4,5],$order)));
    update_field('field_rn_case_'.$section.'_media_order',implode(',',$order),$post_id);
    update_field('field_rn_case_'.$section.'_media_position',in_array($position,['default','before','after'],true)?$position:'after',$post_id);
    return $slot;
}

add_action('rnstyle_gallery_admin',function () {
    if (!current_user_can('manage_options')) { return; }
    echo '<h3>ライブラリのメディアを実績へ追加</h3><p>指定セクションの空き枠へ追加します。本文やほかのセクションは変更しません。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('rnstyle_case_media_attach');
    echo '<input type="hidden" name="action" value="rnstyle_case_media_attach"><p><label>実績ID <input type="number" name="project_id" min="1" required></label> <label>メディアID <input type="number" name="media_id" min="1" required></label></p><p><label>セクション <select name="section">';
    foreach (rn_case_sections() as $key=>$definition) { echo '<option value="'.esc_attr($key).'">'.esc_html($definition[0]).'</option>'; }
    echo '</select></label> <label>メディアの配置 <select name="position"><option value="after">本文の後</option><option value="before">本文の前</option><option value="default">標準の配置</option></select></label></p>';
    submit_button('選択したセクションへ追加','secondary');echo '</form>';
},41);

add_action('admin_post_rnstyle_case_media_attach',function () {
    if (!current_user_can('manage_options')) { wp_die('権限がありません。',403); }
    check_admin_referer('rnstyle_case_media_attach');
    $post_id=absint($_POST['project_id']??0);
    if (!current_user_can('edit_post',$post_id)) { wp_die('権限がありません。',403); }
    $result=rn_case_media_attach($post_id,sanitize_key($_POST['section']??''),absint($_POST['media_id']??0),sanitize_key($_POST['position']??'after'));
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
    wp_safe_redirect(admin_url('themes.php?page=rnstyle&case-media-attached='.$post_id));exit;
});

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && $screen->base === 'post' && $screen->post_type === 'rn_work') {
        wp_enqueue_script('rnstyle-case-media-admin',get_theme_file_uri('/assets/js/case-media-admin.js'),['acf-input','jquery-ui-sortable'],RNSTYLE_VERSION,true);
    }
});

add_action('wp_enqueue_scripts', function () {
    if (!is_singular('rn_work')) { return; }
    wp_enqueue_style('rnstyle-case-media',get_theme_file_uri('/assets/css/case-media.css'),['rnstyle'],RNSTYLE_VERSION);
    wp_enqueue_script('rnstyle-case-media',get_theme_file_uri('/assets/js/case-media.js'),[],RNSTYLE_VERSION,['strategy'=>'defer','in_footer'=>true]);
}, 20);
