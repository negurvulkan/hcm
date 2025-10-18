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
    $groupPriority = $item['group_priority'] ?? null;
    if (!isset($groupedMenu[$group])) {
        $groupedMenu[$group] = [
            'priority' => $groupPriority ?? 999,
            'items' => [],
        ];
    }
    if ($groupPriority !== null) {
        $groupedMenu[$group]['priority'] = min($groupedMenu[$group]['priority'], $groupPriority);
    }
    $groupedMenu[$group]['items'][$path] = $item;
}
uasort($groupedMenu, static function (array $left, array $right): int {
    return ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);
});
$navReadOnlyKeys = $readOnly ? ['entries', 'schedule', 'startlist', 'helpers', 'results'] : [];
$getInitial = static function (string $value): string {
    $slice = function_exists('mb_substr') ? mb_substr($value, 0, 2) : substr($value, 0, 2);
    return function_exists('mb_strtoupper') ? mb_strtoupper($slice) : strtoupper($slice);
};
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
<div class="app-shell">
    <header class="app-topbar navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid align-items-center">
            <div class="d-flex align-items-center gap-2 flex-grow-1 flex-lg-grow-0">
                <button class="btn btn-outline-light btn-sm d-md-none app-sidebar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#primarySidebar" aria-controls="primarySidebar" aria-label="<?= htmlspecialchars(t('layout.nav.toggle'), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="dashboard.php"><?= htmlspecialchars($appName ?? 'Turniermanagement V2', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <?php if ($navQuickActions): ?>
                <div class="app-topbar__quick-actions ms-3">
                    <div class="small text-uppercase text-muted fw-semibold mb-1 d-none d-lg-flex align-items-center gap-2">
                        <?= htmlspecialchars(t('layout.nav.quick_access'), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (($user['role'] ?? null) === 'admin'): ?>
                            <a class="link-light small" href="instance.php#nav-config"><?= htmlspecialchars(t('layout.nav.quick_edit'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="app-topbar__quick-list d-flex flex-lg-wrap gap-2 overflow-auto">
                        <?php foreach ($navQuickActions as $action): ?>
                            <a class="btn btn-sm btn-outline-light flex-shrink-0" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(t($action['label_key']), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (($user['role'] ?? null) === 'admin'): ?>
                <div class="app-topbar__quick-actions ms-3 d-none d-lg-block">
                    <div class="small text-uppercase text-muted fw-semibold mb-1 d-flex align-items-center gap-2">
                        <?= htmlspecialchars(t('layout.nav.quick_access'), ENT_QUOTES, 'UTF-8') ?>
                        <a class="link-light small" href="instance.php#nav-config"><?= htmlspecialchars(t('layout.nav.quick_edit'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                    <div class="text-muted small"><?= htmlspecialchars(t('layout.nav.quick_empty_admin'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="dropdown">
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
                    <div class="dropdown">
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
    </header>
    <div class="app-shell__body">
        <aside class="offcanvas-md offcanvas-start text-bg-dark app-sidebar" tabindex="-1" id="primarySidebar" aria-label="<?= htmlspecialchars(t('layout.nav.menu_label'), ENT_QUOTES, 'UTF-8') ?>" data-bs-scroll="true">
            <div class="offcanvas-header border-bottom border-light-subtle">
                <h2 class="offcanvas-title fs-6 mb-0 text-uppercase small"><?= htmlspecialchars($appName ?? 'Turniermanagement V2', ENT_QUOTES, 'UTF-8') ?></h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="<?= htmlspecialchars(t('layout.nav.toggle'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="app-sidebar__inner">
                    <div class="app-sidebar__brand d-none d-md-flex justify-content-center">
                        <a class="app-sidebar__brand-icon" href="dashboard.php" title="<?= htmlspecialchars($appName ?? 'Turniermanagement V2', ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip">
                            <?= htmlspecialchars($getInitial($appName ?? 'TM'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <nav class="app-sidebar__nav" aria-label="<?= htmlspecialchars(t('layout.nav.menu_label'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($groupedMenu as $group => $groupData): ?>
                        <?php
                        $groupItems = $groupData['items'];
                        $groupId = 'navGroup' . preg_replace('/[^a-z0-9]+/i', '', $group);
                        $groupLabelId = $groupId . 'Label';
                        $groupCollapseId = $groupId . 'Collapse';
                        $moreId = $groupId . 'More';
                        $primaryItems = array_filter($groupItems, static fn ($item) => ($item['variant'] ?? 'primary') === 'primary');
                        $secondaryItems = array_filter($groupItems, static fn ($item) => ($item['variant'] ?? 'primary') === 'secondary');
                        ?>
                        <div class="app-sidebar__group">
                            <button class="app-sidebar__group-toggle text-uppercase small text-muted" id="<?= htmlspecialchars($groupLabelId, ENT_QUOTES, 'UTF-8') ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($groupCollapseId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="true" aria-controls="<?= htmlspecialchars($groupCollapseId, ENT_QUOTES, 'UTF-8') ?>">
                                <span><?= htmlspecialchars(t('nav.groups.' . $group), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="app-sidebar__group-caret" aria-hidden="true"></span>
                            </button>
                            <div class="collapse app-sidebar__collapse-group show" id="<?= htmlspecialchars($groupCollapseId, ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="<?= htmlspecialchars($groupLabelId, ENT_QUOTES, 'UTF-8') ?>">
                                <ul class="app-sidebar__list" aria-labelledby="<?= htmlspecialchars($groupLabelId, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($primaryItems as $path => $item): ?>
                                        <?php
                                        $isActive = $pageKey === ($item['key'] ?? null);
                                        $shouldHighlight = ($item['priority'] ?? 50) <= 12;
                                        $tooltipKey = $item['tooltip_key'] ?? null;
                                        $subtitleKey = $item['subtitle_key'] ?? null;
                                        $label = t($item['label_key'] ?? $item['key']);
                                        $tooltip = $tooltipKey ? t($tooltipKey) : $label;
                                        $initial = $getInitial($label);
                                        ?>
                                        <li class="app-sidebar__item">
                                            <a class="app-sidebar__link <?= $isActive ? 'is-active' : '' ?> <?= $shouldHighlight ? 'is-highlighted' : '' ?>" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip">
                                                <span class="app-sidebar__icon" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="app-sidebar__text">
                                                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($subtitleKey): ?>
                                                        <small><?= htmlspecialchars(t($subtitleKey), ENT_QUOTES, 'UTF-8') ?></small>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if (in_array($item['key'] ?? '', $navReadOnlyKeys, true)): ?>
                                                    <span class="app-sidebar__status" data-bs-toggle="tooltip" title="<?= htmlspecialchars(t('nav.hints.read_only'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">&#9888;</span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ($secondaryItems): ?>
                                    <div class="app-sidebar__more">
                                        <button class="app-sidebar__more-toggle btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(t('nav.more'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                        <div class="collapse app-sidebar__collapse" id="<?= htmlspecialchars($moreId, ENT_QUOTES, 'UTF-8') ?>">
                                            <ul class="app-sidebar__list app-sidebar__list--secondary">
                                                <?php foreach ($secondaryItems as $path => $item): ?>
                                                    <?php
                                                    $label = t($item['label_key'] ?? $item['key']);
                                                    $tooltipKey = $item['tooltip_key'] ?? null;
                                                    $tooltip = $tooltipKey ? t($tooltipKey) : $label;
                                                    $initial = $getInitial($label);
                                                    ?>
                                                    <li class="app-sidebar__item">
                                                        <a class="app-sidebar__link app-sidebar__link--secondary" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip">
                                                            <span class="app-sidebar__icon" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <span class="app-sidebar__text">
                                                                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                                            </span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </nav>
                </div>
            </div>
        </aside>
        <div class="app-main flex-grow-1">

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

<main class="app-content container py-4">
    <?= $content ?>
</main>

<footer class="app-footer container pb-4 text-muted small">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(t('app.footer_notice'), ENT_QUOTES, 'UTF-8') ?>
</footer>
        </div>
    </div>
</div>

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

        if (window.bootstrap) {
            const tooltipElements = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipElements.forEach((element) => {
                window.bootstrap.Tooltip.getInstance(element) ?? new window.bootstrap.Tooltip(element);
            });

            const sidebarElement = document.getElementById('primarySidebar');
            if (sidebarElement && window.bootstrap.Offcanvas) {
                const navLinks = Array.from(sidebarElement.querySelectorAll('.app-sidebar__link'));
                const mobileQuery = window.matchMedia('(max-width: 767.98px)');

                navLinks.forEach((link) => {
                    link.addEventListener('click', () => {
                        if (mobileQuery.matches) {
                            window.bootstrap.Offcanvas.getOrCreateInstance(sidebarElement).hide();
                        }
                    });
                });
            }
        }
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
