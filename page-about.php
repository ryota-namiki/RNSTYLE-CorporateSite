<?php
/** About — Figma PC 186:4 / SP 309:1905. */
get_header(); ?>
<div class="rn-about-page">
<section class="page-hero"><div class="container"><p class="eyebrow"><?php echo rn_eyebrow_art('ABOUT'); ?></p><h1>「らしさ」を知り、<br>次の一歩を<span class="about-title-final">ともにつくる。</span></h1></div></section>
<section class="about-philosophy"><div class="container"><p class="eyebrow"><?php echo rn_eyebrow_art('PHILOSOPHY'); ?></p><h2>事業を理解することから、<br>デザインをはじめる。</h2><div class="body-copy"><?php echo wpautop(esc_html(rn_option('philosophy_body'))); ?></div></div></section>
<section class="about-fields section-alt"><div class="container"><div class="about-fields-heading"><p class="eyebrow"><?php echo rn_eyebrow_art('WHAT WE DO'); ?></p><h2>対応できる領域</h2></div><div class="about-fields-grid"><?php foreach(rn_lines('about_fields') as $line): $field=explode('|',$line,2); ?><div class="about-field"><h3><?php echo esc_html($field[0]); ?></h3><p><?php echo esc_html($field[1]??''); ?></p></div><?php endforeach; ?></div></div></section>
<section class="about-message"><div class="container about-message-grid"><div class="about-message-copy"><p class="eyebrow"><?php echo rn_eyebrow_art('MESSAGE'); ?></p><h2>事業を理解し、<br>その先の人に届ける。</h2><div class="body-copy"><?php echo wpautop(esc_html(rn_option('message_body'))); ?><p class="signature">RNSTYLE 代表<br>並木遼太</p></div></div><figure class="about-portrait"><img src="<?php echo esc_url(rn_asset('9d15a.png')); ?>" alt="RNSTYLE 代表 並木遼太のイラスト" width="407" height="607" loading="lazy"></figure></div><?php while(have_posts()):the_post();if(trim(get_the_content())): ?><div class="container editor-content"><?php the_content(); ?></div><?php endif;endwhile; ?></section>
<?php get_template_part('template-parts/cta'); ?>
</div>
<?php get_footer(); ?>
