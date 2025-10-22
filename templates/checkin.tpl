
<?php
/** @var array|null $station */
/** @var array $shifts */
/** @var array $recentCheckins */
/** @var string $token */
/** @var string $nonce */
/** @var bool $tokenValid */
/** @var bool $tokenExpired */
?>
<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <?php if (!$tokenValid): ?>
            <div class="alert alert-danger text-center">
                <?= htmlspecialchars(t('stations.checkin.invalid_token'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <?php if ($tokenExpired): ?>
                <div class="alert alert-warning text-center">
                    <?= htmlspecialchars(t('stations.checkin.expired_hint'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h1 class="h3 mb-3 text-center"><?= htmlspecialchars($station['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if (!empty($station['location']['label'])): ?>
                        <p class="text-center text-muted mb-1"><?= htmlspecialchars($station['location']['label'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($station['location']['details'])): ?>
                        <p class="text-center mb-3"><?= nl2br(htmlspecialchars($station['location']['details'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($station['equipment'])): ?>
                        <div class="border-top pt-3">
                            <h2 class="h6 text-uppercase text-muted mb-2"><?= htmlspecialchars(t('stations.table.equipment'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <ul class="mb-0">
                                <?php foreach ($station['equipment'] as $item): ?>
                                    <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($station['description'])): ?>
                        <div class="border-top pt-3 mt-3">
                            <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($station['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3"><?= htmlspecialchars(t('stations.checkin.status_heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if (!$shifts): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars(t('stations.checkin.no_shifts'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($shifts as $shift): ?>
                                <?php $status = $shift['status'] ?? 'open'; ?>
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($shift['role_name'] ?? $shift['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($shift['person_name'] ?? t('stations.table.responsible_none'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if (!empty($shift['starts_at']) || !empty($shift['ends_at'])): ?>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($shift['starts_at'] ? date('H:i', strtotime($shift['starts_at'])) : '–', ENT_QUOTES, 'UTF-8') ?> –
                                                <?= htmlspecialchars($shift['ends_at'] ? date('H:i', strtotime($shift['ends_at'])) : '–', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch">
                                        <span class="badge bg-secondary align-self-start">
                                            <?= htmlspecialchars(t('stations.checkin.status_labels.' . $status), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if (!$tokenExpired && in_array($status, ['open', 'assigned'], true)): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="nonce" value="<?= htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="action" value="in">
                                                <button type="submit" class="btn btn-success">
                                                    <?= htmlspecialchars(t('stations.checkin.action_in'), ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            </form>
                                        <?php elseif (!$tokenExpired && $status === 'active'): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="nonce" value="<?= htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>">
                                                <input type="hidden" name="action" value="out">
                                                <button type="submit" class="btn btn-outline-primary">
                                                    <?= htmlspecialchars(t('stations.checkin.action_out'), ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3"><?= htmlspecialchars(t('stations.checkin.recent_heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if (!$recentCheckins): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars(t('stations.checkin.recent_empty'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th><?= htmlspecialchars(t('stations.table.columns.name'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('stations.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('stations.table.columns.event'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentCheckins as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['person_name'] ?? t('stations.table.responsible_none'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($entry['type'] === 'IN' ? t('stations.checkin.action_in') : t('stations.checkin.action_out'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($entry['ts'] ?? $entry['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
