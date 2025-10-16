<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Setup\Installer;
use RuntimeException;

$configFile = __DIR__ . '/config/app.php';
$installed = is_file($configFile) && App::has('pdo');
$errors = [];
$success = false;

$defaults = [
    'app_name' => $_ENV['APP_NAME'] ?? 'Turniermanagement V2',
    'db_driver' => $_ENV['DB_DRIVER'] ?? 'sqlite',
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_port' => $_ENV['DB_PORT'] ?? '3306',
    'db_database' => $_ENV['DB_DATABASE'] ?? '',
    'db_username' => $_ENV['DB_USERNAME'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = $_POST['db_driver'] ?? 'sqlite';
    $appName = trim($_POST['app_name'] ?? 'Turniermanagement V2');
    $admin = [
        'name' => trim($_POST['admin_name'] ?? 'Administrator'),
        'email' => trim($_POST['admin_email'] ?? ''),
        'password' => (string) ($_POST['admin_password'] ?? ''),
    ];

    try {
        if ($admin['email'] === '' || $admin['password'] === '') {
            throw new RuntimeException('Admin-E-Mail und Passwort sind Pflichtfelder.');
        }

        if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Bitte eine gültige Admin-E-Mail angeben.');
        }

        if ($driver === 'sqlite') {
            $dbPath = __DIR__ . '/storage/database.sqlite';
            $dbConfig = [
                'driver' => 'sqlite',
                'database' => $dbPath,
            ];
        } else {
            $dbConfig = [
                'driver' => 'mysql',
                'host' => trim($_POST['db_host'] ?? '127.0.0.1'),
                'port' => trim($_POST['db_port'] ?? '3306'),
                'database' => trim($_POST['db_database'] ?? ''),
                'username' => trim($_POST['db_username'] ?? ''),
                'password' => (string) ($_POST['db_password'] ?? ''),
                'charset' => 'utf8mb4',
            ];
        }

        Installer::run($dbConfig, [
            'admin' => $admin,
            'seed_demo' => isset($_POST['seed_demo']),
        ]);

        Installer::writeConfig($dbConfig, ['name' => $appName]);
        $success = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Setup · Turniermanagement V2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-0">Setup-Assistent</h1>
                        <p class="text-muted small mb-0">Offline nutzbar · SQLite oder MySQL/MariaDB</p>
                    </div>
                    <?php if ($installed): ?>
                        <span class="badge bg-success">Konfiguration gefunden</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>Fertig!</strong> Installation abgeschlossen. <a href="auth.php" class="alert-link">Zum Login</a>
                        </div>
                    <?php else: ?>
                        <?php if ($errors): ?>
                            <div class="alert alert-danger">
                                <strong>Installation fehlgeschlagen</strong>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Projektname</label>
                                <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($_POST['app_name'] ?? $defaults['app_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Datenbank</label>
                                <select name="db_driver" class="form-select" onchange="this.form.submit()">
                                    <option value="sqlite" <?= ($_POST['db_driver'] ?? $defaults['db_driver']) === 'sqlite' ? 'selected' : '' ?>>SQLite (keine Konfiguration)</option>
                                    <option value="mysql" <?= ($_POST['db_driver'] ?? $defaults['db_driver']) === 'mysql' ? 'selected' : '' ?>>MySQL/MariaDB</option>
                                </select>
                                <small class="text-muted">SQLite legt eine Datei unter <code>storage/database.sqlite</code> an.</small>
                            </div>
                            <?php if (($_POST['db_driver'] ?? $defaults['db_driver']) === 'mysql'): ?>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Host</label>
                                        <input type="text" class="form-control" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $defaults['db_host'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Port</label>
                                        <input type="text" class="form-control" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? $defaults['db_port'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Datenbank</label>
                                        <input type="text" class="form-control" name="db_database" value="<?= htmlspecialchars($_POST['db_database'] ?? $defaults['db_database'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Benutzer</label>
                                        <input type="text" class="form-control" name="db_username" value="<?= htmlspecialchars($_POST['db_username'] ?? $defaults['db_username'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Passwort</label>
                                        <input type="password" class="form-control" name="db_password" value="<?= htmlspecialchars($_POST['db_password'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Admin-Name</label>
                                    <input type="text" class="form-control" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin-E-Mail</label>
                                    <input type="email" class="form-control" name="admin_email" required value="<?= htmlspecialchars($_POST['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin-Passwort</label>
                                    <input type="password" class="form-control" name="admin_password" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="seed_demo" name="seed_demo" <?= isset($_POST['seed_demo']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="seed_demo">Demo-Daten anlegen</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted small">Setup speichert Konfiguration & legt Tabellen automatisch an.</div>
                                <button type="submit" class="btn btn-accent">Installation starten</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Kein Internet erforderlich. Vendor-Dateien lokal unter <code>public/assets/vendor/</code> ablegen.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
