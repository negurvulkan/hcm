<?php
/** @var array $menu */
/** @var array $user */
/** @var string $content */
/** @var array $flashes */
$pageKey = $page ?? '';
$extraScripts = $extraScripts ?? [];
$extraStyles = $extraStyles ?? [];
$instanceMeta = $instance ?? [];
$readOnly = $instanceMeta['read_only'] ?? false;
$readOnlyMessage = $instanceMeta['read_only_message'] ?? null;
$peerInfo = $instanceMeta['peer'] ?? [];
$currentLocale = $currentLocale ?? current_locale();
$availableLocales = $availableLocales ?? available_locales();
$titleKey = $titleKey ?? null;
$title = $title ?? ($titleKey ? t($titleKey) : t('layout.default_title'));
$translations = $translations ?? (translator()?->all() ?? []);
$localeMenuId = $localeMenuId ?? 'localeMenu';
$guestLocaleMenuId = $guestLocaleMenuId ?? 'localeMenuGuest';
$translatorInstance = translator();
$navQuickActions = $navQuickActions ?? [];
$groupedMenu = [];
foreach ($menu as $path => $item) {
    $group = $item['group'] ?? 'overview';
    $groupedMenu[$group][$path] = $item;
}
if ($titleKey === null && $pageKey !== '' && $translatorInstance instanceof \App\I18n\Translator) {
    $candidateKey = 'pages.' . $pageKey . '.title';
    $candidate = $translatorInstance->translate($candidateKey);
    if ($candidate !== $translatorInstance->missingKey($candidateKey)) {
        $title = $candidate;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale, ENT_QUOTES, 'UTF-8') ?>" data-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(t('app.title_suffix', ['name' => $appName ?? 'Turniermanagement V2']), ENT_QUOTES, 'UTF-8') ?></title>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="<?= htmlspecialchars(t('layout.nav.toggle'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-mega flex-grow-1">
                <?php foreach ($groupedMenu as $group => $items): ?>
                    <div class="navbar-mega__group">
                        <div class="navbar-mega__label text-uppercase small fw-semibold text-muted">
                            <?= htmlspecialchars(t('nav.groups.' . $group), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <ul class="navbar-nav">
                            <?php foreach ($items as $path => $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $pageKey === $item['key'] ? 'active' : '' ?>" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(t($item['label_key'] ?? $item['key']), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($navQuickActions): ?>
                <div class="nav-quick-actions ms-lg-4">
                    <div class="small text-uppercase text-muted fw-semibold mb-1 d-none d-lg-block">
                        <?= htmlspecialchars(t('layout.nav.quick_access'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($navQuickActions as $action): ?>
                            <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(t($action['label_key']), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="navbar-divider d-none d-lg-block ms-4 me-4"></div>
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 ms-lg-auto mt-3 mt-lg-0">
                <div class="dropdown order-lg-2">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="<?= htmlspecialchars($user ? $localeMenuId : $guestLocaleMenuId, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars(strtoupper($currentLocale), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="<?= htmlspecialchars($user ? $localeMenuId : $guestLocaleMenuId, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($availableLocales as $localeOption): ?>
                            <li>
                                <a class="dropdown-item <?= $localeOption === $currentLocale ? 'active' : '' ?>" href="<?= htmlspecialchars(locale_switch_url($localeOption), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(t('locale.name.' . $localeOption), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($user): ?>
                    <div class="dropdown order-lg-1">
                        <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fw-semibold"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-secondary text-uppercase small"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li class="dropdown-header text-uppercase small text-muted"><?= htmlspecialchars(t('layout.role_label'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></li>
                            <li><a class="dropdown-item" href="auth.php?action=change"><?= htmlspecialchars(t('auth.change_password'), ENT_QUOTES, 'UTF-8') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="auth.php?action=logout"><?= htmlspecialchars(t('auth.logout'), ENT_QUOTES, 'UTF-8') ?></a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php if (!empty($instanceMeta)): ?>
    <div class="instance-status-bar border-bottom small py-2">
        <div class="container-fluid d-flex flex-wrap align-items-center gap-3 px-3">
            <span class="fw-semibold"><?= htmlspecialchars($instanceMeta['status_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <span class="<?= htmlspecialchars($readOnly ? 'text-warning fw-semibold' : 'text-success fw-semibold', ENT_QUOTES, 'UTF-8') ?>">
                <?= $readOnly ? htmlspecialchars(t('layout.read_only'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(t('layout.writeable'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if (!empty($peerInfo['configured'])): ?>
                <span class="<?= htmlspecialchars($peerInfo['class'] ?? 'text-muted', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($peerInfo['label'] ?? t('layout.peer.default'), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($peerInfo['formatted_checked_at'])): ?>
                        · <?= htmlspecialchars($peerInfo['formatted_checked_at'], ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($readOnly && $readOnlyMessage): ?>
    <div class="alert alert-warning text-center rounded-0 mb-0">
        <?= htmlspecialchars($readOnlyMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="flash-stack" aria-live="polite" aria-atomic="true">
    <?php foreach ($flashes as $type => $messages): ?>
        <?php foreach ($messages as $index => $message): ?>
            <div class="flash-message alert alert-<?= $type === 'error' ? 'danger' : $type ?> shadow" role="alert" data-flash-index="<?= (int) $index ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<main class="container py-4">
    <?= $content ?>
</main>

<footer class="container pb-4 text-muted small">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(t('app.footer_notice'), ENT_QUOTES, 'UTF-8') ?>
</footer>

<script src="public/assets/vendor/jquery.min.js"></script>
<script src="public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    window.APP_LOCALE = <?= json_encode($currentLocale, JSON_THROW_ON_ERROR) ?>;
    window.APP_LOCALES = <?= json_encode(array_values($availableLocales), JSON_THROW_ON_ERROR) ?>;
    window.APP_TRANSLATIONS = <?= json_encode($translations, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.addEventListener('DOMContentLoaded', () => {
        const flashes = Array.from(document.querySelectorAll('.flash-message'));
        flashes.forEach((flash, index) => {
            setTimeout(() => {
                flash.classList.add('is-dismissing');
                flash.addEventListener('transitionend', () => flash.remove(), { once: true });
            }, 4000 + index * 600);
        });
    });
</script>
<script src="public/assets/js/i18n.js"></script>
<script src="public/assets/js/helpers.js"></script>
<script src="public/assets/js/ticker.js"></script>
<?php foreach ($extraScripts as $script): ?>
    <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
