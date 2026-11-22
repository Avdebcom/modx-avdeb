<?php
// Run PHP with MODX loaded:  php avdeb-eval.php '<code>'   ($modx is available)
define('MODX_API_MODE', true);
require __DIR__ . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new \MODX\Revolution\modX();
$modx->initialize('web');
$modx->getService('error', 'error.modError');
eval($argv[1] ?? '');
