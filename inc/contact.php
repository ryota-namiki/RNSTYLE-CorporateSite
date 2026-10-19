<?php
if (!defined('ABSPATH')) { exit; }
/** Returns validated fields or WP_Error; no personal data is stored in WordPress. */
function rn_validate_contact($input) {
    $fields=['company','name','email','phone','position','website','budget','deadline','message','consent'];$clean=[];$errors=new WP_Error();
    foreach($fields as $k){$v=$input[$k]??'';if(!is_string($v)){$v='';}$clean[$k]=$k==='message'?sanitize_textarea_field($v):sanitize_text_field($v);if(strlen($clean[$k])>($k==='message'?15000:1500)){$errors->add($k,'入力内容が長すぎます。');}}
    foreach(['company'=>'会社名','name'=>'お名前','email'=>'メールアドレス','phone'=>'電話番号','message'=>'ご相談内容'] as $key=>$label){if(!$clean[$key]){$errors->add($key,$label.'を入力してください。');}}
    if(!is_email($clean['email'])||preg_match('/[\r\n]/',$clean['email'])){$errors->add('email','メールアドレスの形式をご確認ください。');}
    if($clean['website']&&!filter_var($clean['website'],FILTER_VALIDATE_URL)){$errors->add('website','ホームページURLは https:// から入力してください。');}
    if($clean['consent']!=='1'){$errors->add('consent','個人情報保護方針への同意が必要です。');}
    return $errors->has_errors()?$errors:$clean;
}
function rn_contact_response($message,$status,$json,$data=[]) { if($json){wp_send_json(['success'=>$status<400,'message'=>$message]+$data,$status);} if($status<400){wp_safe_redirect(add_query_arg('sent','1',rn_url('contact')));exit;}rn_render_contact_error($message,$data['fields']??[],$data['errors']??[],$status);exit; }
function rn_render_contact_error($message,$fields,$errors,$status){status_header($status);nocache_headers();show_admin_bar(false);$GLOBALS['rn_contact_state']=['message'=>$message,'fields'=>$fields,'errors'=>$errors];get_header();get_template_part('template-parts/contact');get_footer();}
function rn_handle_contact() {
    $json=isset($_POST['rn_ajax'])&&$_POST['rn_ajax']==='1';
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){rn_contact_response('送信方法をご確認ください。',405,$json);}
    $nonce=isset($_POST['rn_nonce'])&&is_string($_POST['rn_nonce'])?sanitize_text_field(wp_unslash($_POST['rn_nonce'])):'';
    if(!wp_verify_nonce($nonce,'rnstyle_contact')){rn_contact_response('ページの有効期限が切れました。再読み込みして送信してください。',403,$json);}
    $raw=wp_unslash($_POST);$clean=rn_validate_contact($raw);
    if(is_wp_error($clean)){rn_contact_response('入力内容をご確認ください。',422,$json,['errors'=>$clean->errors,'fields'=>array_intersect_key(array_map(function($v){return is_string($v)?sanitize_textarea_field($v):'';},$raw),array_flip(['company','name','email','phone','position','website','budget','deadline','message']))]);}
    if(!empty($raw['fax_number'])){rn_contact_response('送信内容を確認できませんでした。',422,$json,['fields'=>$clean]);}
    // Trust only the server address; do not accept proxy headers from a visitor.
    $rate='rn_mail_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',wp_salt('nonce'));
    if(get_transient($rate)){rn_contact_response('少し時間をおいてから、もう一度お試しください。',429,$json,['fields'=>$clean]);}
    set_transient($rate,1,60);
    $recipient=rn_option('contact_email')?:get_option('admin_email');
    $body='RNSTYLE お問い合わせ'."\n\n";$labels=['company'=>'会社名','name'=>'お名前','email'=>'メールアドレス','phone'=>'電話番号','position'=>'役職','website'=>'URL','budget'=>'予算感','deadline'=>'希望納期','message'=>'ご相談内容'];
    foreach($labels as $key=>$label){$body.=$label."\n".$clean[$key]."\n\n";}
    $sent=wp_mail($recipient,'【RNSTYLE】Webサイトからのお問い合わせ',$body,['Content-Type: text/plain; charset=UTF-8','Reply-To: '.$clean['email']]);
    if(!$sent){delete_transient($rate);rn_contact_response('送信できませんでした。入力内容は保持されています。時間をおいて再度お試しください。',503,$json,['fields'=>$clean]);}
    rn_contact_response('お問い合わせを受け付けました。通常2営業日以内にご返信いたします。',200,$json);
}
add_action('admin_post_nopriv_rnstyle_contact','rn_handle_contact');add_action('admin_post_rnstyle_contact','rn_handle_contact');
