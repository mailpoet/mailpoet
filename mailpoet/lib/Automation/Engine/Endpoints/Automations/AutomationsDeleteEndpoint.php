<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Builder\DeleteAutomationController;
use MailPoet\Validator\Builder;

class AutomationsDeleteEndpoint extends Endpoint {
  /** @var DeleteAutomationController */
  private $deleteController;

  public function __construct(
    DeleteAutomationController $deleteController
  ) {
    $this->deleteController = $deleteController;
  }

  public function handle(Request $request): Response {
    $automationId = intval($request->getParam('id'));
    $this->deleteController->deleteAutomation($automationId);
    return new Response(null);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
