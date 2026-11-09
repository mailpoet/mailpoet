<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

class EmailPresetDuplicateEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    return $this->presetResponse($this->controller->duplicate($this->getPreset($request)));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
