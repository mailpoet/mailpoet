<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\AI\AIController;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Validator\Builder;

class AutomationsAIGetEndpoint extends Endpoint {
  /** @var AIController */
  private $aiController;

  /** @var AutomationMapper */
  private $automationMapper;

  public function __construct(
    AIController $aiController,
    AutomationMapper $automationMapper
  ) {
    $this->aiController = $aiController;
    $this->automationMapper = $automationMapper;
  }

  public function handle(Request $request): Response {
    $prompt = $request->getParam('prompt');
    $automation = $this->aiController->generateAutomation($prompt);
    return new Response($this->automationMapper->buildAutomation($automation));
  }

  public static function getRequestSchema(): array {
    return [
      'prompt' => Builder::string(),
    ];
  }
}
