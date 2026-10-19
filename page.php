<?php get_header();while(have_posts()):the_post();rn_page_hero('',get_the_title()); ?><article class="container editor-content"><?php the_content(); ?></article><?php endwhile;get_footer(); ?>
