<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;

class Premium extends APIEndpoint {
  const PREMIUM_PLUGIN_SLUG = 'mailpoet-premium';
  const PREMIUM_PLUGIN_PATH = 'mailpoet-premium/mailpoet-premium.php';

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ServicesChecker $servicesChecker,
    WPFunctions $wp
  ) {
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
  }

  public function installPlugin() {
    $premiumKeyValid = $this->servicesChecker->isPremiumKeyValid(false);
    if (!$premiumKeyValid) {
      return $this->error(__('Premium key is not valid.', 'mailpoet'));
    }

    $pluginInfo = $this->wp->pluginsApi('plugin_information', [
      'slug' => self::PREMIUM_PLUGIN_SLUG,
    ]);

    if (!$pluginInfo || $pluginInfo instanceof WP_Error) {
      return $this->error(__('Error when installing MailPoet Premium plugin.', 'mailpoet'));
    }

    $pluginInfo = (array)$pluginInfo;
    $result = $this->wp->installPlugin($pluginInfo['download_link']);
    if ($result !== true) {
      return $this->error(__('Error when installing MailPoet Premium plugin.', 'mailpoet'));
    }
    return $this->successResponse();
  }

  public function activatePlugin() {
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

  private function error($message) {
    return $this->badRequest([
      APIError::BAD_REQUEST => $message,
    ]);
  }
}
