<?php
ob_start();
$content();
$contentMarkup = ob_get_clean();
echo $this->render('wrapper.tpl.php', [
    'title' => $title ?? 'Turnier-App',
    'menu' => $menu ?? [],
    'user' => $user ?? [],
    'content' => $contentMarkup,
]);
