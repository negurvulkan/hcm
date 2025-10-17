<?php
require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/start_numbers.php';

use App\Core\App;
use App\Core\Auth as AuthCore;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Core\SmartyView;
use App\I18n\LocaleManager;
use App\I18n\Translator;
use App\Services\InstanceConfiguration;

if (!class_exists('Csrf', false)) {
    class_alias(Csrf::class, 'Csrf');
}

if (!is_file(__DIR__ . '/config/app.php')) {
    header('Location: setup.php');
    exit;
}

if (!function_exists('app_pdo')) {
    function app_pdo(): \PDO
    {
        $pdo = App::get('pdo');
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('Keine aktive Datenbankverbindung.');
        }
        return $pdo;
    }
}

function app_view(): SmartyView
{
    $view = App::get('view');
    if (!$view instanceof SmartyView) {
        throw new \RuntimeException('View-Layer nicht initialisiert.');
    }
    return $view;
}

function locale_manager(): ?LocaleManager
{
    $manager = App::get('locale_manager');
    return $manager instanceof LocaleManager ? $manager : null;
}

function available_locales(): array
{
    $manager = locale_manager();
    return $manager ? $manager->supported() : ['de', 'en'];
}

function translator(): ?Translator
{
    $translator = App::get('translator');
    return $translator instanceof Translator ? $translator : null;
}

function locale_switch_url(string $locale): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ($uri === '') {
        $uri = '/';
    }

    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['lang'] = $locale;
    $queryString = http_build_query($query);

    return $path . ($queryString ? '?' . $queryString : '');
}

function instance_config(): InstanceConfiguration
{
    $instance = App::get('instance');
    if (!$instance instanceof InstanceConfiguration) {
        $instance = new InstanceConfiguration(app_pdo());
        App::set('instance', $instance);
    }
    return $instance;
}

function instance_view_context(): array
{
    return instance_config()->viewContext();
}

function instance_refresh_view(): void
{
    if (App::has('view')) {
        app_view()->share('instance', instance_view_context());
    }
}

function instance_is_read_only(): bool
{
    return !instance_config()->canWrite();
}

function require_write_access(string $context = 'default', array $options = []): void
{
    if (!instance_is_read_only()) {
        return;
    }

    $message = instance_config()->readOnlyMessage($context);

    if (!empty($options['json'])) {
        http_response_code(423);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message], JSON_THROW_ON_ERROR);
        exit;
    }

    http_response_code(403);
    render_page('errors/read-only.tpl', [
        'title' => 'Schreibschutz aktiv',
        'page' => 'read-only',
        'message' => $message,
    ]);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type][] = $message;
}

function flash_pull(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function auth_instance(): AuthCore
{
    static $auth;
    if (!$auth instanceof AuthCore) {
        $auth = new AuthCore(app_pdo());
    }
    return $auth;
}

function auth_user(): ?array
{
    return auth_instance()->user();
}

function auth_is_admin(array $user): bool
{
    return ($user['role'] ?? '') === 'admin';
}

function auth_check(): bool
{
    return auth_instance()->check();
}

function event_active(bool $refresh = false): ?array
{
    static $active;
    if ($refresh) {
        $active = null;
    }
    if ($active === null) {
        $active = db_first('SELECT * FROM events WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }
    return $active ?: null;
}

function event_active_id(bool $refresh = false): ?int
{
    $event = event_active($refresh);
    return $event ? (int) $event['id'] : null;
}

function event_accessible(array $user, int $eventId): bool
{
    if (auth_is_admin($user)) {
        return true;
    }
    $activeId = event_active_id();
    return $activeId !== null && $activeId === (int) $eventId;
}

function auth_require(string $permission = 'dashboard'): array
{
    if (!auth_check()) {
        header('Location: auth.php');
        exit;
    }

    $user = auth_user();
    if (!$user) {
        header('Location: auth.php');
        exit;
    }

    if ($permission && !Rbac::allowed($user['role'], $permission)) {
        http_response_code(403);
        render_page('errors/403.tpl', [
            'titleKey' => 'auth.errors.forbidden_title',
            'page' => $permission,
        ]);
        exit;
    }

    return $user;
}

function auth_logout(): void
{
    auth_instance()->logout();
    flash('success', t('auth.flash.logged_out'));
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function render_page(string $template, array $data = []): void
{
    $view = app_view();
    $user = auth_user();
    $menu = $user ? Rbac::menuFor($user['role']) : [];
    $flashes = flash_pull();
    $instance = instance_view_context();
    $translations = translator()?->all() ?? [];
    $navQuickActions = $user ? \App\Core\Rbac::quickActionsFor($user['role']) : [];
    $content = $view->render($template, array_merge($data, [
        'user' => $user,
        'menu' => $menu,
        'instance' => $instance,
        'translations' => $translations,
        'navQuickActions' => $navQuickActions,
    ]));

    echo $view->render('layout.tpl', array_merge($data, [
        'user' => $user,
        'menu' => $menu,
        'content' => $content,
        'flashes' => $flashes,
        'instance' => $instance,
        'availableLocales' => available_locales(),
        'currentLocale' => current_locale(),
        'translations' => $translations,
        'navQuickActions' => $navQuickActions,
    ]));
}

function render_auth(string $template, array $data = []): void
{
    $view = app_view();
    $translations = translator()?->all() ?? [];
    echo $view->render('auth/' . $template, array_merge([
        'currentLocale' => current_locale(),
        'availableLocales' => available_locales(),
        'translations' => $translations,
    ], $data));
}

if (!function_exists('db_all')) {
    function db_all(string $sql, array $params = []): array
    {
        $stmt = app_pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('db_first')) {
    function db_first(string $sql, array $params = []): ?array
    {
        $stmt = app_pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('db_execute')) {
    function db_execute(string $sql, array $params = []): bool
    {
        $stmt = app_pdo()->prepare($sql);
        return $stmt->execute($params);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $action = $_GET['action'] ?? 'login';

    if ($action === 'logout') {
        auth_logout();
        header('Location: auth.php');
        exit;
    }

    if ($action === 'change') {
        $user = auth_require('dashboard');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) {
                $errors[] = t('auth.validation.csrf_invalid');
            }
            if (!$errors) {
                require_write_access('auth');
            }
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            if (!$errors) {
                if ($new === '' || $new !== $confirm) {
                    $errors[] = t('auth.validation.password_mismatch');
                } else {
                    $row = db_first('SELECT password FROM users WHERE id = :id', ['id' => $user['id']]);
                    if (!$row || !password_verify($current, $row['password'])) {
                        $errors[] = t('auth.validation.password_incorrect');
                    }
                }
            }

            if (!$errors) {
                auth_instance()->updatePassword($user['id'], $new);
                flash('success', t('auth.flash.password_updated'));
                header('Location: dashboard.php');
                exit;
            }
        }

        render_auth('change-password.tpl', [
            'errors' => $errors ?? [],
            'token' => csrf_token(),
            'user' => $user,
        ]);
        exit;
    }

    if (auth_check()) {
        header('Location: dashboard.php');
        exit;
    }

    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            $error = t('auth.validation.csrf_invalid');
        } else {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (!auth_instance()->attempt($email, $password)) {
                $error = t('auth.errors.login_failed');
            } else {
                header('Location: dashboard.php');
                exit;
            }
        }
    }

    render_auth('login.tpl', [
        'token' => csrf_token(),
        'error' => $error,
    ]);
    exit;
}
