<?php

declare(strict_types=1);

use App\Modules\LayoutEditor\Controller\LayoutEditorController;

return [
    [
        'method' => 'GET',
        'path' => '/layout-editor/layouts',
        'handler' => [LayoutEditorController::class, 'index'],
        'permission' => 'layout_editor',
        'csrf' => false,
    ],
    [
        'method' => 'POST',
        'path' => '/layout-editor/layouts',
        'handler' => [LayoutEditorController::class, 'store'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'GET',
        'path' => '/layout-editor/layouts/{id}',
        'handler' => [LayoutEditorController::class, 'show'],
        'permission' => 'layout_editor',
        'csrf' => false,
    ],
    [
        'method' => 'PUT',
        'path' => '/layout-editor/layouts/{id}',
        'handler' => [LayoutEditorController::class, 'update'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'DELETE',
        'path' => '/layout-editor/layouts/{id}',
        'handler' => [LayoutEditorController::class, 'destroy'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'GET',
        'path' => '/layout-editor/layouts/{id}/versions',
        'handler' => [LayoutEditorController::class, 'versions'],
        'permission' => 'layout_editor',
        'csrf' => false,
    ],
    [
        'method' => 'POST',
        'path' => '/layout-editor/layouts/{id}/restore',
        'handler' => [LayoutEditorController::class, 'restore'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'GET',
        'path' => '/layout-editor/snippets',
        'handler' => [LayoutEditorController::class, 'listSnippets'],
        'permission' => 'layout_editor',
        'csrf' => false,
    ],
    [
        'method' => 'POST',
        'path' => '/layout-editor/snippets',
        'handler' => [LayoutEditorController::class, 'createSnippet'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'PUT',
        'path' => '/layout-editor/snippets/{snippet}',
        'handler' => [LayoutEditorController::class, 'updateSnippet'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
    [
        'method' => 'DELETE',
        'path' => '/layout-editor/snippets/{snippet}',
        'handler' => [LayoutEditorController::class, 'deleteSnippet'],
        'permission' => 'layout_editor',
        'csrf' => true,
        'write' => true,
    ],
];
