<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/automatewoo';
$downloadCommand = 'download:automate-woo-zip';
$configParameterName = 'automate_woo_version';
$versionsFilenameSuffix = 'automate_woo_version.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilenameSuffix);
