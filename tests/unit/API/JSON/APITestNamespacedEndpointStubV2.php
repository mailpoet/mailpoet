<?php

namespace MailPoet\API\JSON\v2;

use MailPoet\API\JSON\Access as APIAccess;
use MailPoet\API\JSON\Endpoint as APIEndpoint;

if(!defined('ABSPATH')) exit;

class NamespacedEndpointStub extends APIEndpoint {
  public $permissions = array(
    'testVersion' => APIAccess::ALL
  );

  function testVersion() {
    return $this->successResponse('v2');
  }
}
