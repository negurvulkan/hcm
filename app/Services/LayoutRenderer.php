<?php
declare(strict_types=1);

namespace App\Services;

use App\LayoutEditor\TemplateEngine;
use DateTimeImmutable;

class LayoutRenderer
{
    private const PX_PER_MM = 3.7795275591; // 96 DPI reference

    private TemplateEngine $engine;

    public function __construct(?TemplateEngine $engine = null)
    {
        $this->engine = $engine ?? new TemplateEngine();
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<int, array<string, mixed>> $datasets
     * @param array<string, mixed> $options
     */
    public function renderDocument(array $layout, array $datasets, array $options = []): string
    {
        $pagesHtml = '';
        $total = max(count($datasets), 1);

        foreach ($datasets as $index => $dataset) {
            $pagesHtml .= $this->renderDataset($layout, $dataset, $options, $index, $total);
        }

        if ($pagesHtml === '') {
            // Render at least one empty page so Dompdf keeps the layout intact
            $pagesHtml .= $this->renderDataset($layout, [], $options, 0, $total);
        }

        return $this->renderDocumentFromHtml($layout, $pagesHtml, $options);
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $dataset
     * @param array<string, mixed> $options
     */
    public function renderDataset(array $layout, array $dataset, array $options, int $index = 0, int $total = 1): string
    {
        $canvas = $this->normalizeCanvas($layout['canvas'] ?? []);
        $paper = $this->normalizePaper($options['paper'] ?? []);
        $bleed = $this->normalizeBleed($options['bleed_mm'] ?? 0.0);

        $scale = $this->calculateScale($canvas, $paper, $bleed);
        $renderWidth = $canvas['width'] * $scale;
        $renderHeight = $canvas['height'] * $scale;

        $pages = $layout['pages'] ?? [];
        if (!is_array($pages) || !$pages) {
            $pages = [['id' => 'page-1', 'elements' => $layout['elements'] ?? []]];
        }

        $html = '';
        foreach ($pages as $pageIndex => $page) {
            if (!is_array($page)) {
                continue;
            }
            $html .= '<section class="layout-export__page" style="' . $this->pageStyle($paper, $bleed) . '">';
            $html .= '<div class="layout-export__page-inner" style="width:' . $this->formatPx($renderWidth) . ';height:'
                . $this->formatPx($renderHeight) . ';">';
            $html .= '<div class="layout-export__canvas" style="width:' . $this->formatPx($canvas['width']) . ';height:'
                . $this->formatPx($canvas['height']) . ';transform:scale(' . $this->formatNumber($scale) . ');">';
            $html .= $this->renderPageElements($page['elements'] ?? [], $dataset, $pageIndex, $index, $total);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</section>';
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $options
     */
    public function renderDocumentFromHtml(array $layout, string $pagesHtml, array $options): string
    {
        $paper = $this->normalizePaper($options['paper'] ?? []);
        $bleed = $this->normalizeBleed($options['bleed_mm'] ?? 0.0);
        $background = $layout['background'] ?? ($layout['canvas']['background'] ?? '#ffffff');
        $orientation = $paper['orientation'];
        $pageWidth = $this->formatNumber($paper['width_mm']);
        $pageHeight = $this->formatNumber($paper['height_mm']);
        $bleedCss = $this->formatNumber($bleed['mm']);
        $now = (new DateTimeImmutable('now'))->format('c');

        $styles = <<<CSS
@page {
    size: {$pageWidth}mm {$pageHeight}mm {$orientation};
    margin: 0;
}
body.layout-export {
    margin: 0;
    padding: 0;
    background: {$this->sanitizeColor($background)};
    font-family: "Inter", "Segoe UI", sans-serif;
}
.layout-export__page {
    display: block;
    position: relative;
    width: {$pageWidth}mm;
    height: {$pageHeight}mm;
    box-sizing: border-box;
    padding: {$bleedCss}mm;
    page-break-after: always;
}
.layout-export__page:last-of-type {
    page-break-after: auto;
}
.layout-export__page-inner {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.layout-export__canvas {
    position: relative;
    transform-origin: top left;
}
.layout-export__element {
    position: absolute;
    box-sizing: border-box;
    overflow: hidden;
}
.layout-export__element--text .layout-export__text-primary {
    font-weight: 600;
    margin: 0;
}
.layout-export__element--text .layout-export__text-secondary {
    margin: 0;
}
.layout-export__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.layout-export__table {
    width: 100%;
    border-collapse: collapse;
}
.layout-export__table th,
.layout-export__table td {
    border: 1px solid rgba(0,0,0,0.12);
    padding: 4px 6px;
    font-size: 11px;
}
.layout-export__badge {
    position: absolute;
    top: {$bleedCss}mm;
    right: {$bleedCss}mm;
    font-size: 8px;
    color: rgba(0,0,0,0.35);
}
CSS;

        $documentId = $layout['id'] ?? 'layout';

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>'
            . htmlspecialchars((string) ($layout['name'] ?? 'Layout Export'), ENT_QUOTES, 'UTF-8')
            . '</title><style>' . $styles . '</style></head><body class="layout-export"><div class="layout-export__badge">'
            . htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') . ' · '
            . htmlspecialchars($now, ENT_QUOTES, 'UTF-8') . '</div>' . $pagesHtml . '</body></html>';
    }

    /**
     * @param array<int, mixed> $elements
     * @param array<string, mixed> $dataset
     */
    private function renderPageElements(array $elements, array $dataset, int $pageIndex, int $datasetIndex, int $total): string
    {
        $html = '';
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            if (array_key_exists('visible', $element) && !$element['visible']) {
                continue;
            }
            $html .= $this->renderElement($element, $dataset, $pageIndex, $datasetIndex, $total);
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $dataset
     */
    private function renderElement(array $element, array $dataset, int $pageIndex, int $datasetIndex, int $total): string
    {
        $style = $this->elementStyle($element);
        $classes = 'layout-export__element layout-export__element--' . $this->sanitizeClass($element['type'] ?? 'shape');

        return match ($element['type'] ?? 'shape') {
            'text' => $this->renderTextElement($element, $dataset, $style, $classes),
            'image' => $this->renderImageElement($element, $style, $classes),
            'table' => $this->renderTableElement($element, $dataset, $style, $classes),
            'placeholder' => $this->renderPlaceholderElement($element, $dataset, $style, $classes),
            default => $this->renderShapeElement($element, $style, $classes, $pageIndex, $datasetIndex, $total),
        };
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $dataset
     */
    private function renderTextElement(array $element, array $dataset, string $style, string $classes): string
    {
        $data = $element['data'] ?? [];
        $primary = (string) ($data['text'] ?? '');
        $secondary = (string) ($data['subline'] ?? '');
        $fontFamily = $data['fontFamily'] ?? null;
        $fontSize = isset($data['fontSize']) ? (float) $data['fontSize'] : null;
        $color = $data['color'] ?? '#111111';
        $textAlign = $data['textAlign'] ?? 'left';
        $lineHeight = isset($data['lineHeight']) ? (float) $data['lineHeight'] : 1.2;

        $primaryRendered = $this->renderTemplate($primary, $dataset);
        $secondaryRendered = $secondary !== '' ? $this->renderTemplate($secondary, $dataset) : '';

        $styleInline = 'color:' . $this->sanitizeColor($color) . ';text-align:' . $this->sanitizeTextAlign($textAlign)
            . ';line-height:' . $this->formatNumber($lineHeight) . ';';
        if ($fontFamily) {
            $styleInline .= 'font-family:' . $this->sanitizeFont($fontFamily) . ';';
        }
        if ($fontSize) {
            $styleInline .= 'font-size:' . $this->formatPx($fontSize) . ';';
        }

        $html = '<div class="' . $classes . '" style="' . $style . '">';
        $html .= '<p class="layout-export__text-primary" style="' . $styleInline . '">' . $primaryRendered . '</p>';
        if ($secondaryRendered !== '') {
            $html .= '<p class="layout-export__text-secondary" style="' . $styleInline . '">' . $secondaryRendered . '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    private function renderImageElement(array $element, string $style, string $classes): string
    {
        $data = $element['data'] ?? [];
        $src = (string) ($data['src'] ?? '');
        $alt = (string) ($data['alt'] ?? '');
        $objectFit = $data['fit'] ?? 'cover';
        $background = $data['background'] ?? '#ffffff';

        return '<div class="' . $classes . '" style="' . $style . 'background:' . $this->sanitizeColor($background)
            . ';"><img class="layout-export__image" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" style="object-fit:'
            . $this->sanitizeObjectFit($objectFit) . ';" /></div>';
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $dataset
     */
    private function renderTableElement(array $element, array $dataset, string $style, string $classes): string
    {
        $data = $element['data'] ?? [];
        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];

        if (is_string($rows)) {
            $rows = $this->renderTemplate($rows, $dataset);
            $rows = $this->decodeTableRows($rows);
        }

        $html = '<div class="' . $classes . '" style="' . $style . '">';
        $html .= '<table class="layout-export__table">';
        if (is_array($headers) && $headers) {
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                if (is_array($header)) {
                    $html .= '<th>' . htmlspecialchars((string) ($header['label'] ?? $header['title'] ?? ''), ENT_QUOTES, 'UTF-8')
                        . '</th>';
                } else {
                    $html .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
                }
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . $this->renderTemplate((string) $cell, $dataset) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $dataset
     */
    private function renderPlaceholderElement(array $element, array $dataset, string $style, string $classes): string
    {
        $data = $element['data'] ?? [];
        $expression = (string) ($data['expression'] ?? '');
        $fallback = $data['sample'] ?? '';
        $content = $expression !== '' ? $this->renderTemplate($expression, $dataset) : htmlspecialchars((string) $fallback, ENT_QUOTES, 'UTF-8');

        return '<div class="' . $classes . '" style="' . $style . '">' . $content . '</div>';
    }

    private function renderShapeElement(array $element, string $style, string $classes, int $pageIndex, int $datasetIndex, int $total): string
    {
        $data = $element['data'] ?? [];
        $variant = $data['variant'] ?? 'rectangle';
        $background = $data['background'] ?? ($element['background'] ?? '#e5e7eb');
        $borderColor = $data['borderColor'] ?? '#d1d5db';
        $borderWidth = isset($data['borderWidth']) ? (float) $data['borderWidth'] : 1.0;
        $borderRadius = isset($data['borderRadius']) ? (float) $data['borderRadius'] : ($variant === 'circle' ? 9999.0 : 4.0);
        $opacity = isset($data['fillOpacity']) ? (float) $data['fillOpacity'] : 1.0;

        $content = '';
        if (!empty($data['debug'])) {
            $content = '<span style="position:absolute;bottom:4px;right:4px;font-size:9px;color:rgba(0,0,0,0.4);">'
                . ($pageIndex + 1) . '/' . max($total, 1) . '</span>';
        }

        return '<div class="' . $classes . '" style="' . $style
            . 'background:' . $this->sanitizeColor($background) . ';border:' . $this->formatPx($borderWidth)
            . ' solid ' . $this->sanitizeColor($borderColor) . ';border-radius:' . $this->formatPx($borderRadius)
            . ';opacity:' . $this->formatNumber($opacity) . ';">' . $content . '</div>';
    }

    /**
     * @return array{width:int,height:int}
     */
    private function normalizeCanvas(array $canvas): array
    {
        $width = isset($canvas['width']) ? (int) $canvas['width'] : 1024;
        $height = isset($canvas['height']) ? (int) $canvas['height'] : 768;

        return [
            'width' => max($width, 1),
            'height' => max($height, 1),
        ];
    }

    /**
     * @return array{width_mm:float,height_mm:float,orientation:string}
     */
    private function normalizePaper(array $paper): array
    {
        $width = isset($paper['width_mm']) ? (float) $paper['width_mm'] : 210.0;
        $height = isset($paper['height_mm']) ? (float) $paper['height_mm'] : 297.0;
        $orientation = strtolower((string) ($paper['orientation'] ?? 'portrait')) === 'landscape' ? 'landscape' : 'portrait';

        return [
            'width_mm' => $width,
            'height_mm' => $height,
            'orientation' => $orientation,
        ];
    }

    /**
     * @return array{mm:float,px:float}
     */
    private function normalizeBleed(float|int $bleed): array
    {
        $mm = max((float) $bleed, 0.0);
        return [
            'mm' => $mm,
            'px' => $mm * self::PX_PER_MM,
        ];
    }

    /**
     * @param array{width:int,height:int} $canvas
     * @param array{width_mm:float,height_mm:float,orientation:string} $paper
     * @param array{mm:float,px:float} $bleed
     */
    private function calculateScale(array $canvas, array $paper, array $bleed): float
    {
        $paperWidthPx = $this->mmToPx($paper['width_mm']);
        $paperHeightPx = $this->mmToPx($paper['height_mm']);

        $availableWidth = $paperWidthPx - 2 * $bleed['px'];
        $availableHeight = $paperHeightPx - 2 * $bleed['px'];
        $scale = min($availableWidth / $canvas['width'], $availableHeight / $canvas['height']);

        return max($scale, 0.1);
    }

    private function mmToPx(float $mm): float
    {
        return $mm * self::PX_PER_MM;
    }

    private function formatPx(float $value): string
    {
        return $this->formatNumber($value) . 'px';
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function elementStyle(array $element): string
    {
        $x = isset($element['x']) ? (float) $element['x'] : 0.0;
        $y = isset($element['y']) ? (float) $element['y'] : 0.0;
        $width = isset($element['width']) ? (float) $element['width'] : 100.0;
        $height = isset($element['height']) ? (float) $element['height'] : 100.0;
        $rotation = isset($element['rotation']) ? (float) $element['rotation'] : 0.0;
        $opacity = array_key_exists('opacity', $element) ? (float) $element['opacity'] : 1.0;

        $style = 'left:' . $this->formatPx($x) . ';top:' . $this->formatPx($y) . ';width:' . $this->formatPx($width)
            . ';height:' . $this->formatPx($height) . ';';
        if ($rotation !== 0.0) {
            $style .= 'transform:rotate(' . $this->formatNumber($rotation) . 'deg);transform-origin:center center;';
        }
        if ($opacity >= 0.0 && $opacity < 1.0) {
            $style .= 'opacity:' . $this->formatNumber($opacity) . ';';
        }

        if (!empty($element['background'])) {
            $style .= 'background:' . $this->sanitizeColor((string) $element['background']) . ';';
        }

        return $style;
    }

    private function pageStyle(array $paper, array $bleed): string
    {
        return 'width:' . $this->formatNumber($paper['width_mm']) . 'mm;height:'
            . $this->formatNumber($paper['height_mm']) . 'mm;padding:' . $this->formatNumber($bleed['mm']) . 'mm;';
    }

    private function renderTemplate(string $template, array $dataset): string
    {
        if ($template === '') {
            return '';
        }

        try {
            return $this->engine->render($template, $dataset);
        } catch (\Throwable) {
            return htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function decodeTableRows(string $payload): array
    {
        $lines = preg_split('/\r?\n/', $payload) ?: [];
        $rows = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            $rows[] = array_map('trim', explode('|', $trimmed));
        }
        return $rows;
    }

    private function sanitizeColor(string $color): string
    {
        $color = trim($color);
        if ($color === '') {
            return '#ffffff';
        }
        if (str_starts_with($color, 'var(')) {
            return '#ffffff';
        }
        return htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeTextAlign(string $align): string
    {
        return match (strtolower($align)) {
            'center', 'right', 'justify' => strtolower($align),
            default => 'left',
        };
    }

    private function sanitizeFont(string $font): string
    {
        $font = trim($font);
        if ($font === '') {
            return 'inherit';
        }
        return htmlspecialchars($font, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeObjectFit(string $fit): string
    {
        return match (strtolower($fit)) {
            'contain', 'fill', 'none', 'scale-down' => strtolower($fit),
            default => 'cover',
        };
    }

    private function sanitizeClass(string $value): string
    {
        return preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($value)) ?? 'element';
    }
}
