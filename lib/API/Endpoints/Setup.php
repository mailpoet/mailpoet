<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\Config\Activator;

if(!defined('ABSPATH')) exit;

class Setup extends APIEndpoint {
  function __construct() {
  }

  function reset() {
    try {
      $activator = new Activator();
      $activator->deactivate();
      $activator->activate();
      return $this->successResponse();
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getMessage()
      ));
    }
  }
}
