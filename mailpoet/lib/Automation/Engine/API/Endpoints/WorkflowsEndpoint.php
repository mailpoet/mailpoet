<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API\Endpoints;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;

class WorkflowsEndpoint extends Endpoint {
  public function get(Request $request): Response {
    return new Response(['message' => 'Hello world.']);
  }
}
