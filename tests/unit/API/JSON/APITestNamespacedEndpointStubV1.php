<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Access as APIAccess;

if(!defined('ABSPATH')) exit;

class APITestNamespacedEndpointStubV1 extends APIEndpoint {
  public $permissions = array(
    'test' => APIAccess::ALL
  );

  function test($data) {
    return $this->successResponse($data);
  }
}
