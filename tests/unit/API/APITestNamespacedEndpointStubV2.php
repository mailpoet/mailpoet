<?php

namespace MailPoet\API\Endpoints\v2;

use MailPoet\API\Access as APIAccess;
use MailPoet\API\Endpoint as APIEndpoint;

if(!defined('ABSPATH')) exit;

class NamespacedEndpointStub extends APIEndpoint {
  public $permissions = array(
    'testVersion' => APIAccess::ALL
  );

  function testVersion() {
    return $this->successResponse('version_test_succeeded');
  }
}
