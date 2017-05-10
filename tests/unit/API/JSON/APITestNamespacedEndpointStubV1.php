<?php

namespace MailPoet\API\JSON\Endpoints\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Access as APIAccess;

if(!defined('ABSPATH')) exit;

class NamespacedEndpointStub extends APIEndpoint {
  public $permissions = array(
    'test' => APIAccess::ALL
  );

  function test($data) {
    return $this->successResponse($data);
  }
}
