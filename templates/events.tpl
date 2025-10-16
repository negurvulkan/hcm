<?php
/** @var array $events */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Turnier anlegen / bearbeiten</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Titel</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Start</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Ende</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Orte/Plätze (durch Komma getrennt)</label>
                        <input type="text" name="venues" class="form-control" placeholder="Hauptplatz, Abreitehalle">
                    </div>
                    <button class="btn btn-accent w-100" type="submit">Speichern</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Turniere</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Titel</th>
                            <th>Zeitraum</th>
                            <th>Orte</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($event['start_date'] ?? '–', ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($event['end_date'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php foreach ($event['venues_list'] as $venue): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
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
