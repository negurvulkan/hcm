<?php
require __DIR__ . '/auth.php';

$defaultFonts = [
    'Inter, "Segoe UI", sans-serif',
    'Roboto, "Helvetica Neue", Arial, sans-serif',
    'Montserrat, "Segoe UI", sans-serif',
    'Open Sans, "Helvetica Neue", sans-serif',
];

$fontConfigFile = __DIR__ . '/config/layout-editor.php';
$configuredFonts = [];
if (is_file($fontConfigFile)) {
    $config = require $fontConfigFile;
    if (is_array($config)) {
        if (isset($config['fonts']) && is_array($config['fonts'])) {
            $configuredFonts = $config['fonts'];
        } elseif (function_exists('array_is_list') && array_is_list($config)) {
            $configuredFonts = $config;
        } elseif (array_key_exists(0, $config)) {
            $configuredFonts = $config;
        }
    }
}

$fonts = array_values(array_filter(array_map('strval', $configuredFonts))); // remove invalid
if (!$fonts) {
    $fonts = $defaultFonts;
}

$editorConfig = [
    'pages' => [
        ['id' => 'page-1', 'title' => t('layout_editor.pages.default', ['index' => 1])],
    ],
    'activePageId' => 'page-1',
    'zoom' => 1,
    'canvas' => [
        'width' => 1024,
        'height' => 768,
        'gridSize' => 40,
    ],
    'labels' => [
        'page' => t('layout_editor.pages.label_pattern'),
        'status' => t('layout_editor.pages.status_template'),
        'cursor' => t('layout_editor.canvas.cursor'),
    ],
    'messages' => [
        'previewLoading' => t('layout_editor.properties.placeholder_preview_loading'),
        'previewError' => t('layout_editor.properties.placeholder_preview_error'),
        'placeholderHint' => t('layout_editor.properties.placeholder_expression_hint'),
    ],
    'export' => [
        'endpoint' => 'layout_export.php',
        'strings' => [
            'queued' => t('layout_editor.export.queued'),
            'processing' => t('layout_editor.export.processing'),
            'completed' => t('layout_editor.export.completed'),
            'error' => t('layout_editor.export.error'),
            'progress' => t('layout_editor.export.progress'),
            'none' => t('layout_editor.export.none_available'),
        ],
    ],
    'api' => [
        'base' => 'layout_editor_api.php',
    ],
    'fonts' => $fonts,
];

$user = auth_require('layout_editor');

render_page('layout-editor.tpl', [
    'titleKey' => 'layout_editor.title',
    'page' => 'layout_editor',
    'editorConfig' => $editorConfig,
    'fontOptions' => $fonts,
    'extraStyles' => ['public/assets/css/layout-editor.css'],
    'extraScripts' => [
        'public/assets/js/vendor/qrcode.min.js',
        'public/assets/js/layout-editor.js',
    ],
]);
