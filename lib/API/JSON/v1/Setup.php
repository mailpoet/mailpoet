<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Activator;
use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class Setup extends APIEndpoint {
  private $wp;
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS
  );

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function reset() {
    try {
      $activator = new Activator(new SettingsController());
      $activator->deactivate();
      $activator->activate();
      $this->wp->doAction('mailpoet_setup_reset');
      return $this->successResponse();
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}
