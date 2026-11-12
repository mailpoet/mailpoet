<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

/**
 * Deletes a preset in the trash permanently.
 */
class EmailPresetDeleteEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    $preset = $this->getPreset($request, true);
    if ($preset->post_status !== 'trash') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      throw new ApiException(__('The email preset is not in the trash.', 'mailpoet'), 400, 'mailpoet_email_preset_not_trashed');
    }
    if (!$this->controller->delete($preset)) {
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
