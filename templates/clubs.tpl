<?php
/** @var array $clubs */
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editClub ? 'Verein bearbeiten' : 'Verein anlegen' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editClub ? 'update' : 'create' ?>">
                    <input type="hidden" name="club_id" value="<?= $editClub ? (int) $editClub['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editClub['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kürzel</label>
                        <input type="text" name="short_name" class="form-control" maxlength="10" value="<?= htmlspecialchars($editClub['short_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editClub): ?>
                            <a class="btn btn-outline-secondary" href="clubs.php">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Übersicht</h2>
                <form class="row g-2 align-items-end mb-3">
                    <div class="col-md-10">
                        <label class="form-label">Suche</label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filter ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit">Filtern</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Kürzel</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clubs as $club): ?>
                            <tr>
                                <td><?= htmlspecialchars($club['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($club['short_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="clubs.php?edit=<?= (int) $club['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Eintrag wirklich löschen?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="club_id" value="<?= (int) $club['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
