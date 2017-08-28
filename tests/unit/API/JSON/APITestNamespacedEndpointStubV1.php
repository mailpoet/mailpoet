<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

if(!defined('ABSPATH')) exit;

class APITestNamespacedEndpointStubV1 extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
    'methods' => array(
      'test' => AccessControl::NO_ACCESS_RESTRICTION,
      'restricted' => AccessControl::PERMISSION_MANAGE_SETTINGS
    )
  );

  function test($data) {
    return $this->successResponse($data);
  }

  function restricted($data) {
    return $this->successResponse($data);
  }
}
