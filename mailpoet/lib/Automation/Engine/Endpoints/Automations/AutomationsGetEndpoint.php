<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use DateTimeImmutable;
use Exception;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\Automation;
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

    $filter = $this->parseFilter($request->getParam('filter'));

    $automations = $this->automationStorage->getAutomations(
      $status,
      $orderBy,
      $order,
      $page,
      $perPage,
      $search,
      $filter['triggerKeys'],
      $filter['hasActivity'],
      $filter['createdAfter'],
      $filter['createdBefore'],
      $filter['updatedAfter'],
      $filter['updatedBefore']
    );
    $automationCount = $this->automationStorage->getAutomationCount(
      $status,
      $search,
      $filter['triggerKeys'],
      $filter['hasActivity'],
      $filter['createdAfter'],
      $filter['createdBefore'],
      $filter['updatedAfter'],
      $filter['updatedBefore']
    );

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
      'groups' => $this->buildGroups($search, $filter),
    ]);
  }

  /**
   * Per-status counts for the listing tabs. Counts honor the active search and
   * the trigger / activity / date filters, but not the status filter itself
   * (each tab represents a status). "all" excludes the trash, matching the UI.
   *
   * @param array{triggerKeys: ?array, hasActivity: ?bool, createdAfter: ?DateTimeImmutable, createdBefore: ?DateTimeImmutable, updatedAfter: ?DateTimeImmutable, updatedBefore: ?DateTimeImmutable} $filter
   * @return array<array{name: string, label: string, count: int}>
   */
  private function buildGroups(?string $search, array $filter): array {
    $countFor = function (array $status) use ($search, $filter): int {
      return $this->automationStorage->getAutomationCount(
        $status,
        $search,
        $filter['triggerKeys'],
        $filter['hasActivity'],
        $filter['createdAfter'],
        $filter['createdBefore'],
        $filter['updatedAfter'],
        $filter['updatedBefore']
      );
    };

    return [
      ['name' => 'all', 'label' => 'all', 'count' => $countFor([Automation::STATUS_ACTIVE, Automation::STATUS_DRAFT, Automation::STATUS_DEACTIVATING])],
      ['name' => Automation::STATUS_ACTIVE, 'label' => Automation::STATUS_ACTIVE, 'count' => $countFor([Automation::STATUS_ACTIVE])],
      ['name' => Automation::STATUS_DRAFT, 'label' => Automation::STATUS_DRAFT, 'count' => $countFor([Automation::STATUS_DRAFT])],
      ['name' => Automation::STATUS_TRASH, 'label' => Automation::STATUS_TRASH, 'count' => $countFor([Automation::STATUS_TRASH])],
    ];
  }

  /**
   * Normalize the raw `filter` query param into typed storage arguments.
   *
   * @param mixed $rawFilter
   * @return array{triggerKeys: ?array, hasActivity: ?bool, createdAfter: ?DateTimeImmutable, createdBefore: ?DateTimeImmutable, updatedAfter: ?DateTimeImmutable, updatedBefore: ?DateTimeImmutable}
   */
  private function parseFilter($rawFilter): array {
    $filter = is_array($rawFilter) ? $rawFilter : [];

    $triggerKeys = null;
    if (isset($filter['trigger']) && is_array($filter['trigger']) && $filter['trigger'] !== []) {
      $triggerKeys = array_values(array_filter($filter['trigger'], 'is_string'));
      $triggerKeys = $triggerKeys === [] ? null : $triggerKeys;
    }

    $hasActivity = null;
    if (isset($filter['activity']) && $filter['activity'] !== '') {
      $hasActivity = in_array($filter['activity'], ['has', 'true', '1', true, 1], true);
    }

    return [
      'triggerKeys' => $triggerKeys,
      'hasActivity' => $hasActivity,
      'createdAfter' => $this->parseDate($filter['created_after'] ?? null),
      'createdBefore' => $this->parseDate($filter['created_before'] ?? null),
      'updatedAfter' => $this->parseDate($filter['updated_after'] ?? null),
      'updatedBefore' => $this->parseDate($filter['updated_before'] ?? null),
    ];
  }

  /** @param mixed $value */
  private function parseDate($value): ?DateTimeImmutable {
    if (!is_string($value) || trim($value) === '') {
      return null;
    }
    try {
      return new DateTimeImmutable($value);
    } catch (Exception $e) {
      return null;
    }
  }

  public static function getRequestSchema(): array {
    return [
      'status' => Builder::array(Builder::string()),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'search' => Builder::string(),
      'filter' => Builder::object([
        'trigger' => Builder::array(Builder::string()),
        'activity' => Builder::string(),
        'created_after' => Builder::string(),
        'created_before' => Builder::string(),
        'updated_after' => Builder::string(),
        'updated_before' => Builder::string(),
      ]),
    ];
  }
}
