<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/woocommerce-memberships';
$downloadCommand = 'download:woo-commerce-memberships-zip';
$configParameterName = 'woo_memberships_version';
$versionsFilenameSuffix = 'woocommerce_memberships_version.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilenameSuffix);
