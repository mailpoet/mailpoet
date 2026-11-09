<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Endpoints;

use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetsController;
use MailPoet\Validator\Builder;

class EmailPresetsGetEndpoint extends EmailPresetsEndpoint {
  use ListingRequestValidationTrait;

  public function handle(Request $request): Response {
    $this->validatePagination($request);
    $this->validateSortField($request->getParam('orderby'), ['title', 'updated_at']);
    $this->validateSortOrder($request->getParam('order'));

    $perPage = $this->getIntParam($request, 'per_page', 20);
    $result = $this->controller->list([
      'group' => $this->getStringParam($request, 'group', EmailPresetsController::GROUP_ALL),
      'search' => $this->getStringParam($request, 'search', ''),
      'orderby' => $this->getStringParam($request, 'orderby', 'updated_at'),
      'order' => $this->getStringParam($request, 'order', 'desc'),
      'page' => $this->getIntParam($request, 'page', 1),
      'per_page' => $perPage,
    ]);

    $groups = [];
    foreach ($result['groups'] as $name => $count) {
      $groups[] = ['name' => $name, 'count' => $count];
    }
    return new Response([
      'items' => array_map([$this, 'buildItem'], $result['items']),
      'meta' => [
        'count' => $result['count'],
        'pages' => (int)ceil($result['count'] / $perPage),
      ],
      'groups' => $groups,
    ]);
  }

  private function getStringParam(Request $request, string $name, string $default): string {
    $value = $request->getParam($name);
    return is_string($value) && $value !== '' ? $value : $default;
  }

  private function getIntParam(Request $request, string $name, int $default): int {
    $value = $request->getParam($name);
    return is_numeric($value) ? (int)$value : $default;
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'email_presets';
  }

  public static function getRequestSchema(): array {
    return [
      'group' => Builder::string()->pattern('^(' . EmailPresetsController::GROUP_ALL . '|' . EmailPresetsController::GROUP_TRASH . ')$'),
      'search' => Builder::string(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer()->minimum(1),
      'per_page' => Builder::integer()->minimum(1),
    ];
  }
}
