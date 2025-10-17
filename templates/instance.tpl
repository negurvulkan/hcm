<?php
/** @var array $form */
/** @var array $errors */
/** @var string $token */
/** @var bool $hasPeerToken */
/** @var array $lastHealth */
/** @var array $lastDryRun */
/** @var array $localSnapshot */
/** @var bool $hasPendingChanges */
/** @var string $previousMode */

use App\Services\InstanceConfiguration;

$roles = InstanceConfiguration::roles();
$modes = InstanceConfiguration::modes();
?>
<div class="row g-4">
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
        <?php if ($hasPendingChanges): ?>
            <div class="alert alert-info">Es liegen ungespeicherte Änderungen vor. Verbindungstests sind deaktiviert, bis gespeichert wurde.</div>
        <?php endif; ?>
        <form method="post" class="mb-4" id="instance-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="checklist_complete" id="checklist-complete" value="0">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Instanz-Rolle</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Rolle</label>
                            <select class="form-select" name="instance_role" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $form['instance_role'] === $role ? 'selected' : '' ?>>
                                        <?php if ($role === InstanceConfiguration::ROLE_ONLINE): ?>Online-Instanz<?php elseif ($role === InstanceConfiguration::ROLE_LOCAL): ?>Lokale Instanz<?php else: ?>Mirror/Slave<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modus / Phase</label>
                            <select class="form-select" name="operation_mode" data-operation-mode required>
                                <?php foreach ($modes as $mode): ?>
                                    <option value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" <?= $form['operation_mode'] === $mode ? 'selected' : '' ?>>
                                        <?php if ($mode === InstanceConfiguration::MODE_PRE_TOURNAMENT): ?>Prä-Turnier<?php elseif ($mode === InstanceConfiguration::MODE_TOURNAMENT): ?>Turnierbetrieb<?php else: ?>Post-Turnier<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4" data-peer-section>
                <div class="card-body">
                    <h2 class="h5 mb-3">Peer / Mirror</h2>
                    <div class="row g-3">
                        <div class="col-md-8" data-peer-base-field>
                            <label class="form-label">Peer-Basis-URL</label>
                            <input type="url" class="form-control" name="peer_base_url" placeholder="https://scores.mein-turnier.de" value="<?= htmlspecialchars($form['peer_base_url'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4" data-peer-event-field>
                            <label class="form-label">Peer Turnier-ID</label>
                            <input type="text" class="form-control" name="peer_turnier_id" value="<?= htmlspecialchars($form['peer_turnier_id'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-8" data-peer-token-field>
                            <label class="form-label">API-Token</label>
                            <input type="password" class="form-control" name="peer_api_token" placeholder="<?= $hasPeerToken ? 'Token hinterlegt – neues Token eingeben um zu ersetzen' : 'Token eingeben' ?>">
                            <?php if ($hasPeerToken): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="peer_api_token_clear" id="peer-token-clear">
                                    <label class="form-check-label" for="peer-token-clear">Gespeichertes Token löschen</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="save" class="btn btn-primary">Speichern</button>
                <button type="submit" name="action" value="test_connection" class="btn btn-outline-secondary" <?= $hasPendingChanges ? 'disabled' : '' ?>>Verbindung testen</button>
                <button type="submit" name="action" value="dry_run" class="btn btn-outline-secondary" <?= $hasPendingChanges ? 'disabled' : '' ?>>Dry-Run Sync testen</button>
                <button type="button" class="btn btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#switchModal">Umschalt-Checkliste</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 text-uppercase mb-3">Letzter Verbindungstest</h2>
                <p class="mb-1"><strong>Status:</strong> <span class="<?= htmlspecialchars($lastHealth['class'] ?? 'text-muted', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lastHealth['label'] ?? 'Nicht geprüft', ENT_QUOTES, 'UTF-8') ?></span></p>
                <p class="mb-1"><strong>Zuletzt:</strong> <?= htmlspecialchars($lastHealth['formatted_checked_at'] ?? 'Keine Daten', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="small text-muted mb-0"><?= htmlspecialchars($lastHealth['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 text-uppercase mb-3">Dry-Run Überblick</h2>
                <p class="mb-1"><strong>Letzter Lauf:</strong> <?= htmlspecialchars($lastDryRun['formatted_timestamp'] ?? 'Noch nicht durchgeführt', ENT_QUOTES, 'UTF-8') ?></p>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="text-end">Lokal</th>
                            <th class="text-end">Peer</th>
                            <th class="text-end">Δ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Entries</td>
                            <td class="text-end"><?= (int) ($lastDryRun['local']['entries'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['remote']['entries'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['differences']['entries'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Klassen</td>
                            <td class="text-end"><?= (int) ($lastDryRun['local']['classes'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['remote']['classes'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['differences']['classes'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Ergebnisse</td>
                            <td class="text-end"><?= (int) ($lastDryRun['local']['results'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['remote']['results'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($lastDryRun['differences']['results'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase mb-3">Lokaler Snapshot</h2>
                <p class="mb-1"><strong>Aktives Turnier:</strong> <?= htmlspecialchars($localSnapshot['event']['title'] ?? 'Keins aktiv', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-1">Einträge: <?= (int) ($localSnapshot['counts']['entries'] ?? 0) ?></p>
                <p class="mb-1">Prüfungen: <?= (int) ($localSnapshot['counts']['classes'] ?? 0) ?></p>
                <p class="mb-0">Ergebnisse: <?= (int) ($localSnapshot['counts']['results'] ?? 0) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="switchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">Checkliste für Moduswechsel</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Bitte bestätige die folgenden Schritte bevor der Modus gewechselt wird:</p>
                <div class="form-check">
                    <input class="form-check-input checklist-item" type="checkbox" id="checklist-readonly">
                    <label class="form-check-label" for="checklist-readonly">Online-Instanz ggf. auf Read-only schalten</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input checklist-item" type="checkbox" id="checklist-sync">
                    <label class="form-check-label" for="checklist-sync">Lokale Daten initial synchronisieren (Pull)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input checklist-item" type="checkbox" id="checklist-mirror">
                    <label class="form-check-label" for="checklist-mirror">Öffentlichen Mirror aktivieren (falls benötigt)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" data-checklist-confirm disabled>Checkliste bestätigt</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const roleSelect = document.querySelector('[name="instance_role"]');
    const modeSelect = document.querySelector('[name="operation_mode"]');
    const peerSection = document.querySelector('[data-peer-section]');
    const peerBaseField = document.querySelector('[data-peer-base-field]');
    const peerBaseInput = peerBaseField ? peerBaseField.querySelector('input') : null;
    const peerEventInput = document.querySelector('[name="peer_turnier_id"]');
    const peerTokenInput = document.querySelector('[name="peer_api_token"]');

    function togglePeer() {
        if (!peerSection || !modeSelect || !roleSelect) {
            return;
        }
        const mode = modeSelect.value;
        const role = roleSelect.value;
        const shouldShow = mode !== '<?= InstanceConfiguration::MODE_PRE_TOURNAMENT ?>' || role === '<?= InstanceConfiguration::ROLE_MIRROR ?>';
        peerSection.classList.toggle('d-none', !shouldShow);

        const allowBase = mode !== '<?= InstanceConfiguration::MODE_TOURNAMENT ?>'
            || role === '<?= InstanceConfiguration::ROLE_LOCAL ?>'
            || role === '<?= InstanceConfiguration::ROLE_MIRROR ?>';
        if (peerBaseField) {
            peerBaseField.classList.toggle('d-none', !allowBase);
        }
        if (peerBaseInput) {
            peerBaseInput.disabled = !allowBase;
            if (!allowBase) {
                peerBaseInput.value = '';
            }
        }

        const requirePeerCredentials = mode === '<?= InstanceConfiguration::MODE_TOURNAMENT ?>'
            && (role === '<?= InstanceConfiguration::ROLE_LOCAL ?>' || role === '<?= InstanceConfiguration::ROLE_ONLINE ?>');
        if (peerEventInput) {
            peerEventInput.required = requirePeerCredentials;
        }
        if (peerTokenInput) {
            peerTokenInput.required = requirePeerCredentials;
        }
    }

    roleSelect?.addEventListener('change', togglePeer);
    modeSelect?.addEventListener('change', togglePeer);
    togglePeer();

    const checklistModal = document.getElementById('switchModal');
    const checklistButton = checklistModal ? checklistModal.querySelector('[data-checklist-confirm]') : null;
    const checklistInputs = checklistModal ? checklistModal.querySelectorAll('.checklist-item') : null;
    const hiddenField = document.getElementById('checklist-complete');
    let checklistConfirmed = false;

    function updateChecklistButton() {
        if (!checklistButton || !checklistInputs) {
            return;
        }
        let allChecked = true;
        checklistInputs.forEach((input) => {
            if (!input.checked) {
                allChecked = false;
            }
        });
        checklistButton.disabled = !allChecked;
    }

    checklistInputs?.forEach((input) => input.addEventListener('change', updateChecklistButton));

    checklistButton?.addEventListener('click', () => {
        if (hiddenField) {
            hiddenField.value = '1';
        }
        checklistConfirmed = true;
        updateChecklistButton();
        if (checklistModal && window.bootstrap) {
            const modalInstance = window.bootstrap.Modal.getInstance(checklistModal);
            modalInstance?.hide();
        }
    });

    checklistModal?.addEventListener('hidden.bs.modal', () => {
        if (!checklistConfirmed && hiddenField) {
            hiddenField.value = '0';
        }
        checklistInputs?.forEach((input) => {
            input.checked = false;
        });
        checklistConfirmed = false;
        updateChecklistButton();
    });

    updateChecklistButton();
})();
</script>
