<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/woocommerce-memberships';
$downloadCommand = 'download:woo-commerce-memberships-zip';
$configParameterName = 'woo_memberships_version';
$versionsFilename = 'woocommerce_memberships_versions.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName);
