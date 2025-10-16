<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Login – Turnier-App</title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Login</h1>
                </div>
                <div class="card-body">
                    <?php if (!empty($installed)): ?>
                        <div class="alert alert-success">Installation erfolgreich – bitte einloggen.</div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <form method="post" action="index.php?page=login">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label">E-Mail</label>
                            <input type="email" name="email" class="form-control" required placeholder="rolle@demo.local">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Passwort</label>
                            <input type="password" name="password" class="form-control" required placeholder="rolle">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Anmelden</button>
                    </form>
                </div>
                <div class="card-footer text-muted small">
                    Demo-Zugänge: admin@demo.local, meldestelle@demo.local, richter@demo.local usw. Passwort jeweils = Rolle.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
