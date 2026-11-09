<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

class EmailPresetTrashEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    $preset = $this->getPreset($request);
    if (!$this->controller->trash($preset)) {
      throw new ApiException(__('The email preset could not be moved to the trash.', 'mailpoet'), 500, 'mailpoet_email_preset_trash_failed');
    }
    return $this->presetResponse($preset->ID, true);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
