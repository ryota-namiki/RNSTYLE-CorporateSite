<?php
/** TOP の画像は Works の Orbit View と同じ画像IDを使用する。 */
$gallery_images = [];
$seen_images = [];
foreach (rn_gallery_projects() as $project) {
    $image_id = absint(rn_gallery_image_id($project->ID));
    if (!$image_id || isset($seen_images[$image_id]) || !wp_get_attachment_image_url($image_id, 'rnstyle-work')) {
        continue;
    }
    $seen_images[$image_id] = true;
    $gallery_images[] = $image_id;
}

$image_count = count($gallery_images);
$use_gallery = $image_count > 0;
$fallback = $use_gallery ? [] : json_decode(file_get_contents(get_template_directory() . '/inc/hero-media.json'), true);

// 隣のレーンが同じ並びにならないよう、画像数に互いに素な間隔で開始位置をずらす。
$phase_step = max(1, (int) floor($image_count / 2));
while ($image_count > 1) {
    $a = $phase_step;
    $b = $image_count;
    while ($b) { [$a, $b] = [$b, $a % $b]; }
    if ($a === 1) { break; }
    --$phase_step;
}
$lane_images = static function ($lane_index) use ($gallery_images, $image_count, $phase_step) {
    $cycle = [];
    for ($i = 0; $i < $image_count; ++$i) {
        $cycle[] = $gallery_images[($i + $lane_index * $phase_step) % $image_count];
    }
    // 同じ一周分を繰り返し、CSSアニメーションの折り返しを継ぎ目なくする。
    return array_merge($cycle, $cycle, $cycle);
};
?>
<div class="motion-pc<?php echo $use_gallery ? ' motion-pc--gallery' : ''; ?>">
    <img class="ambient" src="<?php echo esc_url(rn_asset('ec40a.svg')); ?>" alt="">
    <div class="motion-plane">
        <?php for ($i = 0; $i < 6; ++$i) :
            $items = $use_gallery ? $lane_images($i) : $fallback['pc'][$i];
            $distance = $image_count * 368;
            $original_left = [-2484, -2208, -2392, -2300, -2484, -2208][$i];
            $start_left = $i % 2 === 0 ? $original_left - ($distance - 1472) : $original_left;
            $duration = max(32, $image_count * 8);
            $style = $use_gallery ? sprintf('left:%dpx;--rn-lane-distance:%dpx;animation-duration:%ds;animation-delay:-%ds',
                $start_left, $distance, $duration, ($i * 7) % $duration) : '';
        ?>
        <div class="lane-carrier lane-carrier--<?php echo (int) $i; ?>">
            <div class="motion-lane"<?php if ($style) : ?> style="<?php echo esc_attr($style); ?>"<?php endif; ?>>
                <?php foreach ($items as $item) :
                    if ($use_gallery) {
                        echo wp_get_attachment_image($item, 'rnstyle-work', false, [
                            'alt' => '', 'sizes' => '340px', 'loading' => 'lazy', 'decoding' => 'async',
                        ]);
                    } else { ?>
                        <img src="<?php echo esc_url(rn_hero_src($item, 'pc')); ?>" alt="" width="340" height="228" decoding="async">
                    <?php }
                endforeach; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="hero-copy-protection"></div>
</div>
<div class="motion-sp<?php echo $use_gallery ? ' motion-sp--gallery' : ''; ?>">
    <div class="portrait-plane">
        <?php for ($i = 0; $i < 3; ++$i) :
            $items = $use_gallery ? $lane_images($i + 6) : $fallback['sp'][$i];
            $distance = $image_count * 272;
            $duration = max(32, $image_count * 8);
            $style = $use_gallery ? sprintf('top:%dpx;--rn-portrait-distance:%dpx;animation-duration:%ds;animation-delay:-%ds',
                $i === 1 ? -$distance : -1088, $distance, $duration, ($i * 13) % $duration) : '';
        ?>
        <div class="portrait-carrier">
            <div class="portrait-lane"<?php if ($style) : ?> style="<?php echo esc_attr($style); ?>"<?php endif; ?>>
                <?php foreach ($items as $item) :
                    if ($use_gallery) {
                        echo wp_get_attachment_image($item, 'rnstyle-work', false, [
                            'alt' => '', 'sizes' => '144px', 'loading' => 'lazy', 'decoding' => 'async',
                        ]);
                    } else { ?>
                        <img src="<?php echo esc_url(rn_hero_src($item, 'sp')); ?>" alt="" width="144" height="256" decoding="async">
                    <?php }
                endforeach; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="hero-copy-protection"></div>
</div>
