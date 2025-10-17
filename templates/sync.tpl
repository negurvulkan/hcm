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
                <h2 class="h6 text-uppercase mb-3"><?= htmlspecialchars(t('sync.ui.peer.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted"><?= htmlspecialchars(t('sync.ui.peer.base_url'), ENT_QUOTES, 'UTF-8') ?></dt>
                    <dd class="col-6 text-end"><?= $peer['base_url'] ? htmlspecialchars($peer['base_url'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">' . htmlspecialchars(t('sync.ui.peer.not_set'), ENT_QUOTES, 'UTF-8') . '</span>' ?></dd>
                    <dt class="col-6 text-muted"><?= htmlspecialchars(t('sync.ui.peer.peer_event_id'), ENT_QUOTES, 'UTF-8') ?></dt>
                    <dd class="col-6 text-end"><?= $peer['turnier_id'] ? htmlspecialchars($peer['turnier_id'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">' . htmlspecialchars(t('sync.ui.peer.not_set'), ENT_QUOTES, 'UTF-8') . '</span>' ?></dd>
                    <dt class="col-6 text-muted"><?= htmlspecialchars(t('sync.ui.peer.last_sync'), ENT_QUOTES, 'UTF-8') ?></dt>
                    <dd class="col-6 text-end">
                        <?php if ($lastSyncAt): ?>
                            <?php
                            try {
                                $formatted = format_datetime(new DateTimeImmutable($lastSyncAt));
                            } catch (Throwable) {
                                $formatted = $lastSyncAt;
                            }
                            echo htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8');
                            ?>
                        <?php else: ?>
                            <span class="text-muted"><?= htmlspecialchars(t('sync.ui.peer.no_data'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-6 text-muted"><?= htmlspecialchars(t('sync.ui.peer.cursor'), ENT_QUOTES, 'UTF-8') ?></dt>
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
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('sync.ui.actions.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" class="me-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="diff">
                        <button type="submit" class="btn btn-outline-primary"><?= htmlspecialchars(t('sync.ui.actions.diff'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                    <form method="post" class="me-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="pull">
                        <button type="submit" class="btn btn-outline-secondary" <?= $canPull ? '' : 'disabled' ?>><?= htmlspecialchars(t('sync.ui.actions.pull_now'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="push">
                        <button type="submit" class="btn btn-outline-secondary" <?= $canPush ? '' : 'disabled' ?>><?= htmlspecialchars(t('sync.ui.actions.push_now'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($diffSummary): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase mb-3"><?= htmlspecialchars(t('sync.ui.diff.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="text-muted small mb-3"><?= t('sync.ui.diff.since', ['cursor' => '<code>' . htmlspecialchars($diffSummary['since'], ENT_QUOTES, 'UTF-8') . '</code>']) ?></p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars(t('sync.ui.diff.scope'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(t('sync.ui.diff.changes'), ENT_QUOTES, 'UTF-8') ?></th>
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
                <h2 class="h6 text-uppercase mb-3"><?= htmlspecialchars(t('sync.ui.logs.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!$logs): ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('sync.ui.logs.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars(t('sync.ui.logs.time'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('sync.ui.logs.direction'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('sync.ui.logs.operation'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('sync.ui.logs.scopes'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('sync.ui.logs.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-nowrap small">
                                            <?php if (!empty($log['created_at'])): ?>
                                                <?php
                                                try {
                                                    $formatted = format_datetime(new DateTimeImmutable($log['created_at']), 'short', 'short');
                                                } catch (Throwable) {
                                                    $formatted = $log['created_at'];
                                                }
                                                echo htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8');
                                                ?>
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
