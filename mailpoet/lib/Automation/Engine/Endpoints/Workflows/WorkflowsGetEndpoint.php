<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\API\Request;
use MailPoet\Automation\Engine\API\Response;

class WorkflowsGetEndpoint extends Endpoint {
  public function handle(Request $request): Response {
    return new Response(['message' => 'Hello world.']);
  }
}
