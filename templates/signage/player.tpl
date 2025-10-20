<?php
/** @var array $state */
/** @var string $token */
/** @var string $title */
$locale = current_locale();
$appName = $appName ?? 'Turniermanagement V2';
$stateJson = json_encode($state, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$tokenJson = json_encode($token, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/assets/css/signage-player.css">
</head>
<body class="signage-player-body">
<?php if (($state['status'] ?? '') !== 'ok'): ?>
    <div class="signage-player-error">
        <h1><?= htmlspecialchars(t('signage.player.error_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(t('signage.player.error_message'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
<?php else: ?>
    <div id="signage-player-root" class="signage-player-root" data-player-ready></div>
<?php endif; ?>
<script>
    window.SIGNAGE_PLAYER_STATE = <?= $stateJson ?>;
    window.SIGNAGE_PLAYER_TOKEN = <?= $tokenJson ?>;
</script>
<script src="public/assets/js/signage-player.js"></script>
</body>
</html>
