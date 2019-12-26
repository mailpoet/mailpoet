<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

class APITestNamespacedEndpointStubV1 extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
    'methods' => [
      'test' => AccessControl::NO_ACCESS_RESTRICTION,
      'restricted' => AccessControl::PERMISSION_MANAGE_SETTINGS,
    ],
  ];

  public function test($data) {
    return $this->successResponse($data);
  }

  public function restricted($data) {
    return $this->successResponse($data);
  }
}
