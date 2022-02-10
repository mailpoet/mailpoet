<?php declare(strict_types = 1);

namespace MailPoet\Automation\API\Endpoints;

use MailPoet\Automation\API\Endpoint;
use MailPoet\Automation\API\Request;
use MailPoet\Automation\API\Response;

class WorkflowsEndpoint extends Endpoint {
  public function get(Request $request): Response {
    return new Response(['message' => 'Hello world.']);
  }
}
