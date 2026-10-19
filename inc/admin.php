<?php
/* RNSTYLE gallery 1.1.0 */
if (!defined('ABSPATH')) { exit; }
add_action('admin_menu', function () { add_theme_page('RNSTYLE 設定', 'RNSTYLE 設定', 'manage_options', 'rnstyle', 'rn_admin_page'); });
add_action('admin_init', function () {
    register_setting('rnstyle', 'rnstyle_options', ['sanitize_callback'=>function($input){ $clean=[];foreach(rn_defaults()['options'] as $key=>$default){$value=isset($input[$key])&&is_string($input[$key])?$input[$key]:$default;$clean[$key]=$key==='contact_email'?sanitize_email($value):sanitize_textarea_field($value);}return $clean;}]);
});
function rn_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    $labels=['hero_pc_images'=>'ヒーローPC画像（元ファイル名|メディア画像ID、1行1件）','hero_sp_images'=>'ヒーローSP画像（元ファイル名|メディア画像ID、1行1件）','hero_title'=>'メインコピー（改行可能）','hero_subtitle'=>'サブコピー','hero_body'=>'ヒーロー本文','statement_title'=>'ステートメント見出し','statement_body'=>'ステートメント本文','problems'=>'相談内容（1行1項目）','reasons'=>'選ばれる理由（1行1項目）','about_title'=>'Aboutプレビュー見出し','about_body'=>'Aboutプレビュー本文','cta_title'=>'CTA見出し','cta_body'=>'CTA本文','prices'=>'料金（名称|金額、1行1項目）','process_steps'=>'Process（英語名|日本語名|本文、1行1工程）','philosophy_body'=>'About・Philosophy本文','message_body'=>'About・Message本文','contact_email'=>'お問い合わせ送信先（空欄なら管理者メール）','contact_hours'=>'対応時間'];
    echo '<div class="wrap"><h1>RNSTYLE 設定</h1><p>サービス・実績・自社プロダクトは左メニューから編集できます。画像はメディアライブラリへアップロードしてください。</p>';
    do_action('rnstyle_gallery_admin');
    if(isset($_GET['seeded'])){echo '<div class="notice notice-success"><p>初期コンテンツを作成しました。既存ページは上書きしていません。Privacy等の既存下書きは、固定ページ画面で内容を確認して公開してください。</p></div>';}
    echo '<form action="options.php" method="post">';settings_fields('rnstyle');echo '<table class="form-table">';
    foreach(rn_defaults()['options'] as $key=>$v){echo '<tr><th><label for="rn-'.esc_attr($key).'">'.esc_html($labels[$key]??$key).'</label></th><td>';if($key==='contact_email'){echo '<input class="regular-text" type="email" id="rn-'.esc_attr($key).'" name="rnstyle_options['.esc_attr($key).']" value="'.esc_attr(rn_option($key)).'">';}else{echo '<textarea class="large-text" rows="'.(str_contains($key,'body')?6:3).'" id="rn-'.esc_attr($key).'" name="rnstyle_options['.esc_attr($key).']">'.esc_textarea(rn_option($key)).'</textarea>';}echo '</td></tr>';}
    echo '</table>';submit_button();echo '</form><hr><h2>初期コンテンツ</h2><p>Home・Service・Works・About・Contact・News・Privacy・Owned Productsと、Figmaのサービス／実績の初期データを作ります。既存スラッグは上書きしません。テーマ有効化だけではデータは作成されません。</p><p>設定 → 表示設定でトップページをHomeに指定してください。初期画像と実績内容はFigmaに含まれるサンプルです。公開前に掲載可否と内容をご確認ください。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('rnstyle_seed');echo '<input type="hidden" name="action" value="rnstyle_seed">';submit_button('初期コンテンツを作成','secondary');echo '</form></div>';
}
add_action('admin_post_rnstyle_seed',function(){if(!current_user_can('manage_options')){wp_die('権限がありません。',403);}check_admin_referer('rnstyle_seed');rn_seed();wp_safe_redirect(admin_url('themes.php?page=rnstyle&seeded=1'));exit;});
function rn_seed() {
    $pages=['home'=>'Home','service'=>'Service','works'=>'Works','about'=>'About','contact'=>'Contact','news'=>'News','privacy-policy'=>'プライバシーポリシー','owned-products'=>'Owned Products'];
    foreach($pages as $slug=>$title){if(!get_page_by_path($slug)){wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$slug==='privacy-policy'?file_get_contents(__DIR__.'/privacy-content.html'):'']);}}
    foreach(rn_defaults()['services'] as $i=>$s){if(get_page_by_path($s['slug'],OBJECT,'rn_service')){continue;}$id=wp_insert_post(['post_type'=>'rn_service','post_status'=>'publish','post_name'=>$s['slug'],'post_title'=>$s['title'],'post_content'=>$s['body'],'post_excerpt'=>$s['excerpt'],'menu_order'=>$i],true);if(is_wp_error($id)){continue;}foreach(['subtitle','lead','tags','price'] as $k){update_post_meta($id,'_rn_'.$k,$s[$k]);}}
    foreach(['WEB'=>'web','UI/UX'=>'uiux','GRAPHIC'=>'graphic'] as $n=>$s){if(!term_exists($s,'work_category')){wp_insert_term($n,'work_category',['slug'=>$s]);}}
    $works=[['retold-flyer','Graphic / RETOLD TOKYO ポスティングチラシ','GRAPHIC'],['retold-brochure','Graphic / RETOLD TOKYO 三つ折りパンフレット','GRAPHIC'],['jewelry-package','Graphic / ジュエリーブランドの梱包資材デザイン','GRAPHIC'],['sunlive','Web / 株式会社サンリブ コーポレートサイト','WEB']];
    foreach($works as $i=>$w){if(get_page_by_path($w[0],OBJECT,'rn_work')){continue;}$id=wp_insert_post(['post_type'=>'rn_work','post_status'=>'publish','post_name'=>$w[0],'post_title'=>$w[1],'menu_order'=>$i,'post_content'=>'<!-- wp:paragraph --><p>制作の背景や取り組みの詳細をここに入力してください。</p><!-- /wp:paragraph -->'],true);if(is_wp_error($id)){continue;}wp_set_object_terms($id,$w[2],'work_category');update_post_meta($id,'_rn_year','2026');update_post_meta($id,'_rn_role',$i===3?'プロジェクトマネジメント・制作':'アートディレクション・グラフィックデザイン・入稿データ制作');}
    flush_rewrite_rules(false);
}
add_action('add_meta_boxes',function(){foreach(['rn_work','rn_service','rn_product'] as $type){add_meta_box('rnstyle-details','RNSTYLE 詳細情報','rn_meta_box',$type,'normal','default');}});
function rn_meta_box($post) {
    wp_nonce_field('rnstyle_meta','rnstyle_meta_nonce');
    $fields=$post->post_type==='rn_service'?['subtitle'=>'日本語タイトル','lead'=>'見出し','tags'=>'タグ（カンマ区切り）','price'=>'参考価格']:['client'=>'クライアント','role'=>'担当範囲','year'=>'制作年','external_url'=>'外部URL','gallery'=>'ギャラリー画像ID（カンマ区切り）','challenge'=>'CHALLENGE','solution'=>'SOLUTION','result'=>'RESULT'];
    foreach($fields as $key=>$label){echo '<p><label><strong>'.esc_html($label).'</strong><br><textarea style="width:100%" rows="'.(in_array($key,['challenge','solution','result'],true)?4:1).'" name="rn_meta['.esc_attr($key).']">'.esc_textarea(get_post_meta($post->ID,'_rn_'.$key,true)).'</textarea></label></p>';}
    echo '<p>ギャラリー画像IDはメディアの編集URL内の post= の番号です。並び順は「順序」で指定。アイキャッチ画像はカード画像に使用します。</p>';
}
add_action('save_post',function($id){
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||wp_is_post_revision($id)||!isset($_POST['rnstyle_meta_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rnstyle_meta_nonce'])),'rnstyle_meta')||!current_user_can('edit_post',$id)){return;}
    $data=isset($_POST['rn_meta'])&&is_array($_POST['rn_meta'])?wp_unslash($_POST['rn_meta']):[];
    foreach(['subtitle','lead','tags','price','client','role','year','external_url','gallery','challenge','solution','result'] as $k){if(isset($data[$k])&&is_string($data[$k])){update_post_meta($id,'_rn_'.$k,$k==='external_url'?esc_url_raw($data[$k]):sanitize_textarea_field($data[$k]));}}
});
