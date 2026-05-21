<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\ManualStart\ManualStartService;
use MailPoet\Validator\Builder;

class AutomationManualStartEndpoint extends Endpoint {
  /** @var ManualStartService */
  private $manualStartService;

  public function __construct(
    ManualStartService $manualStartService
  ) {
    $this->manualStartService = $manualStartService;
  }

  public function handle(Request $request): Response {
    return new Response($this->manualStartService->start(
      $this->getRequiredInt($request->getParam('id')),
      $this->getRequiredInt($request->getParam('segment_id')),
      $this->getOptionalInt($request->getParam('filter_segment_id')),
      $this->getRequiredString($request->getParam('preview_signature'))
    ));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'segment_id' => Builder::integer()->required(),
      'filter_segment_id' => Builder::integer()->nullable(),
      'preview_signature' => Builder::string()->required(),
    ];
  }

  /** @param mixed $value */
  private function getRequiredInt($value): int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return 0;
  }

  /** @param mixed $value */
  private function getOptionalInt($value): ?int {
    if (!is_numeric($value)) {
      return null;
    }
    return (int)$value;
  }

  /** @param mixed $value */
  private function getRequiredString($value): string {
    if (is_string($value)) {
      return $value;
    }
    return '';
  }
}
