<?php
require __DIR__ . '/app/bootstrap.php';

if (!is_file(__DIR__ . '/config/app.php')) {
    header('Location: setup.php');
    exit;
}

header('Location: dashboard.php');
exit;
