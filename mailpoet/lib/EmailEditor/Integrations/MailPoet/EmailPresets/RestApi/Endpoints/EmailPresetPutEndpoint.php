<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

class EmailPresetPutEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    $preset = $this->getPreset($request);
    $this->controller->rename($preset, $this->getTitle($request));
    return $this->presetResponse($preset->ID);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'title' => Builder::string()->minLength(1)->maxLength(255)->required(),
    ];
  }
}
