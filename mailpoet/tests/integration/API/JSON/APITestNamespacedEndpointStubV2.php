<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v2;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

class APITestNamespacedEndpointStubV2 extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
    'methods' => [
      'test' => AccessControl::NO_ACCESS_RESTRICTION,
    ],
  ];

  public function testVersion() {
    return $this->successResponse('v2');
  }
}
