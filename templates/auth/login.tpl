<?php
/** @var string $token */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Turniermanagement V2</title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Anmeldung</h1>
                    <p class="text-muted small">Bitte lokale Zugangsdaten eingeben. Vendors liegen lokal vor.</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label">E-Mail</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Passwort</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="small" href="auth.php?action=change">Passwort ändern</a>
                            <button type="submit" class="btn btn-accent">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted small">
                    Kein Internet nötig · Bootstrap/jQuery lokal referenzieren.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
