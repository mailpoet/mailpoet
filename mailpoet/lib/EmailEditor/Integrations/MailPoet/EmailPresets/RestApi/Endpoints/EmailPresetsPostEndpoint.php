<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Validator\Builder;

/**
 * Creates a blank preset, or saves a block email or another preset as a new preset.
 */
class EmailPresetsPostEndpoint extends EmailPresetsEndpoint {
  public function handle(Request $request): Response {
    $title = $this->getTitle($request);

    $source = null;
    $sourceId = $request->getParam('source_id');
    if ($sourceId !== null) {
      $source = is_numeric($sourceId) ? $this->controller->getSource((int)$sourceId) : null;
      if (!$source) {
        throw new ApiException(__('The email does not exist.', 'mailpoet'), 404, 'mailpoet_email_preset_source_not_found');
      }
    }

    return $this->presetResponse($this->controller->create($title, $source));
  }

  public static function getRequestSchema(): array {
    return [
      'title' => Builder::string()->minLength(1)->maxLength(255)->required(),
      'source_id' => Builder::integer()->minimum(1),
    ];
  }
}
