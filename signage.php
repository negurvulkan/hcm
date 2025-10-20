<?php
require __DIR__ . '/auth.php';

use App\Signage\SignageRepository;

$user = auth_require('signage');
$repository = new SignageRepository();
$eventId = event_active_id();

$layouts = $repository->listLayouts($eventId);
$displays = $repository->listDisplays();
$playlists = $repository->listPlaylists();

$layoutDetails = [];
foreach ($layouts as $layoutMeta) {
    $detail = $repository->getLayout((int) $layoutMeta['id']);
    if ($detail) {
        $layoutDetails[] = $detail;
    }
}

$designerConfig = [
    'layouts' => $layoutDetails,
    'displays' => $displays,
    'playlists' => $playlists,
    'csrfToken' => csrf_token(),
    'apiEndpoint' => 'signage_api.php',
    'locale' => current_locale(),
];

render_page('signage/index.tpl', [
    'titleKey' => 'signage.title',
    'page' => 'signage',
    'layouts' => $layouts,
    'displays' => $displays,
    'playlists' => $playlists,
    'designerConfig' => $designerConfig,
    'extraStyles' => ['public/assets/css/signage.css'],
    'extraScripts' => ['public/assets/js/signage-designer.js'],
]);
