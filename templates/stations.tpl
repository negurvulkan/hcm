
<?php
/** @var array $stations */
/** @var array $persons */
/** @var array $events */
/** @var array $filters */
/** @var array|null $editStation */
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars($editStation ? t('stations.form.edit_title') : t('stations.form.create_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <form method="post" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editStation ? 'update' : 'create' ?>">
                    <input type="hidden" name="station_id" value="<?= $editStation ? (int) $editStation['id'] : '' ?>">
                    <div class="mb-3">
                        <label class="form-label" for="station-name"><?= htmlspecialchars(t('stations.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="station-name" name="name" required value="<?= htmlspecialchars($editStation['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="invalid-feedback"><?= htmlspecialchars(t('stations.validation.name_required'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-event"><?= htmlspecialchars(t('stations.form.event'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="station-event" name="event_id">
                            <option value=""><?= htmlspecialchars(t('stations.form.event_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>" <?= $editStation && (int) $editStation['event_id'] === (int) $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-responsible"><?= htmlspecialchars(t('stations.form.responsible'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="station-responsible" name="responsible_person_id">
                            <option value=""><?= htmlspecialchars(t('stations.form.responsible_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= $editStation && (int) ($editStation['responsible_person_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-location"><?= htmlspecialchars(t('stations.form.location_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="station-location" name="location_label" value="<?= htmlspecialchars($editStation['location']['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-location-details"><?= htmlspecialchars(t('stations.form.location_details'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" id="station-location-details" name="location_details" rows="2"><?= htmlspecialchars($editStation['location']['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-emergency-url"><?= htmlspecialchars(t('stations.form.emergency_url'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="url" class="form-control" id="station-emergency-url" name="emergency_url" value="<?= htmlspecialchars($editStation['location']['emergency_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-equipment"><?= htmlspecialchars(t('stations.form.equipment'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" id="station-equipment" name="equipment" rows="3" placeholder="<?= htmlspecialchars(t('stations.form.equipment_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?php if (!empty($editStation['equipment'])): ?><?= htmlspecialchars(implode("
", $editStation['equipment']), ENT_QUOTES, 'UTF-8') ?><?php endif; ?></textarea>
                        <div class="form-text"><?= htmlspecialchars(t('stations.form.equipment_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="station-description"><?= htmlspecialchars(t('stations.form.description'), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" id="station-description" name="description" rows="3"><?= htmlspecialchars($editStation['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="station-active" name="active" value="1" <?= $editStation ? ((int) $editStation['active'] === 1 ? 'checked' : '') : 'checked' ?>>
                        <label class="form-check-label" for="station-active"><?= htmlspecialchars(t('stations.form.active'), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <?php if ($editStation): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="station-refresh-token" name="refresh_token" value="1">
                            <label class="form-check-label" for="station-refresh-token"><?= htmlspecialchars(t('stations.form.refresh_token'), ENT_QUOTES, 'UTF-8') ?></label>
                        </div>
                    <?php endif; ?>
                    <div class="d-grid gap-2">
                        <button class="btn btn-accent" type="submit"><?= htmlspecialchars(t('stations.form.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php if ($editStation): ?>
                            <a href="stations.php" class="btn btn-outline-secondary"><?= htmlspecialchars(t('stations.form.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="get">
                    <div class="col-md-4">
                        <label class="form-label" for="filter-search"><?= htmlspecialchars(t('stations.filters.search'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="search" class="form-control" id="filter-search" name="q" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(t('stations.filters.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-event"><?= htmlspecialchars(t('stations.filters.event'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-event" name="event_id">
                            <option value="0"><?= htmlspecialchars(t('stations.filters.event_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= (int) $event['id'] ?>" <?= (int) $filters['event_id'] === (int) $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="filter-responsible"><?= htmlspecialchars(t('stations.filters.responsible'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-responsible" name="responsible_id">
                            <option value="0"><?= htmlspecialchars(t('stations.filters.responsible_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?= (int) $person['id'] ?>" <?= (int) $filters['responsible_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-active"><?= htmlspecialchars(t('stations.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="filter-active" name="active">
                            <option value="all" <?= $filters['active'] === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(t('stations.filters.status_all'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>><?= htmlspecialchars(t('stations.filters.status_active'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>><?= htmlspecialchars(t('stations.filters.status_inactive'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto ms-md-auto">
                        <button class="btn btn-outline-secondary w-100" type="submit"><?= htmlspecialchars(t('stations.filters.apply'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= htmlspecialchars(t('stations.table.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('stations.table.columns.name'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('stations.table.columns.event'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('stations.table.columns.responsible'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('stations.table.columns.status'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('stations.table.columns.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stations as $station): ?>
                            <?php $isActive = (int) $station['active'] === 1; ?>
                            <tr class="<?= $isActive ? '' : 'table-secondary' ?>">
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($station['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small text-muted">
                                        <?php if ($station['location']['label']): ?><?= htmlspecialchars($station['location']['label'], ENT_QUOTES, 'UTF-8') ?><?php else: ?><?= htmlspecialchars(t('stations.table.location_unknown'), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($station['event_title'] ?? t('stations.table.event_none'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($station['responsible_name'] ?? t('stations.table.responsible_none'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars($isActive ? t('stations.table.status_active') : t('stations.table.status_inactive'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($station['checkin_token'])): ?>
                                        <div class="small text-muted mt-1">
                                            <?= htmlspecialchars(t('stations.table.token'), ENT_QUOTES, 'UTF-8') ?>
                                            <code><?= htmlspecialchars($station['checkin_token'], ENT_QUOTES, 'UTF-8') ?></code>
                                        </div>
                                        <?php if (!empty($station['checkin_expires_at'])): ?>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars(t('stations.table.expires'), ENT_QUOTES, 'UTF-8') ?>
                                                <?= htmlspecialchars(date('Y-m-d H:i', strtotime($station['checkin_expires_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="small text-warning"><?= htmlspecialchars(t('stations.table.no_token'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
<?php $hasToken = !empty($station['checkin_token']); ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stationQrModal" data-name="<?= htmlspecialchars($station['name'], ENT_QUOTES, 'UTF-8') ?>" data-checkin="<?= $hasToken ? htmlspecialchars('checkin.php?token=' . rawurlencode((string) $station['checkin_token']), ENT_QUOTES, 'UTF-8') : '' ?>" data-emergency="<?= htmlspecialchars($station['location']['emergency_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= $hasToken ? '' : 'disabled' ?>>
                                            <?= htmlspecialchars(t('stations.table.show_qr'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                        <a href="stations.php?edit=<?= (int) $station['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(t('stations.table.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="station_id" value="<?= (int) $station['id'] ?>">
                                            <input type="hidden" name="set_active" value="<?= $isActive ? '0' : '1' ?>">
                                            <button class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>" type="submit"><?= htmlspecialchars($isActive ? t('stations.table.deactivate') : t('stations.table.activate'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="regenerate_token">
                                            <input type="hidden" name="station_id" value="<?= (int) $station['id'] ?>">
                                            <button class="btn btn-sm btn-outline-info" type="submit"><?= htmlspecialchars(t('stations.table.regenerate'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('stations.table.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="station_id" value="<?= (int) $station['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(t('stations.table.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-bottom">
                                <td colspan="5" class="py-3">
                                    <div class="row g-3 small">
                                        <div class="col-md-6">
                                            <div class="fw-semibold mb-1"><?= htmlspecialchars(t('stations.table.location'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div><?= htmlspecialchars($station['location']['details'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="fw-semibold mb-1"><?= htmlspecialchars(t('stations.table.equipment'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($station['equipment'])): ?>
                                                <ul class="mb-0">
                                                    <?php foreach ($station['equipment'] as $item): ?>
                                                        <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div><?= htmlspecialchars(t('stations.table.equipment_none'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$stations): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4"><?= htmlspecialchars(t('stations.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stationQrModal" tabindex="-1" aria-labelledby="stationQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stationQrModalLabel" data-modal-title><?= htmlspecialchars(t('stations.qr.modal_title'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('stations.qr.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div data-qr-target class="d-inline-block"></div>
                </div>
                <p class="text-center">
                    <a href="#" data-checkin-link target="_blank" rel="noopener" class="fw-semibold"></a>
                </p>
                <div class="text-center" data-emergency-wrapper>
                    <a href="#" class="btn btn-outline-danger" target="_blank" rel="noopener" data-emergency-link><?= htmlspecialchars(t('stations.qr.emergency_link'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('stations.qr.close'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-accent" data-download><?= htmlspecialchars(t('stations.qr.download'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<?php app_view()->push('scripts', function () { ?>
    <script src="public/assets/vendor/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('stationQrModal');
            if (!modal) {
                return;
            }
            var qrContainer = modal.querySelector('[data-qr-target]');
            var checkinLink = modal.querySelector('[data-checkin-link]');
            var emergencyWrapper = modal.querySelector('[data-emergency-wrapper]');
            var emergencyLink = modal.querySelector('[data-emergency-link]');
            var downloadButton = modal.querySelector('[data-download]');
            var bsModal = modal instanceof bootstrap.Modal ? modal : new bootstrap.Modal(modal);

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }
                var targetUrl = trigger.getAttribute('data-checkin');
                var emergencyUrl = trigger.getAttribute('data-emergency');
                var title = trigger.getAttribute('data-name');

                qrContainer.innerHTML = '';
                var qr = new QRCode(qrContainer, {
                    text: targetUrl,
                    width: 256,
                    height: 256,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });

                var modalTitle = modal.querySelector('[data-modal-title]');
                if (modalTitle) {
                    modalTitle.textContent = title;
                }

                if (checkinLink) {
                    checkinLink.textContent = targetUrl;
                    checkinLink.href = targetUrl;
                }

                if (emergencyUrl) {
                    emergencyWrapper.classList.remove('d-none');
                    emergencyLink.href = emergencyUrl;
                } else {
                    emergencyWrapper.classList.add('d-none');
                    emergencyLink.href = '#';
                }
            });

            downloadButton.addEventListener('click', function () {
                var img = qrContainer.querySelector('img');
                if (!img) {
                    return;
                }
                var link = document.createElement('a');
                link.href = img.src;
                link.download = 'station-qr.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
<?php }); ?>
