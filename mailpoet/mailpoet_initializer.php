<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use MailPoet\Config\Env;
use MailPoet\Config\RequirementsChecker;
use Tracy\Debugger;

if (empty($mailpoetPlugin)) exit;

require_once($mailpoetPlugin['autoloader']);

if (PHP_VERSION_ID >= 80200) {
  $tracyPath = __DIR__ . '/tools/vendor/tracy.phar';
} else {
  $tracyPath = __DIR__ . '/tools/vendor/tracy-legacy.phar';
}
if (WP_DEBUG && PHP_VERSION_ID >= 70100 && file_exists($tracyPath)) {
  require_once $tracyPath;

  if (getenv('MAILPOET_TRACY_PRODUCTION_MODE')) {
    $logDir = getenv('MAILPOET_TRACY_LOG_DIR');
    if (!$logDir) {
      throw new RuntimeException("Environment variable 'MAILPOET_TRACY_LOG_DIR' was not set.");
    }

    if (!is_dir($logDir)) {
      @mkdir($logDir, 0777, true);
    }

    if (!is_writable($logDir)) {
      throw new RuntimeException("Logging directory '$logDir' is not writable.'");
    }

    Debugger::enable(Debugger::PRODUCTION, $logDir);
    Debugger::$logSeverity = E_ALL;
  } else {
    function render_tracy() {
      ob_start();
      Debugger::renderLoader();
      $tracyScriptHtml = ob_get_clean();

      // strip 'async' to ensure all AJAX request are caught
      // (even when fired immediately after page starts loading)
      // see: https://github.com/nette/tracy/issues/246
      $tracyScriptHtml = str_replace('async', '', $tracyScriptHtml);

      // set higher number of displayed AJAX rows
      $maxAjaxRows = 4;
      $tracyScriptHtml .= "<script>window.TracyMaxAjaxRows = $maxAjaxRows;</script>\n";

      // just minor adjustments to Debugger::renderLoader() output
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $tracyScriptHtml;
    }

    add_action('admin_enqueue_scripts', 'render_tracy', PHP_INT_MAX, 0);
    session_start();
    Debugger::enable(Debugger::DEVELOPMENT);

    if (getenv('MAILPOET_DISABLE_TRACY_PANEL')) {
      Debugger::$showBar = false;
    }
  }
  define('MAILPOET_DEVELOPMENT', true);
}

define('MAILPOET_VERSION', $mailpoetPlugin['version']);

Env::init(
  $mailpoetPlugin['filename'],
  $mailpoetPlugin['version']
);

$requirements = new RequirementsChecker();
$requirementsCheckResults = $requirements->checkAllRequirements();
if (
  !$requirementsCheckResults[RequirementsChecker::TEST_VENDOR_SOURCE]
) {
  return;
}

// Ensure functions like get_plugins, etc.
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Initialize the Email Editor container to allow getting its instances in MailPoet constructors.
Email_Editor_Container::init();

$initializer = MailPoet\DI\ContainerWrapper::getInstance()->get(MailPoet\Config\Initializer::class);
$initializer->init();
