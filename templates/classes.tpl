<?php
/** @var array $events */
/** @var array $classes */
/** @var array $presets */
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Prüfung anlegen</h2>
                <form method="post" data-class-form data-presets='<?= json_encode($presets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                    <?= csrf_field() ?>
                    <input type="hidden" name="class_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Turnier</label>
                        <select name="event_id" class="form-select" required>
                            <option value="">Wählen…</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>"><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bezeichnung</label>
                        <input type="text" name="label" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Platz</label>
                        <input type="text" name="arena" class="form-control">
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Startzeit</label>
                            <input type="datetime-local" name="start_time" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Ende</label>
                            <input type="datetime-local" name="end_time" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3 mb-3">
                        <label class="form-label">Max. Starter</label>
                        <input type="number" name="max_starters" class="form-control" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Richter (Komma getrennt)</label>
                        <input type="text" name="judges" class="form-control" placeholder="Anna Richter, Max Mustermann">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Regeln (JSON)</label>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-sm btn-outline-secondary" data-preset="dressage" type="button">Dressur</button>
                            <button class="btn btn-sm btn-outline-secondary" data-preset="jumping" type="button">Springen</button>
                            <button class="btn btn-sm btn-outline-secondary" data-preset="western" type="button">Western</button>
                        </div>
                        <textarea name="rules_json" class="form-control" rows="6" spellcheck="false" placeholder='{"type":"dressage"}'></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiebreaker-Kette (Komma getrennt)</label>
                        <input type="text" name="tiebreakers" class="form-control" placeholder="beste Teilnote, Zeit, Los">
                    </div>
                    <button class="btn btn-accent w-100" type="submit">Speichern</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Prüfungen</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Turnier</th>
                            <th>Bezeichnung</th>
                            <th>Platz / Zeitraum</th>
                            <th>Richter</th>
                            <th>Tiebreaker</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?= htmlspecialchars($class['event_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($class['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($class['arena'] ?? '–', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($class['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <?php foreach ($class['judges'] as $judge): ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($judge, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($class['tiebreakers'] as $item): ?>
                                        <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></span>
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
