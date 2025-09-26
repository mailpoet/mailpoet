<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Validator\Builder;

class AutomationsGetEndpoint extends Endpoint {
  /** @var AutomationMapper */
  private $automationMapper;

  /** @var AutomationStorage */
  private $automationStorage;

  public function __construct(
    AutomationMapper $automationMapper,
    AutomationStorage $automationStorage
  ) {
    $this->automationMapper = $automationMapper;
    $this->automationStorage = $automationStorage;
  }

  public function handle(Request $request): Response {
    $status = $request->getParam('status') ? (array)$request->getParam('status') : null;

    $orderByParam = $request->getParam('orderby');
    $orderBy = is_string($orderByParam) ? $orderByParam : null;

    $orderParam = $request->getParam('order');
    $order = is_string($orderParam) ? $orderParam : null;

    $pageParam = $request->getParam('page');
    $page = is_numeric($pageParam) ? max(1, (int)$pageParam) : 1;

    $perPageParam = $request->getParam('per_page');
    $perPage = is_numeric($perPageParam) ? max(1, (int)$perPageParam) : null;

    $searchParam = $request->getParam('search');
    $search = is_string($searchParam) ? $searchParam : null;

    $automations = $this->automationStorage->getAutomations($status, $orderBy, $order, $page, $perPage, $search);
    $automationCount = $this->automationStorage->getAutomationCount($status, $search);

    if ($automationCount === 0) {
      $pages = 0;
    } elseif ($perPage !== null) {
      $pages = (int)ceil($automationCount / $perPage);
    } else {
      $pages = 1;
    }

    return new Response([
      'items' => $this->automationMapper->buildAutomationList($automations),
      'meta' => [
          'pages' => $pages,
          'count' => $automationCount,
        ],
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'status' => Builder::array(Builder::string()),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'search' => Builder::string(),
    ];
  }
}
