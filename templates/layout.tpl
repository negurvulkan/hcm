<?php
/** @var array $menu */
/** @var array $user */
/** @var string $content */
/** @var array $flashes */
$title = $title ?? 'Turniermanagement';
$pageKey = $page ?? '';
$extraScripts = $extraScripts ?? [];
$extraStyles = $extraStyles ?? [];
?>
<!DOCTYPE html>
<html lang="de" data-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($appName ?? 'Turniermanagement V2', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
    <?php foreach ($extraStyles as $href): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><?= htmlspecialchars($appName ?? 'Turniermanagement V2', ENT_QUOTES, 'UTF-8') ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($menu as $path => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $pageKey === $item['key'] ? 'active' : '' ?>" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($user): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="fw-semibold"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small text-uppercase">Role: <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="btn-group">
                        <a href="auth.php?action=change" class="btn btn-outline-light btn-sm">Passwort ändern</a>
                        <a href="auth.php?action=logout" class="btn btn-light btn-sm">Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($flashes as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="flash alert alert-<?= $type === 'error' ? 'danger' : $type ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="container pb-4 text-muted small">
    &copy; <?= date('Y') ?> Turniermanagement V2 · Kein Internet notwendig · Vendors lokal einbinden.
</footer>

<script src="public/assets/vendor/jquery.min.js"></script>
<script src="public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/helpers.js"></script>
<script src="public/assets/js/ticker.js"></script>
<?php foreach ($extraScripts as $script): ?>
    <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
