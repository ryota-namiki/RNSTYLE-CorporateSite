<?php
/** Fixed eyebrow artwork outlined from Futura PT Medium Oblique. No font files are served. */
function rn_eyebrow_art($text) {
    static $art = null;
    $text = (string) $text;
    if ($art === null) { $art = json_decode(file_get_contents(__DIR__ . '/eyebrow-art.json'), true) ?: []; }
    // New, user-authored labels remain readable until their artwork is exported.
    if (!isset($art[$text])) { return esc_html($text); }
    $html = '<span class="screen-reader-text">' . esc_html($text) . '</span><span class="eyebrow-art" aria-hidden="true">';
    foreach ($art[$text] as $glyph) {
        $width = (float) $glyph['width'];
        $html .= '<svg class="eyebrow-glyph" xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($width . 'em') . '" height="1em" viewBox="0 -800 ' . esc_attr((string) ($width * 1000)) . ' 1000" focusable="false" aria-hidden="true">';
        if ($glyph['path'] !== '') { $html .= '<path fill="currentColor" d="' . esc_attr($glyph['path']) . '"/>'; }
        $html .= '</svg>';
    }
    return $html . '</span>';
}
