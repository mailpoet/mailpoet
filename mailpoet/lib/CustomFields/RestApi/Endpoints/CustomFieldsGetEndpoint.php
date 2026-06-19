<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\CustomFields\RestApi\CustomFieldApiException;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Validator\Builder;

class CustomFieldsGetEndpoint extends CustomFieldsEndpoint {
  private const ALLOWED_TYPES = [
    CustomFieldEntity::TYPE_TEXT,
    CustomFieldEntity::TYPE_TEXTAREA,
    CustomFieldEntity::TYPE_RADIO,
    CustomFieldEntity::TYPE_CHECKBOX,
    CustomFieldEntity::TYPE_SELECT,
    CustomFieldEntity::TYPE_DATE,
  ];

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
  }

  public function handle(Request $request): Response {
    $search = is_string($request->getParam('search')) ? (string)$request->getParam('search') : '';
    $orderby = is_string($request->getParam('orderby')) ? (string)$request->getParam('orderby') : 'name';
    $order = is_string($request->getParam('order')) ? (string)$request->getParam('order') : 'asc';
    $page = is_numeric($request->getParam('page')) ? max(1, (int)$request->getParam('page')) : 1;
    $perPage = is_numeric($request->getParam('per_page')) ? max(1, min(100, (int)$request->getParam('per_page'))) : 25;
    $group = is_string($request->getParam('group')) ? (string)$request->getParam('group') : 'all';
    $filter = $this->parseFilter($request->getParam('filter'));

    $result = $this->customFieldsRepository->listWithCounts([
      'search' => $search,
      'orderby' => $orderby,
      'order' => $order,
      'page' => $page,
      'per_page' => $perPage,
      'group' => $group,
      'filter' => $filter,
    ]);

    $items = array_map([$this, 'buildItemFromRow'], $result['items']);
    $pages = $result['total'] === 0 ? 0 : (int)ceil($result['total'] / max(1, $perPage));

    return new Response([
      'items' => $items,
      'meta' => [
        'count' => $result['total'],
        'pages' => $pages,
      ],
      'groups' => $result['groups'],
    ]);
  }

  /**
   * @param mixed $rawFilter
   * @return array{type?: string[]}
   */
  private function parseFilter($rawFilter): array {
    if ($rawFilter === null || $rawFilter === '' || $rawFilter === []) {
      return [];
    }
    if (!is_array($rawFilter)) {
      throw new CustomFieldApiException(
        __('Filters must be an object.', 'mailpoet'),
        400,
        'mailpoet_custom_fields_invalid_filter'
      );
    }

    $allowed = ['type'];
    foreach (array_keys($rawFilter) as $key) {
      if (!in_array($key, $allowed, true)) {
        throw new CustomFieldApiException(
          __('Unsupported custom fields filter.', 'mailpoet'),
          400,
          'mailpoet_custom_fields_invalid_filter'
        );
      }
    }

    $filter = [];
    if (array_key_exists('type', $rawFilter)) {
      $filter['type'] = $this->parseTypeFilter($rawFilter['type']);
    }

    return $filter;
  }

  /**
   * @param mixed $rawType
   * @return string[]
   */
  private function parseTypeFilter($rawType): array {
    if ($rawType === '' || $rawType === null) {
      return [];
    }
    $values = is_array($rawType) ? $rawType : [$rawType];
    $types = [];
    foreach ($values as $value) {
      if (!is_string($value) || !in_array($value, self::ALLOWED_TYPES, true)) {
        throw new CustomFieldApiException(
          __('Unsupported custom field type filter.', 'mailpoet'),
          400,
          'mailpoet_custom_fields_invalid_type'
        );
      }
      $types[] = $value;
    }
    return array_values(array_unique($types));
  }

  public static function getRequestSchema(): array {
    return [
      'search' => Builder::string(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'group' => Builder::string(),
      'filter' => Builder::object(),
    ];
  }
}
