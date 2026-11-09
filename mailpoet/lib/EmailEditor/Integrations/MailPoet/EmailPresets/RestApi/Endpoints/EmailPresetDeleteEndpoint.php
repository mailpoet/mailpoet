<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

/**
 * Deletes a preset permanently. Use the trash endpoint to move it to the trash.
 */
class EmailPresetDeleteEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    if (!$this->controller->delete($this->getPreset($request, true))) {
      throw new ApiException(__('The email preset could not be deleted.', 'mailpoet'), 500, 'mailpoet_email_preset_delete_failed');
    }
    return new Response(null);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}
