<?php

namespace MailPoet\API\JSON\v2;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

if(!defined('ABSPATH')) exit;

class APITestNamespacedEndpointStubV2 extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
    'methods' => array(
      'test' => AccessControl::NO_ACCESS_RESTRICTION
    )
  );

  function testVersion() {
    return $this->successResponse('v2');
  }
}
