<?php
/** Shared collection for orbit, grid, filters and the project preview. */
$projects = rn_gallery_projects();
$all_tags = [];
foreach ($projects as $project) { $all_tags += rn_gallery_tags($project->ID); }
$selected = isset($_GET['work_tag']) && is_scalar($_GET['work_tag']) ? sanitize_key(wp_unslash($_GET['work_tag'])) : '';
$grid = isset($_GET['view']) && is_scalar($_GET['view']) && $_GET['view'] === 'grid';
$base = rn_url('works');
$view_args = $grid ? ['view'=>'grid'] : [];
$hint_id = wp_unique_id('orbit-hint-');
$dialog_id = wp_unique_id('work-preview-');
$visible = array_values(array_filter($projects, static function ($project) use ($selected) { return $selected === '' || isset(rn_gallery_tags($project->ID)[$selected]); }));
$visible_ids = wp_list_pluck($visible, 'ID');
?>
<section class="works-orbit<?php echo $grid ? ' is-grid' : ''; ?>" aria-label="制作実績ギャラリー" data-filter="<?php echo esc_attr($selected); ?>">
    <div class="orbit-plane" role="group" tabindex="0" aria-label="制作実績。左右にドラッグして回転" aria-describedby="<?php echo esc_attr($hint_id); ?>">
        <?php foreach ($projects as $project) :
            $id = $project->ID;
            $tags = rn_gallery_tags($id);
            $image = rn_gallery_image_id($id);
            $title = get_the_title($id);
            $intro = rn_case_value($id, 'preview_intro') ?: rn_case_value($id, 'intro') ?: get_the_excerpt($project);
            $sample = rn_case_value($id, 'is_sample') === '1';
            $slot = array_search($id, $visible_ids, true);
            $angle = $slot !== false && count($visible) > 1 ? -$slot * 2 * M_PI / count($visible) : 0;
            $depth = (cos($angle) + 1) / 2;
            $style = sprintf('--orbit-x:%.6F;--orbit-y:%.6F;--orbit-scale:%.6F;--orbit-opacity:%.6F;--orbit-brightness:%.6F;--orbit-saturation:%.6F;z-index:%d', sin($angle), count($visible) > 1 ? cos($angle) : 0, .7 + .38 * $depth, .75 + .25 * $depth, .4 + .6 * $depth, .6 + .4 * $depth, round(10 + 80 * $depth));
            $preview = ['title'=>rn_case_value($id,'client','client') ?: $title,'intro'=>wp_strip_all_tags($intro),'image'=>$image ? wp_get_attachment_image_url($image, 'large') : '', 'alt'=>$image ? get_post_meta($image, '_wp_attachment_image_alt', true) : '', 'tags'=>array_values($tags), 'year'=>rn_case_value($id,'year','year'), 'sample'=>$sample];
        ?>
        <a class="orbit-art" href="<?php echo esc_url(get_permalink($id)); ?>" aria-label="<?php echo esc_attr($preview['title'] . 'のプレビュー'); ?>" data-work-id="<?php echo (int)$id; ?>" data-tags="<?php echo esc_attr(implode(' ', array_keys($tags))); ?>" data-preview="<?php echo esc_attr(wp_json_encode($preview)); ?>" style="<?php echo esc_attr($style); ?>"<?php echo $slot === false ? ' hidden' : ''; ?>>
            <div class="orbit-media">
                <?php if ($image) : echo wp_get_attachment_image($image, 'rnstyle-work', false, ['alt'=>'', 'draggable'=>'false', 'loading'=>'lazy', 'decoding'=>'async', 'sizes'=>'(max-width:767px) 85vw, (max-width:1200px) 50vw, 580px']); else : ?>
                <div class="orbit-image-placeholder"><span><?php echo esc_html($preview['title']); ?></span></div>
                <?php endif; ?>
            </div>
            <div class="orbit-card-info">
                <p class="orbit-card-meta"><span class="orbit-card-label"><?php echo esc_html(implode(' / ',array_values($tags))); ?></span><?php if ($preview['year']) { echo ' <span>'.esc_html($preview['year']).'</span>'; } ?></p>
                <h2><?php echo esc_html($preview['title']); ?></h2>
                <p class="orbit-card-intro"><?php echo esc_html(wp_strip_all_tags($intro)); ?></p>
                <ul class="orbit-card-tags" aria-label="タグ"><?php foreach ($tags as $label) : ?><li><?php echo esc_html($label); ?></li><?php endforeach; ?></ul>
                <span class="orbit-card-enter">VIEW PROJECT <span aria-hidden="true">↗</span></span>
            </div>
        </a>
        <?php endforeach; ?>
        <p class="orbit-empty"<?php echo $visible ? ' hidden' : ''; ?>>該当する制作実績はありません。</p>
    </div>
    <div class="orbit-footer">
        <nav class="works-filter" aria-label="タグで実績を絞り込む">
            <a href="<?php echo esc_url(add_query_arg($view_args, $base)); ?>" data-work-filter=""<?php echo $selected === '' ? ' aria-current="true"' : ''; ?>>ALL</a>
            <?php foreach ($all_tags as $key=>$label) : ?>
            <a href="<?php echo esc_url(add_query_arg(array_merge($view_args, ['work_tag'=>$key]), $base)); ?>" data-work-filter="<?php echo esc_attr($key); ?>"<?php echo $selected === $key ? ' aria-current="true"' : ''; ?>><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>
        <a class="orbit-view-toggle" href="<?php echo esc_url(add_query_arg(array_merge($selected ? ['work_tag'=>$selected] : [], $grid ? [] : ['view'=>'grid']), $base)); ?>" data-orbit-view aria-label="<?php echo $grid ? '回転表示に切り替え' : 'グリッド表示に切り替え'; ?>"><?php echo $grid ? 'ORBIT VIEW' : 'GRID VIEW'; ?> <span aria-hidden="true">↗</span></a>
    </div>
    <p class="orbit-hint" id="<?php echo esc_attr($hint_id); ?>"><span aria-hidden="true">↔ DRAG TO ROTATE · CLICK TO EXPLORE</span><span class="screen-reader-text">左右にドラッグ、または左右矢印キーで回転。実績をクリックすると紹介が開き、詳細ページへ進めます。</span></p>
    <p class="orbit-result-count screen-reader-text" role="status" aria-live="polite"><?php echo count($visible); ?>件の制作実績</p>
    <dialog class="work-preview" id="<?php echo esc_attr($dialog_id); ?>" aria-labelledby="<?php echo esc_attr($dialog_id); ?>-title">
        <button class="work-preview-close" type="button" aria-label="プレビューを閉じる">×</button>
        <div class="work-preview-image"></div>
        <div class="work-preview-copy">
            <p class="work-preview-meta"></p>
            <h2 id="<?php echo esc_attr($dialog_id); ?>-title"></h2>
            <p class="work-preview-intro"></p>
            <ul class="orbit-card-tags work-preview-tags" aria-label="タグ"></ul>
            <div class="work-preview-navigation"><button type="button" data-preview-step="-1">← PREV</button><button type="button" data-preview-step="1">NEXT →</button></div>
            <a class="button work-preview-enter" href="<?php echo esc_url($base); ?>">実績詳細を見る <span aria-hidden="true">↗</span></a>
        </div>
    </dialog>
</section>
