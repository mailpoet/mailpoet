<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/woocommerce-subscriptions';
$downloadCommand = 'download:woo-commerce-subscriptions-zip';
$configParameterName = 'woo_subscriptions_version';
$versionsFilenameSuffix = 'woocommerce_subscriptions_version.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilenameSuffix);
