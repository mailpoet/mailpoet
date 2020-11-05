<?php

// Constants
define('ABSPATH', getenv('WP_ROOT') . '/');
define('WPINC', 'wp-includes');
define('WP_DEBUG', false);
define('WP_LANG_DIR', 'wp-content/languages');
define('WP_PLUGIN_DIR', 'wp-content/plugins');
define('WP_MEMORY_LIMIT', 268435456);
define('WP_MAX_MEMORY_LIMIT', 268435456);
define('ARRAY_A', 'ARRAY_A');
define('OBJECT', 'OBJECT');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);
define('MAILPOET_VERSION', '1.0.0');

// Define Database Tables constants
$dbConfig = new \MailPoet\Config\Database();
$dbConfig->defineTables();

// This needs to be set because \MailPoet\Doctrine\TablePrefixMetadataFactory can't construct without it
MailPoet\Config\Env::$dbPrefix = 'wp_';

// Classes needed in runtime
MailPoet\Mailer\WordPress\PHPMailerLoader::load();
require_once(ABSPATH . 'wp-includes/class-wp-widget.php'); // We extend this class it is need in runtime
// Load tracy
$tracyPath = __DIR__ . '/../../tools/vendor/tracy.phar';
require_once($tracyPath);
