<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Validator\Builder;

class CustomFieldsGetEndpoint extends CustomFieldsEndpoint {
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

    $result = $this->customFieldsRepository->listWithCounts([
      'search' => $search,
      'orderby' => $orderby,
      'order' => $order,
      'page' => $page,
      'per_page' => $perPage,
    ]);

    $items = array_map([$this, 'buildItemFromRow'], $result['items']);
    $pages = $result['total'] === 0 ? 0 : (int)ceil($result['total'] / max(1, $perPage));

    return new Response([
      'items' => $items,
      'meta' => [
        'count' => $result['total'],
        'pages' => $pages,
      ],
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'search' => Builder::string(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
    ];
  }
}
