<?php

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Coinsnap\\WC\\' => array($baseDir . '/src'),
    'Coinsnap\\' => array($vendorDir . '/coinsnap/coinsnap-php/src'),
);
