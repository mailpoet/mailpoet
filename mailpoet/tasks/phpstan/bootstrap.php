<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// Constants
define('WPINC', 'wp-includes');
define('WP_MEMORY_LIMIT', 268435456);
define('WP_MAX_MEMORY_LIMIT', 268435456);
define('MAILPOET_VERSION', '1.0.0');
define('MAILPOET_PREMIUM_VERSION', '1.0.0');

// This needs to be set because \MailPoet\Doctrine\TablePrefixMetadataFactory can't construct without it
MailPoet\Config\Env::$dbPrefix = 'wp_';

// PHPStan for Doctrine needs to know the version of Doctrine packages we use for some checks.
// We need to point it to the prefixer's installed.php file because that's where the Doctrine packages are installed.
$data = require __DIR__ . '/../../prefixer/vendor/composer/installed.php';
\Composer\InstalledVersions::reload($data);

// Load tracy
$tracyPath = __DIR__ . '/../../tools/vendor/tracy.phar';
require_once($tracyPath);
