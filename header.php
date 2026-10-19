<?php if (!defined('ABSPATH')) { exit; } ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main">本文へスキップ</a>
<header class="site-header"><div class="header-start"><?php rn_header_logo(); ?><nav class="desktop-nav" aria-label="メインナビゲーション"><?php rn_nav(); ?></nav></div><div class="header-actions"><?php if (function_exists('pll_the_languages')) : ?><div class="languages"><?php pll_the_languages(['show_flags'=>0,'show_names'=>1]); ?></div><?php elseif (defined('ICL_SITEPRESS_VERSION')) : do_action('wpml_add_language_selector'); endif; ?><?php rn_button(); ?><button type="button" class="menu-toggle" aria-controls="mobile-nav" aria-expanded="false"><span></span><span></span><span class="screen-reader-text">メニューを開く</span></button></div></header>
<nav id="mobile-nav" class="mobile-nav" aria-label="モバイルナビゲーション" hidden><?php rn_nav(); rn_button(); ?></nav>
<main id="main">
