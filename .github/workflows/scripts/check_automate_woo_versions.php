<?php

require_once __DIR__ . '/helpers.php';

$repository = 'woocommerce/automatewoo';
$downloadCommand = 'download:automate-woo-zip';
$configParameterName = 'automate_woo_version';
$versionsFilename = 'automate_woo_versions.txt';

replacePrivatePluginVersion($repository, $downloadCommand, $configParameterName, $versionsFilename);
