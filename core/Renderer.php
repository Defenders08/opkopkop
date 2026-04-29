<?php
/**
 * ChubbyCMS - Renderer Module
 * Transforms block data into HTML
 */

namespace Core;

class Renderer {
    public static function render($blocks) {
        if (!is_array($blocks)) return '';

        $html = '<div class="markdown">';
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        $html .= '</div>';

        return $html;
    }

    private static function renderBlock($block) {
        $type = $block['type'] ?? 'text';
        $content = $block['content'] ?? '';
        $settings = $block['settings'] ?? [];

        $alignClass = isset($settings['align']) ? ' align-' . $settings['align'] : '';

        switch ($type) {
            case 'paragraph':
            case 'text':
                return '<p class="' . $alignClass . '">' . nl2br(self::escape($content)) . '</p>';

            case 'heading':
            case 'h3':
                $level = $settings['level'] ?? 3;
                return '<h' . $level . ' class="' . $alignClass . '">' . self::escape($content) . '</h' . $level . '>';

            case 'image':
                $src = self::escape($content);
                $alt = self::escape($settings['alt'] ?? '');
                return '<img src="' . $src . '" alt="' . $alt . '" class="' . $alignClass . '">';

            case 'img_little':
                $src = self::escape($block['image'] ?? '');
                $text = $block['text'] ?? '';
                $nested = isset($block['blocks']) ? self::renderBlocks($block['blocks']) : nl2br(self::escape($text));

                return '<div class="tab"><div class="img-little-wrap">' .
                       ($src ? '<img src="' . $src . '" alt="">' : '') .
                       '<div>' . $nested . '</div></div></div>';

            case 'gallery':
                $images = $block['images'] ?? [];
                $html = '<div class="gallery ' . $alignClass . '">';
                foreach ($images as $img) {
                    $html .= '<img src="' . self::escape($img) . '" alt="">';
                }
                $html .= '</div>';
                return $html;

            case 'button':
                $url = self::escape($settings['url'] ?? '#');
                $label = self::escape($content ?: 'кнопка');
                return '<a href="' . $url . '" class="cms-btn ' . $alignClass . '">' . $label . '</a>';

            case 'quote':
            case 'tab':
                $nested = isset($block['blocks']) ? self::renderBlocks($block['blocks']) : nl2br(self::escape($content));
                return '<div class="tab ' . $alignClass . '">' . $nested . '</div>';

            case 'ul':
            case 'list':
                $items = is_array($content) ? $content : explode("\n", $content);
                $html = '<ul class="' . $alignClass . '">';
                foreach ($items as $item) {
                    if (trim($item)) {
                        $html .= '<li>' . self::escape(trim($item)) . '</li>';
                    }
                }
                $html .= '</ul>';
                return $html;

            case 'code':
                return '<pre class="' . $alignClass . '"><code>' . self::escape($content) . '</code></pre>';

            case 'callout':
                $icon = self::escape($settings['icon'] ?? 'ℹ️');
                return '<div class="tab callout ' . $alignClass . '"><span>' . $icon . '</span> ' . nl2br(self::escape($content)) . '</div>';

            case 'columns':
                $cols = $block['columns'] ?? [];
                $html = '<div class="group columns ' . $alignClass . '" style="display: flex; gap: 20px; flex-wrap: wrap;">';
                foreach ($cols as $col) {
                    $width = $col['width'] ?? '1';
                    $html .= '<div class="column" style="flex: ' . $width . '; min-width: 200px;">';
                    $html .= self::renderBlocks($col['blocks'] ?? []);
                    $html .= '</div>';
                }
                $html .= '</div>';
                return $html;

            case 'container':
            case 'group':
                $html = '<div class="group ' . $alignClass . '">';
                $html .= self::renderBlocks($block['blocks'] ?? []);
                $html .= '</div>';
                return $html;

            case 'hr':
                return '<hr>';

            case 'link':
                $url = self::escape($settings['url'] ?? '#');
                return '<a href="' . $url . '">' . self::escape($content) . '</a>';

            default:
                return '<!-- unknown block type: ' . self::escape($type) . ' -->';
        }
    }

    private static function renderBlocks($blocks) {
        $html = '';
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        return $html;
    }

    private static function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
