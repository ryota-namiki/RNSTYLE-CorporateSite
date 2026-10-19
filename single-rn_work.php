<?php
get_header();
while (have_posts()) : the_post();
    $id = get_the_ID();
    $facts = [];
    foreach (['client'=>'CLIENT','services'=>'SERVICES','year'=>'DATE','role'=>'ROLE'] as $key=>$label) {
        $value = rn_case_value($id,$key,$key==='services'?'':$key);
        if (trim($value)!=='') { $facts[$label]=$value; }
    }
    $url = esc_url(rn_case_value($id,'external_url','external_url'));
    $intro = rn_case_value($id,'intro');
    $hero = rn_case_image_id($id,'hero_image');
    $mobile = rn_case_image_id($id,'hero_mobile');
    $gallery = array_filter(array_map('absint',explode(',',get_post_meta($id,'_rn_gallery',true))));
    $has_visual = $hero || $gallery || has_post_thumbnail();
    $terms = get_the_terms($id,'work_category');
?>
<article class="case-study">
    <header class="case-header container container--wide">
        <p class="case-category eyebrow"><?php echo rn_eyebrow_art($terms && !is_wp_error($terms) ? implode(' / ',wp_list_pluck($terms,'name')) : 'WORKS'); ?></p>
        <?php rn_case_entry_title($id); ?>
        <?php if(trim($intro)!=='' || $facts || $url): ?>
        <div class="case-overview<?php echo trim($intro)===''?' case-overview--facts-only':''; ?>">
            <?php if(trim($intro)!==''): ?><p class="case-intro"><?php echo nl2br(esc_html($intro)); ?></p><?php endif; ?>
            <?php if($facts || $url): ?><dl class="work-facts">
                <?php foreach($facts as $label=>$value): ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?>
                <?php if($url): ?><div><dt>LINK</dt><dd><a href="<?php echo $url; ?>" target="_blank" rel="noopener noreferrer">View project <span aria-hidden="true">↗</span><span class="screen-reader-text">（新しいタブで開きます）</span></a></dd></div><?php endif; ?>
            </dl><?php endif; ?>
        </div>
        <?php endif; ?>
        <a class="case-scroll" href="#<?php echo $has_visual?'case-visual':'case-content'; ?>"><span>SCROLL TO EXPLORE</span><span aria-hidden="true">↓</span></a>
    </header>
    <?php if($has_visual): ?><div id="case-visual" class="case-visual">
        <?php if($hero): ?>
        <div class="container container--wide case-hero"><figure class="case-figure"><picture>
            <?php if($mobile): ?><source media="(max-width: 767px)" srcset="<?php echo esc_attr(wp_get_attachment_image_srcset($mobile,'full') ?: wp_get_attachment_image_url($mobile,'full')); ?>" sizes="calc(100vw - 48px)"><?php endif; ?>
            <?php echo wp_get_attachment_image($hero,'full',false,['loading'=>'eager','fetchpriority'=>'high','sizes'=>'(max-width: 767px) calc(100vw - 48px), (max-width: 996px) calc(100vw - 96px), 900px']); ?>
        </picture><?php $caption=wp_get_attachment_caption($hero); if($caption): ?><figcaption><?php echo esc_html($caption); ?></figcaption><?php endif; ?></figure></div>
        <?php elseif($gallery): ?>
        <section class="fan-carousel" aria-roledescription="カルーセル" aria-label="制作実績の画像"><div class="carousel-track">
            <?php foreach($gallery as $i=>$image): ?><figure class="carousel-slide"><?php echo wp_get_attachment_image($image,'large',false,['loading'=>$i?'lazy':'eager']); ?></figure><?php endforeach; ?>
        </div><div class="carousel-controls"><button type="button" data-carousel="prev" aria-label="前の画像">←</button><button type="button" data-carousel="next" aria-label="次の画像">→</button></div></section>
        <?php elseif(has_post_thumbnail()): ?><div class="container container--wide case-hero"><?php rn_case_figure(get_post_thumbnail_id(),true); ?></div><?php endif; ?>
    </div><?php endif; ?>
    <div class="case-story" id="case-content">
        <?php rn_case_render_sections($id); ?>
        <?php if(trim(get_the_content())!==''): ?><div class="case-additional editor-content"><?php the_content(); ?></div><?php endif; ?>
    </div>
    <?php $previous=get_previous_post(); $next=get_next_post(); ?>
    <nav class="case-pagination container container--wide" aria-label="制作実績の前後移動">
        <div><?php if($previous): ?><a class="case-adjacent" href="<?php echo esc_url(get_permalink($previous)); ?>" rel="prev"><span class="eyebrow"><?php echo rn_eyebrow_art('← PREV'); ?></span><span><?php echo esc_html(get_the_title($previous)); ?></span></a><?php endif; ?></div>
        <a class="case-all-works" href="<?php echo esc_url(rn_url('works')); ?>">All Works <span aria-hidden="true">↗</span></a>
        <div><?php if($next): ?><a class="case-adjacent case-adjacent--next" href="<?php echo esc_url(get_permalink($next)); ?>" rel="next"><span class="eyebrow"><?php echo rn_eyebrow_art('NEXT →'); ?></span><span><?php echo esc_html(get_the_title($next)); ?></span></a><?php endif; ?></div>
    </nav>
</article>
<?php endwhile; get_template_part('template-parts/cta'); get_footer(); ?>
