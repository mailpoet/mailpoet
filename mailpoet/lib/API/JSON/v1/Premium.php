<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WPCOM\DotcomHelperFunctions;

class Premium extends APIEndpoint {
  const PREMIUM_PLUGIN_SLUG = 'mailpoet-premium';
  const PREMIUM_PLUGIN_PATH = 'mailpoet-premium/mailpoet-premium.php';
  // This is the path to the managed plugin on Dotcom platform. It is relative to WP_PLUGIN_DIR.
  const DOTCOM_SYMLINK_PATH = '../../../../wordpress/plugins/mailpoet-premium/latest';

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  private ServicesChecker $servicesChecker;

  private WPFunctions $wp;

  private DotcomHelperFunctions $dotcomHelperFunctions;

  private Installer $premiumInstaller;

  public function __construct(
    ServicesChecker $servicesChecker,
    WPFunctions $wp,
    DotcomHelperFunctions $dotcomHelperFunctions,
    ?Installer $premiumInstaller = null
  ) {
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
    $this->dotcomHelperFunctions = $dotcomHelperFunctions;
    $this->premiumInstaller = $premiumInstaller ?? new Installer(Installer::PREMIUM_PLUGIN_SLUG);
  }

  public function installPlugin() {
    if (!$this->wp->currentUserCan('install_plugins')) {
      return $this->forbidden();
    }

    $premiumKeyValid = $this->servicesChecker->isPremiumKeyValid(false);
    if (!$premiumKeyValid) {
      return $this->error(__('Premium key is not valid.', 'mailpoet'));
    }

    // If we are in Dotcom platform, we try to symlink the plugin instead of downloading it
    try {
      if ($this->dotcomHelperFunctions->isDotcom()) {
        $result = symlink(self::DOTCOM_SYMLINK_PATH, WP_PLUGIN_DIR . '/' . self::PREMIUM_PLUGIN_SLUG);
        if ($result === true) {
          return $this->successResponse();
        }
      }
    } catch (\Exception $e) {
      // Do nothing and continue with a regular installation
    }

    $result = $this->wp->installPlugin($this->premiumInstaller->buildDownloadUrl());
    if ($result !== true) {
      return $this->error(__('Error when installing MailPoet Premium plugin.', 'mailpoet'));
    }
    return $this->successResponse();
  }

  public function activatePlugin() {
    if (!$this->wp->currentUserCan('activate_plugins')) {
      return $this->forbidden();
    }

    $premiumKeyValid = $this->servicesChecker->isPremiumKeyValid(false);
    if (!$premiumKeyValid) {
      return $this->error(__('Premium key is not valid.', 'mailpoet'));
    }

    $result = $this->wp->activatePlugin(self::PREMIUM_PLUGIN_PATH);
    if ($result !== null) {
      return $this->error(__('Error when activating MailPoet Premium plugin.', 'mailpoet'));
    }
    return $this->successResponse();
  }

  private function forbidden() {
    return $this->errorResponse([
      APIError::FORBIDDEN => __('You do not have the required permissions.', 'mailpoet'),
    ], [], Response::STATUS_FORBIDDEN);
  }

  private function error($message) {
    return $this->badRequest([
      APIError::BAD_REQUEST => $message,
    ]);
  }
}
