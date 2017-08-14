<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Activator;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class Setup extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS
  );
  private $access_control;

  function __construct(AccessControl $access_control) {
    $this->access_control = $access_control;
  }

  function reset() {
    try {
      $activator = new Activator($this->access_control);
      $activator->deactivate();
      $activator->activate();
      Hooks::doAction('mailpoet_setup_reset');
      return $this->successResponse();
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}
