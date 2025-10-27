<?php
/** @var array<int, array<string, mixed>> $layouts */
/** @var array<string, mixed> $filters */
/** @var array<string> $categories */
/** @var array<string> $statuses */
/** @var array<int, array<int, array<string, mixed>>> $versions */
/** @var array<int, array<string, mixed>> $ownerOptions */
/** @var int|null $activeLayoutId */
?>
<div class="container-fluid px-0">
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars(t('layouts.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-0 small"><?= htmlspecialchars(t('layouts.subheading'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
            <form method="post" action="layouts.php" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="import">
                <label class="btn btn-outline-secondary btn-sm mb-0">
                    <?= htmlspecialchars(t('layouts.import.button'), ENT_QUOTES, 'UTF-8') ?>
                    <input type="file" name="package" class="d-none" accept="application/zip">
                </label>
                <button type="submit" class="btn btn-primary btn-sm">
                    <?= htmlspecialchars(t('layouts.import.submit'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="layouts.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?= htmlspecialchars(t('layouts.filters.search'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="search" name="search" class="form-control" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(t('layouts.filters.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= htmlspecialchars(t('layouts.filters.category'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['category'] ?? 'all') === $category ? 'selected' : '' ?>><?= htmlspecialchars(t('layouts.categories.' . $category), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= htmlspecialchars(t('layouts.filters.status'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? 'all') === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('layouts.statuses.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= htmlspecialchars(t('layouts.filters.owner'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="owner_id" class="form-select">
                        <option value=""><?= htmlspecialchars(t('layouts.filters.owner_all'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php foreach ($ownerOptions as $owner): ?>
                            <option value="<?= (int) $owner['id'] ?>" <?= ((int) ($filters['owner_id'] ?? 0)) === (int) $owner['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 text-md-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <?= htmlspecialchars(t('layouts.filters.apply'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0"><?= htmlspecialchars(t('layouts.create.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <div class="card-body">
                    <form method="post" action="layouts.php" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.description'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea name="description" class="form-control" rows="2" placeholder="<?= htmlspecialchars(t('layouts.form.description_placeholder'), ENT_QUOTES, 'UTF-8') ?>"></textarea>
                        </div>
                        <div>
                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.category'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="category" class="form-select">
                                <?php foreach (array_slice($categories, 1) as $category): ?>
                                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('layouts.categories.' . $category), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.status'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="status" class="form-select">
                                <?php foreach (array_slice($statuses, 1) as $status): ?>
                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('layouts.statuses.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.owner'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="owner_id" class="form-select">
                                <option value=""><?= htmlspecialchars(t('layouts.filters.owner_all'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($ownerOptions as $owner): ?>
                                    <option value="<?= (int) $owner['id'] ?>"><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <?= htmlspecialchars(t('layouts.create.submit'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0"><?= htmlspecialchars(t('layouts.help.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2"><?= htmlspecialchars(t('layouts.help.versioning'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-0"><?= htmlspecialchars(t('layouts.help.approval'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small text-uppercase fw-semibold"><?= htmlspecialchars(t('layouts.categories.label'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php foreach (array_slice($categories, 1) as $category): ?>
                    <?php $active = ($filters['category'] ?? 'all') === $category; ?>
                    <a class="badge rounded-pill <?= $active ? 'bg-primary' : 'bg-light text-dark' ?>" href="layouts.php?<?= http_build_query(array_merge($filters, ['category' => $category])) ?>">
                        <?= htmlspecialchars(t('layouts.categories.' . $category), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
                <a class="badge rounded-pill <?= ($filters['category'] ?? 'all') === 'all' ? 'bg-primary' : 'bg-light text-dark' ?>" href="layouts.php?<?= http_build_query(array_merge($filters, ['category' => 'all'])) ?>">
                    <?= htmlspecialchars(t('layouts.categories.all'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            <?php if (!$layouts): ?>
                <div class="alert alert-secondary">
                    <?= htmlspecialchars(t('layouts.empty'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <?php foreach ($layouts as $layout): ?>
                    <?php $isActive = $activeLayoutId !== null && (int) $layout['id'] === $activeLayoutId; ?>
                    <div class="card mb-3 <?= $isActive ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between flex-wrap gap-3">
                                <div>
                                    <h2 class="h5 mb-1">
                                        <?= htmlspecialchars($layout['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <small class="text-muted">#<?= (int) $layout['id'] ?></small>
                                    </h2>
                                    <?php if (!empty($layout['description'])): ?>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars($layout['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="badge bg-info-subtle text-info-emphasis text-uppercase"><?= htmlspecialchars(t('layouts.categories.' . ($layout['category'] ?? 'general')), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge <?= $layout['status'] === 'approved' ? 'bg-success' : ($layout['status'] === 'in_review' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars(t('layouts.statuses.' . ($layout['status'] ?? 'draft')), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="badge bg-light text-dark">v<?= (int) $layout['version'] ?></span>
                                    </div>
                                </div>
                                <div class="text-end small text-muted">
                                    <?php if (!empty($layout['updated_at'])): ?>
                                        <div><?= htmlspecialchars(t('layouts.meta.updated', ['date' => format_datetime($layout['updated_at'])]), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($layout['approved_at'])): ?>
                                        <div><?= htmlspecialchars(t('layouts.meta.approved', ['date' => format_datetime($layout['approved_at'])]), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($layout['owner_id'])): ?>
                                        <div><?= htmlspecialchars(t('layouts.meta.owner', ['id' => (int) $layout['owner_id']]), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <a href="layouts.php?action=export&amp;id=<?= (int) $layout['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <?= htmlspecialchars(t('layouts.actions.export'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <form method="post" action="layouts.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="duplicate">
                                    <input type="hidden" name="layout_id" value="<?= (int) $layout['id'] ?>">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        <?= htmlspecialchars(t('layouts.actions.duplicate'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                                <form method="post" action="layouts.php" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(t('layouts.actions.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="layout_id" value="<?= (int) $layout['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <?= htmlspecialchars(t('layouts.actions.delete'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                            </div>

                            <details class="mt-3">
                                <summary class="text-muted small text-uppercase fw-semibold mb-2"><?= htmlspecialchars(t('layouts.versions.title'), ENT_QUOTES, 'UTF-8') ?></summary>
                                <?php $layoutVersions = $versions[$layout['id']] ?? []; ?>
                                <?php if (!$layoutVersions): ?>
                                    <p class="text-muted small mb-0"><?= htmlspecialchars(t('layouts.versions.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th><?= htmlspecialchars(t('layouts.versions.version'), ENT_QUOTES, 'UTF-8') ?></th>
                                                    <th><?= htmlspecialchars(t('layouts.versions.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                                    <th><?= htmlspecialchars(t('layouts.versions.created'), ENT_QUOTES, 'UTF-8') ?></th>
                                                    <th><?= htmlspecialchars(t('layouts.versions.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($layoutVersions as $version): ?>
                                                    <tr>
                                                        <td>v<?= (int) $version['version'] ?></td>
                                                        <td><?= htmlspecialchars(t('layouts.statuses.' . ($version['status'] ?? 'draft')), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars(format_datetime($version['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td class="text-end">
                                                            <a href="layouts.php?action=export&amp;id=<?= (int) $layout['id'] ?>&amp;version=<?= (int) $version['version'] ?>" class="btn btn-outline-secondary btn-sm">
                                                                <?= htmlspecialchars(t('layouts.actions.export_version'), ENT_QUOTES, 'UTF-8') ?>
                                                            </a>
                                                            <?php if (($version['status'] ?? '') !== 'approved'): ?>
                                                                <form method="post" action="layouts.php" class="d-inline">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="action" value="approve">
                                                                    <input type="hidden" name="layout_id" value="<?= (int) $layout['id'] ?>">
                                                                    <input type="hidden" name="version" value="<?= (int) $version['version'] ?>">
                                                                    <button type="submit" class="btn btn-success btn-sm">
                                                                        <?= htmlspecialchars(t('layouts.actions.approve'), ENT_QUOTES, 'UTF-8') ?>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </details>

                            <div class="mt-3">
                                <details>
                                    <summary class="text-muted small"><?= htmlspecialchars(t('layouts.editor.edit_metadata'), ENT_QUOTES, 'UTF-8') ?></summary>
                                    <form method="post" action="layouts.php" class="mt-3 vstack gap-3">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_meta">
                                        <input type="hidden" name="layout_id" value="<?= (int) $layout['id'] ?>">
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.name'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($layout['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.form.description'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars((string) ($layout['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label"><?= htmlspecialchars(t('layouts.form.category'), ENT_QUOTES, 'UTF-8') ?></label>
                                                <select name="category" class="form-select">
                                                    <?php foreach (array_slice($categories, 1) as $category): ?>
                                                        <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" <?= ($layout['category'] ?? 'general') === $category ? 'selected' : '' ?>><?= htmlspecialchars(t('layouts.categories.' . $category), ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label"><?= htmlspecialchars(t('layouts.form.status'), ENT_QUOTES, 'UTF-8') ?></label>
                                                <select name="status" class="form-select">
                                                    <?php foreach (array_slice($statuses, 1) as $status): ?>
                                                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($layout['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= htmlspecialchars(t('layouts.statuses.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label"><?= htmlspecialchars(t('layouts.form.owner'), ENT_QUOTES, 'UTF-8') ?></label>
                                                <select name="owner_id" class="form-select">
                                                    <option value=""><?= htmlspecialchars(t('layouts.filters.owner_all'), ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php foreach ($ownerOptions as $owner): ?>
                                                        <option value="<?= (int) $owner['id'] ?>" <?= ((int) ($layout['owner_id'] ?? 0)) === (int) $owner['id'] ? 'selected' : '' ?>><?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <?= htmlspecialchars(t('layouts.editor.save_metadata'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            </div>

                            <div class="mt-3">
                                <details>
                                    <summary class="text-muted small"><?= htmlspecialchars(t('layouts.editor.new_version'), ENT_QUOTES, 'UTF-8') ?></summary>
                                    <form method="post" action="layouts.php" class="mt-3 vstack gap-3">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="create_version">
                                        <input type="hidden" name="layout_id" value="<?= (int) $layout['id'] ?>">
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.editor.version_status'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <select name="status" class="form-select">
                                                <?php foreach (array_slice($statuses, 1) as $status): ?>
                                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('layouts.statuses.' . $status), ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.editor.version_comment'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="text" name="comment" class="form-control" placeholder="<?= htmlspecialchars(t('layouts.editor.version_comment_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.editor.data_json'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <textarea name="data" class="form-control" rows="4" placeholder='<?= htmlspecialchars(t('layouts.editor.json_placeholder'), ENT_QUOTES, 'UTF-8') ?>'></textarea>
                                        </div>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.editor.meta_json'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <textarea name="meta" class="form-control" rows="3" placeholder='<?= htmlspecialchars(t('layouts.editor.json_placeholder'), ENT_QUOTES, 'UTF-8') ?>'></textarea>
                                        </div>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars(t('layouts.editor.assets_json'), ENT_QUOTES, 'UTF-8') ?></label>
                                            <textarea name="assets" class="form-control" rows="3" placeholder='<?= htmlspecialchars(t('layouts.editor.assets_placeholder'), ENT_QUOTES, 'UTF-8') ?>'></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-outline-success btn-sm">
                                                <?= htmlspecialchars(t('layouts.editor.save_version'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
