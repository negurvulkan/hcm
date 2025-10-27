<?php
require __DIR__ . '/auth.php';

use App\Core\Csrf;
use App\Services\LayoutRenderer;

if (!is_dir(__DIR__ . '/storage/layout_exports')) {
    mkdir(__DIR__ . '/storage/layout_exports', 0775, true);
}

$user = auth_require('layout_editor');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower((string) ($_GET['action'] ?? ($method === 'GET' ? 'meta' : 'create')));

header('Content-Type: application/json; charset=utf-8');

if ($action === 'meta') {
    echo json_encode(metaPayload($user), JSON_UNESCAPED_UNICODE);
    return;
}

if ($method !== 'POST' && !in_array($action, ['status', 'download'], true)) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'code' => 'METHOD_NOT_ALLOWED']);
    return;
}

if (in_array($action, ['create', 'process'], true) && !Csrf::check(requestToken())) {
    http_response_code(419);
    echo json_encode(['status' => 'error', 'code' => 'CSRF_INVALID', 'csrf' => csrf_token()], JSON_UNESCAPED_UNICODE);
    return;
}

switch ($action) {
    case 'create':
        handleCreate($user);
        break;
    case 'process':
        handleProcess($user);
        break;
    case 'status':
        handleStatus($user);
        break;
    case 'download':
        handleDownload($user);
        break;
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'ACTION_UNKNOWN']);
}

function requestJson(): array
{
    static $cache;
    if ($cache !== null) {
        return $cache;
    }
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        $cache = [];
        return $cache;
    }
    try {
        $cache = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $cache = [];
    }
    return $cache;
}

function requestToken(): ?string
{
    $payload = requestJson();
    if (isset($payload['_token']) && is_string($payload['_token'])) {
        return $payload['_token'];
    }
    $headers = getallheaders();
    $headerToken = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? null;
    return is_string($headerToken) ? $headerToken : ($_POST['_token'] ?? null);
}

function metaPayload(array $user): array
{
    $layouts = availableLayouts();
    $dataSources = dataSources();
    $papers = paperPresets();

    return [
        'status' => 'ok',
        'csrf' => csrf_token(),
        'layouts' => array_values($layouts),
        'data_sources' => array_values($dataSources),
        'paper' => array_values($papers),
        'classes' => accessibleClasses($user),
    ];
}

function handleCreate(array $user): void
{
    $payload = requestJson();
    $layoutId = trim((string) ($payload['layout'] ?? ''));
    $dataSource = trim((string) ($payload['data_source'] ?? ''));
    $paperId = trim((string) ($payload['paper'] ?? 'a4-portrait'));
    $bleed = isset($payload['bleed']) ? (float) $payload['bleed'] : 0.0;
    $options = $payload['options'] ?? [];

    $layouts = availableLayouts();
    if (!isset($layouts[$layoutId])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'LAYOUT_NOT_FOUND', 'csrf' => csrf_token()]);
        return;
    }

    $papers = paperPresets();
    if (!isset($papers[$paperId])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'code' => 'PAPER_INVALID', 'csrf' => csrf_token()]);
        return;
    }

    $dataSources = dataSources();
    if (!isset($dataSources[$dataSource])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'code' => 'DATA_SOURCE_INVALID', 'csrf' => csrf_token()]);
        return;
    }

    $jobId = uniqid('layout_export_', true);
    $jobPath = jobPath($jobId);

    $job = [
        'id' => $jobId,
        'user_id' => (int) $user['id'],
        'layout_id' => $layoutId,
        'data_source' => $dataSource,
        'options' => [
            'paper' => $papers[$paperId],
            'bleed_mm' => max($bleed, 0.0),
            'requested' => $options,
        ],
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'status' => 'queued',
        'processed' => 0,
        'total' => 0,
        'message' => null,
        'download' => null,
        'source_options' => sanitizeSourceOptions($dataSources[$dataSource], $payload),
    ];

    saveJob($jobPath, $job);

    echo json_encode([
        'status' => 'ok',
        'csrf' => csrf_token(),
        'job' => ['id' => $jobId],
    ], JSON_UNESCAPED_UNICODE);
}

function handleProcess(array $user): void
{
    $payload = requestJson();
    $jobId = trim((string) ($payload['job'] ?? ''));
    $job = loadJob($jobId, $user);
    if (!$job) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'JOB_NOT_FOUND', 'csrf' => csrf_token()]);
        return;
    }

    if ($job['status'] === 'completed') {
        echo json_encode(['status' => 'ok', 'job' => $job, 'csrf' => csrf_token()]);
        return;
    }

    $layout = availableLayouts()[$job['layout_id']] ?? null;
    if (!$layout) {
        $job['status'] = 'failed';
        $job['message'] = 'Layout konnte nicht geladen werden.';
        $job['updated_at'] = date('c');
        saveJob(jobPath($job['id']), $job);
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'LAYOUT_NOT_FOUND', 'csrf' => csrf_token()]);
        return;
    }

    $datasets = resolveDatasets($job['data_source'], $job['source_options'] ?? []);
    if (!$datasets) {
        $job['status'] = 'failed';
        $job['message'] = 'Keine Datensätze gefunden.';
        $job['updated_at'] = date('c');
        saveJob(jobPath($job['id']), $job);
        http_response_code(422);
        echo json_encode(['status' => 'error', 'code' => 'DATASET_EMPTY', 'csrf' => csrf_token()]);
        return;
    }

    $job['status'] = 'processing';
    $job['total'] = count($datasets);
    $job['processed'] = 0;
    $job['updated_at'] = date('c');
    $job['message'] = null;
    saveJob(jobPath($job['id']), $job);

    $renderer = new LayoutRenderer();
    $htmlFragments = '';
    foreach ($datasets as $index => $dataset) {
        $htmlFragments .= $renderer->renderDataset($layout, $dataset, $job['options'], $index, count($datasets));
        $job['processed'] = $index + 1;
        $job['updated_at'] = date('c');
        saveJob(jobPath($job['id']), $job);
    }
    $html = $renderer->renderDocumentFromHtml($layout, $htmlFragments, $job['options']);
    $pdfPath = jobPdfPath($job['id']);
    if (!class_exists('Dompdf\\Dompdf') && is_file(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
    }

    if (!class_exists('Dompdf\\Dompdf')) {
        $job['status'] = 'failed';
        $job['message'] = 'Dompdf nicht verfügbar.';
        $job['updated_at'] = date('c');
        saveJob(jobPath($job['id']), $job);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'code' => 'DOMPDF_MISSING', 'csrf' => csrf_token()]);
        return;
    }

    $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
    $dompdf->loadHtml($html);
    $paper = $job['options']['paper'];
    $dompdf->setPaper([$paper['width_mm'] * 72 / 25.4, $paper['height_mm'] * 72 / 25.4], $paper['orientation']);
    $dompdf->render();
    file_put_contents($pdfPath, $dompdf->output());

    $job['status'] = 'completed';
    $job['download'] = basename($pdfPath);
    $job['updated_at'] = date('c');
    saveJob(jobPath($job['id']), $job);

    echo json_encode(['status' => 'ok', 'job' => $job, 'csrf' => csrf_token()], JSON_UNESCAPED_UNICODE);
}

function handleStatus(array $user): void
{
    $jobId = trim((string) ($_GET['job'] ?? ''));
    $job = loadJob($jobId, $user);
    if (!$job) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'JOB_NOT_FOUND']);
        return;
    }

    echo json_encode(['status' => 'ok', 'job' => $job, 'csrf' => csrf_token()], JSON_UNESCAPED_UNICODE);
}

function handleDownload(array $user): void
{
    $jobId = trim((string) ($_GET['job'] ?? ''));
    $job = loadJob($jobId, $user);
    if (!$job || $job['status'] !== 'completed' || !$job['download']) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'JOB_NOT_READY']);
        return;
    }

    $pdfPath = jobPdfPath($job['id']);
    if (!is_file($pdfPath)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'FILE_MISSING']);
        return;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($pdfPath) . '"');
    header('Content-Length: ' . filesize($pdfPath));
    readfile($pdfPath);
    exit;
}

function jobPath(string $jobId): string
{
    return __DIR__ . '/storage/layout_exports/' . $jobId . '.json';
}

function jobPdfPath(string $jobId): string
{
    return __DIR__ . '/storage/layout_exports/' . $jobId . '.pdf';
}

function loadJob(string $jobId, array $user): ?array
{
    if ($jobId === '') {
        return null;
    }
    $path = jobPath($jobId);
    if (!is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || (int) ($data['user_id'] ?? 0) !== (int) $user['id']) {
        return null;
    }
    return $data;
}

function saveJob(string $path, array $job): void
{
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    rename($tmp, $path);
}

function availableLayouts(): array
{
    $dir = __DIR__ . '/storage/layouts';
    if (!is_dir($dir)) {
        return [];
    }
    $layouts = [];
    foreach (scandir($dir) ?: [] as $file) {
        if (!str_ends_with((string) $file, '.json')) {
            continue;
        }
        $path = $dir . '/' . $file;
        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            continue;
        }
        if (!is_array($data) || empty($data['id'])) {
            continue;
        }
        $data['file'] = $file;
        $layouts[$data['id']] = $data;
    }
    return $layouts;
}

function dataSources(): array
{
    return [
        'dummy' => [
            'id' => 'dummy',
            'label' => 'Demo-Datensatz',
            'description' => 'Generiert Beispielwerte basierend auf dem Layout-Dummy.',
            'options' => [],
        ],
        'startlist' => [
            'id' => 'startlist',
            'label' => 'Startliste einer Prüfung',
            'description' => 'Alle Starter einer Prüfung mit Reiter-, Pferde- und Startnummern.',
            'options' => ['class_id'],
        ],
        'results' => [
            'id' => 'results',
            'label' => 'Ergebnisliste',
            'description' => 'Finale Ergebnisse einer Prüfung inklusive Platzierung und Punktzahl.',
            'options' => ['class_id'],
        ],
    ];
}

function sanitizeSourceOptions(array $source, array $payload): array
{
    $options = [];
    foreach ($source['options'] as $option) {
        $value = $payload[$option] ?? null;
        if ($option === 'class_id') {
            $options['class_id'] = (int) $value;
        } else {
            $options[$option] = $value;
        }
    }
    return $options;
}

function paperPresets(): array
{
    return [
        'a4-portrait' => [
            'id' => 'a4-portrait',
            'label' => 'A4 Hochformat',
            'width_mm' => 210,
            'height_mm' => 297,
            'orientation' => 'portrait',
        ],
        'a4-landscape' => [
            'id' => 'a4-landscape',
            'label' => 'A4 Querformat',
            'width_mm' => 297,
            'height_mm' => 210,
            'orientation' => 'landscape',
        ],
        'a5-portrait' => [
            'id' => 'a5-portrait',
            'label' => 'A5 Hochformat',
            'width_mm' => 148,
            'height_mm' => 210,
            'orientation' => 'portrait',
        ],
        'letter-portrait' => [
            'id' => 'letter-portrait',
            'label' => 'US Letter',
            'width_mm' => 216,
            'height_mm' => 279,
            'orientation' => 'portrait',
        ],
    ];
}

function accessibleClasses(array $user): array
{
    $sql = 'SELECT c.id, c.label, e.title AS event_title FROM classes c JOIN events e ON e.id = c.event_id';
    $params = [];
    if (!auth_is_admin($user)) {
        $active = event_active();
        if (!$active) {
            return [];
        }
        $sql .= ' WHERE c.event_id = :event_id';
        $params['event_id'] = (int) $active['id'];
    }
    $sql .= ' ORDER BY e.start_date DESC, c.label';
    return db_all($sql, $params);
}

function resolveDatasets(string $source, array $options): array
{
    return match ($source) {
        'startlist' => buildStartlistDatasets((int) ($options['class_id'] ?? 0)),
        'results' => buildResultsDatasets((int) ($options['class_id'] ?? 0)),
        default => buildDummyDatasets(),
    };
}

function buildDummyDatasets(): array
{
    $provider = new App\LayoutEditor\DummyDataProvider();
    $base = $provider->example();
    $datasets = [];
    for ($i = 0; $i < 3; $i++) {
        $copy = $base;
        $copy['person']['name'] = $base['person']['name'] . ' #' . ($i + 1);
        $copy['start']['number'] = chr(65 + $i) . sprintf('%02d', $i + 1);
        $copy['start']['position'] = $i + 1;
        $datasets[] = $copy;
    }
    return $datasets;
}

function buildStartlistDatasets(int $classId): array
{
    if ($classId <= 0) {
        return [];
    }
    $class = db_first('SELECT c.*, e.title AS event_title, e.venues, e.start_date, e.end_date FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
    if (!$class) {
        return [];
    }
    $rows = db_all('SELECT si.position, si.start_number_display, si.state, pr.display_name AS rider, h.name AS horse, clubs.name AS club_name FROM startlist_items si JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id LEFT JOIN clubs ON clubs.id = profile.club_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY si.position', ['class_id' => $classId]);
    $datasets = [];
    foreach ($rows as $row) {
        $datasets[] = buildDatasetFromRow($row, $class, null);
    }
    return $datasets;
}

function buildResultsDatasets(int $classId): array
{
    if ($classId <= 0) {
        return [];
    }
    $class = db_first('SELECT c.*, e.title AS event_title, e.venues, e.start_date, e.end_date FROM classes c JOIN events e ON e.id = c.event_id WHERE c.id = :id', ['id' => $classId]);
    if (!$class) {
        return [];
    }
    $rows = db_all('SELECT r.rank, r.total, r.status, pr.display_name AS rider, h.name AS horse, clubs.name AS club_name FROM results r JOIN startlist_items si ON si.id = r.startlist_id JOIN entries e ON e.id = si.entry_id JOIN parties pr ON pr.id = e.party_id LEFT JOIN person_profiles profile ON profile.party_id = pr.id LEFT JOIN clubs ON clubs.id = profile.club_id JOIN horses h ON h.id = e.horse_id WHERE si.class_id = :class_id ORDER BY r.rank IS NULL, r.rank ASC', ['class_id' => $classId]);
    $datasets = [];
    foreach ($rows as $row) {
        $datasets[] = buildDatasetFromRow($row, $class, $row);
    }
    return $datasets;
}

function buildDatasetFromRow(array $row, array $class, ?array $result): array
{
    return [
        'event' => [
            'title' => $class['event_title'] ?? '',
            'location' => $class['venues'] ?? '',
            'start_date' => $class['start_date'] ?? '',
            'end_date' => $class['end_date'] ?? '',
            'discipline' => $class['label'] ?? '',
        ],
        'person' => [
            'name' => $row['rider'] ?? '',
            'club' => $row['club_name'] ?? '',
            'role' => 'Reiter',
        ],
        'horse' => [
            'name' => $row['horse'] ?? '',
        ],
        'start' => [
            'number' => $row['start_number_display'] ?? '',
            'position' => $row['position'] ?? ($result['rank'] ?? null),
            'status' => $row['state'] ?? ($result['status'] ?? ''),
            'score' => $result['total'] ?? null,
        ],
        'result' => $result,
    ];
}
