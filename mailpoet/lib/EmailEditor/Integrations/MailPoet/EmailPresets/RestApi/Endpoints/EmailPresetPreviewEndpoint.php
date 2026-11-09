<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

class EmailPresetPreviewEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    $rendered = $this->controller->render($this->getPreset($request, true));
    return new Response([
      'subject' => $rendered['subject'],
      'html' => $rendered['html'],
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
