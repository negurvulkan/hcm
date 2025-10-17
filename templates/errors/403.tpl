<section class="py-5 text-center">
    <div class="container">
        <h1 class="display-6 mb-3"><?= htmlspecialchars(t('auth.errors.forbidden_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="lead"><?= htmlspecialchars(t('auth.errors.forbidden_message'), ENT_QUOTES, 'UTF-8') ?></p>
        <a href="dashboard.php" class="btn btn-accent"><?= htmlspecialchars(t('auth.errors.forbidden_back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
