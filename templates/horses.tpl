<?php
/** @var array $horses */
/** @var array $owners */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editHorse ? 'Pferd bearbeiten' : 'Pferd erfassen' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editHorse ? 'update' : 'create' ?>">
                    <input type="hidden" name="horse_id" value="<?= $editHorse ? (int) $editHorse['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editHorse['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Besitzer / Reiter</label>
                        <select name="owner_id" class="form-select">
                            <option value="">–</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= (int) $owner['id'] ?>" <?= $editHorse && (int) ($editHorse['owner_id'] ?? 0) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="documents_ok" name="documents_ok" <?= $editHorse && (int) ($editHorse['documents_ok'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="documents_ok">Dokumente geprüft</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notiz</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($editHorse['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editHorse): ?>
                            <a href="horses.php" class="btn btn-outline-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Übersicht</h2>
                <form class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filterName ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Besitzer</label>
                        <select name="owner" class="form-select">
                            <option value="">Alle</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= (int) $owner['id'] ?>" <?= ((int)($filterOwner ?? 0)) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit">Filtern</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Besitzer</th>
                            <th>Status</th>
                            <th>Notiz</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($horses as $horse): ?>
                            <tr>
                                <td><?= htmlspecialchars($horse['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($horse['owner_name'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ((int) ($horse['documents_ok'] ?? 0) === 1): ?>
                                        <span class="badge bg-success">Dokumente ok</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Prüfung offen</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($horse['notes'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="horses.php?edit=<?= (int) $horse['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Eintrag wirklich löschen?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="horse_id" value="<?= (int) $horse['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
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
