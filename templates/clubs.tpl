<?php
/** @var array $clubs */
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Verein anlegen</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kürzel</label>
                        <input type="text" name="short_name" class="form-control" maxlength="10" required>
                    </div>
                    <button class="btn btn-accent w-100" type="submit">Speichern</button>
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
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clubs as $club): ?>
                            <tr>
                                <td><?= htmlspecialchars($club['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($club['short_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
