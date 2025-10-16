<?php
/** @var array $errors */
/** @var string $token */
/** @var array $user */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passwort ändern · Turniermanagement V2</title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h5 mb-3">Passwort ändern</h1>
                    <p class="text-muted small">Angemeldet als <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label">Aktuelles Passwort</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Neues Passwort</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bestätigung</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dashboard.php" class="btn btn-link">Zurück</a>
                            <button type="submit" class="btn btn-accent">Speichern</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted small">
                    Änderungen werden sofort aktiv.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
