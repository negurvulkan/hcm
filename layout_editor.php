<?php
require __DIR__ . '/auth.php';

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
];

$user = auth_require('layout_editor');

render_page('layout-editor.tpl', [
    'titleKey' => 'layout_editor.title',
    'page' => 'layout_editor',
    'editorConfig' => $editorConfig,
    'extraStyles' => ['public/assets/css/layout-editor.css'],
    'extraScripts' => ['public/assets/js/layout-editor.js'],
]);
