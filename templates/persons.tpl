<?php
/** @var array $roles */
/** @var array $persons */
/** @var array $clubs */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editPerson ? 'Person bearbeiten' : 'Person anlegen' ?></h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editPerson ? 'update' : 'create' ?>">
                    <input type="hidden" name="person_id" value="<?= $editPerson ? (int) $editPerson['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editPerson['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editPerson['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editPerson['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rollen</label>
                        <select name="roles[]" class="form-select" multiple size="<?= min(6, count($roles)) ?>">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $editPerson && in_array($role, $editPerson['role_list'] ?? [], true) ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Mehrfachauswahl möglich.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Verein</label>
                        <select name="club_id" class="form-select">
                            <option value="">–</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int) $club['id'] ?>" <?= $editPerson && (int) ($editPerson['club_id'] ?? 0) === (int) $club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit">Speichern</button>
                        <?php if ($editPerson): ?>
                            <a href="persons.php" class="btn btn-outline-secondary">Abbrechen</a>
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
                        <label class="form-label">Rolle</label>
                        <select name="role" class="form-select">
                            <option value="">Alle</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterRole ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
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
                            <th>Kontakt</th>
                            <th>Rollen</th>
                            <th>Verein</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($persons as $person): ?>
                            <tr>
                                <td><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($person['email'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($person['phone'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <?php foreach ($person['role_list'] as $role): ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><?= htmlspecialchars($person['club_name'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="persons.php?edit=<?= (int) $person['id'] ?>">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Eintrag wirklich löschen?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="person_id" value="<?= (int) $person['id'] ?>">
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
