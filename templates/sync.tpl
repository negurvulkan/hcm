<?php
/** @var array $peer */
/** @var array $logs */
/** @var array|null $diffSummary */
/** @var array $errors */
/** @var string|null $success */
/** @var array $scopes */
/** @var \App\Sync\SyncCursor $cursor */
/** @var bool $canPull */
/** @var bool $canPush */
/** @var string|null $lastSyncAt */

use DateTimeImmutable;
use Throwable;
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase mb-3">Peer-Informationen</h2>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Basis-URL</dt>
                    <dd class="col-6 text-end"><?= $peer['base_url'] ? htmlspecialchars($peer['base_url'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">nicht gesetzt</span>' ?></dd>
                    <dt class="col-6 text-muted">Peer Turnier-ID</dt>
                    <dd class="col-6 text-end"><?= $peer['turnier_id'] ? htmlspecialchars($peer['turnier_id'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">nicht gesetzt</span>' ?></dd>
                    <dt class="col-6 text-muted">Letzter Sync</dt>
                    <dd class="col-6 text-end">
                        <?php if ($lastSyncAt): ?>
                            <?php try { $dt = new DateTimeImmutable($lastSyncAt); echo htmlspecialchars($dt->format('d.m.Y H:i'), ENT_QUOTES, 'UTF-8'); } catch (Throwable) { echo htmlspecialchars($lastSyncAt, ENT_QUOTES, 'UTF-8'); } ?>
                        <?php else: ?>
                            <span class="text-muted">keine Daten</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-6 text-muted">Aktueller Cursor</dt>
                    <dd class="col-6 text-end small text-break"><?= htmlspecialchars($cursor->value(), ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Sync-Aktionen</h2>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" class="me-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="diff">
                        <button type="submit" class="btn btn-outline-primary">Dry-Run / Diff</button>
                    </form>
                    <form method="post" class="me-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="pull">
                        <button type="submit" class="btn btn-outline-secondary" <?= $canPull ? '' : 'disabled' ?>>Pull jetzt</button>
                    </form>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="push">
                        <button type="submit" class="btn btn-outline-secondary" <?= $canPush ? '' : 'disabled' ?>>Push jetzt</button>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($diffSummary): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase mb-3">Dry-Run Ergebnis</h2>
                    <p class="text-muted small mb-3">Seit Cursor <code><?= htmlspecialchars($diffSummary['since'], ENT_QUOTES, 'UTF-8') ?></code></p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Scope</th>
                                    <th class="text-end">Änderungen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diffSummary['counts'] as $scope => $count): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end fw-semibold"><?= (int) $count ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase mb-3">Letzte Sync-Operationen</h2>
                <?php if (!$logs): ?>
                    <p class="text-muted mb-0">Keine Einträge vorhanden.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Zeit</th>
                                    <th>Richtung</th>
                                    <th>Aktion</th>
                                    <th>Scopes</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-nowrap small">
                                            <?php if (!empty($log['created_at'])): ?>
                                                <?php try { $dt = new DateTimeImmutable($log['created_at']); echo htmlspecialchars($dt->format('d.m. H:i'), ENT_QUOTES, 'UTF-8'); } catch (Throwable) { echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); } ?>
                                            <?php else: ?>
                                                <span class="text-muted">–</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted text-uppercase small"><?= htmlspecialchars($log['direction'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small"><?= htmlspecialchars($log['operation'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small">
                                            <?php $scopeList = $log['scopes'] ?? []; if (!$scopeList) { echo '<span class="text-muted">–</span>'; } else { echo htmlspecialchars(implode(', ', $scopeList), ENT_QUOTES, 'UTF-8'); } ?>
                                        </td>
                                        <td>
                                            <?php $status = strtolower((string) ($log['status'] ?? '')); ?>
                                            <span class="badge <?= $status === 'completed' ? 'bg-success' : ($status === 'error' ? 'bg-danger' : 'bg-secondary') ?>"><?= htmlspecialchars($log['status'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
