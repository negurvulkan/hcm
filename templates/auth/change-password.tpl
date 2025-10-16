<?php
/** @var array $errors */
/** @var string $token */
/** @var array $user */
/** @var string $currentLocale */
/** @var array $availableLocales */
$currentLocale = $currentLocale ?? current_locale();
$availableLocales = $availableLocales ?? available_locales();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(t('auth.change.title'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(t('app.title_suffix', ['name' => $appName ?? 'Turniermanagement V2']), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/styles.css">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-end mb-3">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="changeLocale" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= htmlspecialchars(strtoupper($currentLocale), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="changeLocale">
                                <?php foreach ($availableLocales as $localeOption): ?>
                                    <li>
                                        <a class="dropdown-item <?= $localeOption === $currentLocale ? 'active' : '' ?>" href="<?= htmlspecialchars(locale_switch_url($localeOption), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(t('locale.name.' . $localeOption), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <h1 class="h5 mb-3"><?= htmlspecialchars(t('auth.change.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-muted small"><?= htmlspecialchars(t('auth.change.user_info', ['email' => $user['email'] ?? '']), ENT_QUOTES, 'UTF-8') ?></p>
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
                            <label class="form-label"><?= htmlspecialchars(t('auth.change.current'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('auth.change.new'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('auth.change.confirm'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dashboard.php" class="btn btn-link"><?= htmlspecialchars(t('auth.change.back'), ENT_QUOTES, 'UTF-8') ?></a>
                            <button type="submit" class="btn btn-accent"><?= htmlspecialchars(t('auth.change.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted small">
                    <?= htmlspecialchars(t('auth.change.footer'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
