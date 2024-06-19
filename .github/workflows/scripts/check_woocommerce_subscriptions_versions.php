<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/woocommerce-subscriptions';
$downloadCommand = 'download:woo-commerce-subscriptions-zip';
$configParameterName = 'woo_subscriptions_version';
$versionsFilename = 'woocommerce_subscriptions_versions.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilename);
