<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\API\Endpoints;

use MailPoet\API\REST\Endpoint as APIEndpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

class Endpoint extends APIEndpoint {
  /** @var callable|null */
  private $requestCallback;

  public function __construct(
    callable $requestCallback = null
  ) {
    $this->requestCallback = $requestCallback;
  }

  public function handle(Request $request): Response {
    if ($this->requestCallback) {
      ($this->requestCallback)($request);
    }
    return new Response();
  }

  public function checkPermissions(): bool {
    return true;
  }

  public static function getRequestSchema(): array {
    return [
      'required' => Builder::string()->required(),
      'string' => Builder::string(),
      'number-1' => Builder::number(),
      'number-2' => Builder::number(),
      'integer-1' => Builder::integer(),
      'integer-2' => Builder::integer(),
      'boolean-1' => Builder::boolean(),
      'boolean-2' => Builder::boolean(),
      'null' => Builder::null(),
    ];
  }
}
