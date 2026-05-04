<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Validator\Builder;

class AutomationVersionsGetEndpoint extends Endpoint {
  /** @var AutomationStorage */
  private $automationStorage;

  public function __construct(
    AutomationStorage $automationStorage
  ) {
    $this->automationStorage = $automationStorage;
  }

  public function handle(Request $request): Response {
    $rawId = $request->getParam('id');
    $automationId = is_numeric($rawId) ? (int)$rawId : 0;
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation) {
      throw Exceptions::automationNotFound($automationId);
    }

    $versions = $this->automationStorage->getAutomationVersionDates($automationId);
    $currentVersionId = $automation->getVersionId();

    $items = array_map(
      function (array $version) use ($currentVersionId): array {
        return [
          'id' => $version['id'],
          'created_at' => $version['created_at']->format(\DateTimeInterface::ATOM),
          'is_current' => $version['id'] === $currentVersionId,
        ];
      },
      $versions
    );

    return new Response(['items' => $items]);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
