<?php /** @var array $user */ ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Turnier-App', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?= htmlspecialchars($appName ?? 'Turnier-App', ENT_QUOTES, 'UTF-8') ?></a>
        <div class="navbar-text text-white small">
            <?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
        </div>
        <a href="index.php?page=logout" class="btn btn-sm btn-outline-light ms-3">Logout</a>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 bg-light border-end min-vh-100">
            <div class="list-group list-group-flush">
                <?php foreach ($menu as $route => $item): ?>
                    <a href="index.php?page=<?= urlencode($route) ?>" class="list-group-item list-group-item-action <?= ($route === ($_GET['page'] ?? 'dashboard')) ? 'active' : '' ?>">
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="public/assets/vendor/jquery.min.js"></script>
<script src="public/assets/vendor/bootstrap.bundle.min.js"></script>
</body>
</html>
