<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Setup\Installer;

$configFile = __DIR__ . '/config/app.php';

$alreadyInstalled = is_file($configFile) && App::has('pdo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = $_POST['driver'] ?? 'sqlite';
    $errors = [];

    try {
        if ($driver === 'sqlite') {
            $dbPath = __DIR__ . '/storage/database.sqlite';
            $dbConfig = [
                'driver' => 'sqlite',
                'database' => $dbPath,
            ];
        } else {
            $dbConfig = [
                'driver' => 'mysql',
                'host' => trim($_POST['host'] ?? '127.0.0.1'),
                'port' => trim($_POST['port'] ?? '3306'),
                'database' => trim($_POST['database'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'charset' => 'utf8mb4',
            ];
        }

        Installer::run($dbConfig);

        $config = [
            'app' => [
                'name' => 'Turnier-App',
            ],
            'db' => $dbConfig,
        ];
        if (!is_dir(__DIR__ . '/config')) {
            mkdir(__DIR__ . '/config', 0777, true);
        }
        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");

        header('Location: index.php?installed=1');
        exit;
    } catch (\Throwable $throwable) {
        $errors[] = $throwable->getMessage();
    }
}

$driver = $_POST['driver'] ?? 'sqlite';
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Installer – Turnier-App</title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Turnier-App Installation</h1>
                </div>
                <div class="card-body">
                    <?php if ($alreadyInstalled): ?>
                        <div class="alert alert-info">Das System wurde bereits installiert. <a href="index.php">Zum Login</a></div>
                    <?php endif; ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <strong>Installation fehlgeschlagen:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Datenbank-Treiber</label>
                            <select name="driver" class="form-select" onchange="this.form.submit()">
                                <option value="sqlite" <?= $driver === 'sqlite' ? 'selected' : '' ?>>SQLite (empfohlen)</option>
                                <option value="mysql" <?= $driver === 'mysql' ? 'selected' : '' ?>>MySQL/MariaDB</option>
                            </select>
                        </div>
                        <?php if ($driver === 'mysql'): ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Host</label>
                                <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($_POST['host'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Port</label>
                                <input type="text" name="port" class="form-control" value="<?= htmlspecialchars($_POST['port'] ?? '3306', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Datenbank</label>
                                <input type="text" name="database" class="form-control" value="<?= htmlspecialchars($_POST['database'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Benutzer</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Passwort</label>
                                <input type="password" name="password" class="form-control" value="<?= htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">SQLite wird automatisch unter <code>storage/database.sqlite</code> angelegt.</p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">Demo-Daten werden automatisch angelegt.</div>
                            <button type="submit" class="btn btn-primary">Installation starten</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted small">
                    Nach Abschluss öffnet sich der Login automatisch.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
