<?php
namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\Activator;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class Setup extends APIEndpoint {
  function reset() {
    try {
      $activator = new Activator();
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
