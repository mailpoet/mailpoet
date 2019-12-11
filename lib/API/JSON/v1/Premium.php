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
  private $services_checker;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ServicesChecker $services_checker,
    WPFunctions $wp
  ) {
    $this->services_checker = $services_checker;
    $this->wp = $wp;
  }

  public function installPlugin() {
    $premium_key_valid = $this->services_checker->isPremiumKeyValid(false);
    if (!$premium_key_valid) {
      return $this->error($this->wp->__('Premium key is not valid.', 'mailpoet'));
    }

    $plugin_info = $this->wp->pluginsApi('plugin_information', [
      'slug' => self::PREMIUM_PLUGIN_SLUG,
    ]);

    if (!$plugin_info || $plugin_info instanceof WP_Error) {
      return $this->error($this->wp->__('Error when installing MailPoet Premium plugin.', 'mailpoet'));
    }

    $plugin_info = (array)$plugin_info;
    $result = $this->wp->installPlugin($plugin_info['download_link']);
    if ($result !== true) {
      return $this->error($this->wp->__('Error when installing MailPoet Premium plugin.', 'mailpoet'));
    }
    return $this->successResponse();
  }

  public function activatePlugin() {
    $premium_key_valid = $this->services_checker->isPremiumKeyValid(false);
    if (!$premium_key_valid) {
      return $this->error($this->wp->__('Premium key is not valid.', 'mailpoet'));
    }

    $result = $this->wp->activatePlugin(self::PREMIUM_PLUGIN_PATH);
    if ($result !== null) {
      return $this->error($this->wp->__('Error when activating MailPoet Premium plugin.', 'mailpoet'));
    }
    return $this->successResponse();
  }

  private function error($message) {
    return $this->badRequest([
      APIError::BAD_REQUEST => $message,
    ]);
  }
}
