<?php
require __DIR__ . '/auth.php';

use DateTimeImmutable;

$user = auth_require('clubs');

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editClub = $editId ? db_first('SELECT * FROM clubs WHERE id = :id', ['id' => $editId]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check($_POST['_token'] ?? null)) {
        flash('error', t('clubs.validation.csrf_invalid'));
        header('Location: clubs.php');
        exit;
    }

    require_write_access('clubs');

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $clubId = (int) ($_POST['club_id'] ?? 0);
        if ($clubId) {
            $club = db_first('SELECT party_id FROM clubs WHERE id = :id', ['id' => $clubId]);
            if ($club) {
                $partyId = (int) ($club['party_id'] ?? 0);
                db_execute('DELETE FROM clubs WHERE id = :id', ['id' => $clubId]);
                if ($partyId) {
                    db_execute('DELETE FROM organization_profiles WHERE party_id = :party', ['party' => $partyId]);
                    db_execute('DELETE FROM parties WHERE id = :id AND party_type = :type', ['id' => $partyId, 'type' => 'organization']);
                }
            }
            flash('success', t('clubs.flash.deleted'));
        }
        header('Location: clubs.php');
        exit;
    }

    $clubId = (int) ($_POST['club_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $short = strtoupper(trim((string) ($_POST['short_name'] ?? '')));

    if ($name === '' || $short === '') {
        flash('error', t('clubs.validation.name_short_required'));
    } else {
        $now = (new DateTimeImmutable())->format('c');
        if ($action === 'update' && $clubId > 0) {
            $club = db_first('SELECT party_id FROM clubs WHERE id = :id', ['id' => $clubId]);
            $partyId = $club ? (int) ($club['party_id'] ?? 0) : 0;
            if ($partyId) {
                db_execute('UPDATE parties SET display_name = :name, sort_name = :sort, updated_at = :updated WHERE id = :id AND party_type = :type', [
                    'name' => $name,
                    'sort' => mb_strtolower($name),
                    'updated' => $now,
                    'id' => $partyId,
                    'type' => 'organization',
                ]);
                db_execute('UPDATE organization_profiles SET short_name = :short, updated_at = :updated WHERE party_id = :party', [
                    'short' => $short,
                    'updated' => $now,
                    'party' => $partyId,
                ]);
            }
            db_execute('UPDATE clubs SET name = :name, short_name = :short, updated_at = :updated WHERE id = :id', [
                'name' => $name,
                'short' => $short,
                'updated' => $now,
                'id' => $clubId,
            ]);
            flash('success', t('clubs.flash.updated'));
        } else {
            db_execute('INSERT INTO parties (party_type, display_name, sort_name, created_at, updated_at) VALUES (:type, :name, :sort, :created, :updated)', [
                'type' => 'organization',
                'name' => $name,
                'sort' => mb_strtolower($name),
                'created' => $now,
                'updated' => $now,
            ]);
            $partyId = (int) app_pdo()->lastInsertId();
            db_execute('INSERT INTO organization_profiles (party_id, category, short_name, updated_at) VALUES (:party, :category, :short, :updated)', [
                'party' => $partyId,
                'category' => 'club',
                'short' => $short,
                'updated' => $now,
            ]);
            db_execute('INSERT INTO clubs (party_id, name, short_name, updated_at) VALUES (:party, :name, :short, :updated)', [
                'party' => $partyId,
                'name' => $name,
                'short' => $short,
                'updated' => $now,
            ]);
            flash('success', t('clubs.flash.created'));
        }
    }

    header('Location: clubs.php');
    exit;
}

$filter = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT * FROM clubs WHERE 1=1';
$params = [];
if ($filter !== '') {
    $sql .= ' AND (name LIKE :filter OR short_name LIKE :filter)';
    $params['filter'] = '%' . $filter . '%';
}
$sql .= ' ORDER BY name';
$clubs = db_all($sql, $params);

render_page('clubs.tpl', [
    'title' => t('pages.clubs.title'),
    'titleKey' => 'pages.clubs.title',
    'page' => 'clubs',
    'clubs' => $clubs,
    'filter' => $filter,
    'editClub' => $editClub,
]);
