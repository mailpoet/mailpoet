<?php declare(strict_types = 1);

namespace MailPoet\API\REST;

use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Validator\Builder;

/**
 * Base class for REST endpoints that expose a MailPoet listing
 * (search, sort, filter, pagination) via the shared
 * {@see ListingRepository} infrastructure.
 *
 * Concrete subclasses only need to supply the repository and the
 * per-row response mapper. The request schema, parameter parsing,
 * and response shape (`{items, meta:{count, pages}, filters, groups}`)
 * are standardized so all DataViews-backed listings in the admin UI
 * consume the same contract.
 */
abstract class AbstractListingEndpoint extends Endpoint {
  public const DEFAULT_PER_PAGE = 20;
  public const MAX_PER_PAGE = 100;
  // Sentinel cap on `page`. Keeps `(page-1) * per_page` from producing
  // huge OFFSETs that would push MySQL through millions of skipped rows
  // when a client (or fuzzer) sends `page=999999999`.
  public const MAX_PAGE = 100000;

  /** @var ListingHandler */
  private $listingHandler;

  public function __construct(
    ListingHandler $listingHandler
  ) {
    $this->listingHandler = $listingHandler;
  }

  /**
   * Subclasses MUST override `checkPermissions()` to declare a real
   * capability. The inherited default from `Endpoint::checkPermissions()`
   * checks a non-existent `admin` capability and would fail closed but
   * silently. PHP does not let us redeclare the parent method as `abstract`
   * here without breaking other consumers, so this is enforced by review +
   * tests instead of the type system.
   */
  public function handle(Request $request): Response {
    $definition = $this->buildDefinition($request);
    $repository = $this->getListingRepository();

    $rows = $repository->getData($definition);
    $count = $repository->getCount($definition);
    $perPage = $definition->getLimit() ?: self::DEFAULT_PER_PAGE;
    $pages = $count === 0 ? 0 : (int)ceil($count / max(1, $perPage));

    return new Response([
      'items' => $this->buildItems($rows, $definition),
      'meta' => [
        'count' => $count,
        'pages' => $pages,
      ],
      'filters' => $repository->getFilters($definition),
      'groups' => $repository->getGroups($definition),
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'sort_by' => Builder::string(),
      'sort_order' => Builder::string(),
      'search' => Builder::string(),
      'group' => Builder::string(),
      'filter' => Builder::object(),
    ];
  }

  abstract protected function getListingRepository(): ListingRepository;

  /**
   * @param mixed[] $rows Rows returned by {@see ListingRepository::getData()}.
   * @param ListingDefinition $definition Parsed request — exposed so subclasses
   *   can branch on filter/group when shaping items without stashing per-request
   *   state on the (shared) endpoint instance.
   * @return array<int, array<string, mixed>> Items ready to be serialized.
   */
  abstract protected function buildItems(array $rows, ListingDefinition $definition): array;

  protected function getDefaultSortBy(): string {
    return 'id';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  /**
   * Default group applied when the client does not send one. Useful when a
   * listing's repository uses groups to gate "all" vs "trash" (or similar)
   * and would otherwise return mixed results.
   */
  protected function getDefaultGroup(): ?string {
    return null;
  }

  protected function getDefaultPerPage(): int {
    return self::DEFAULT_PER_PAGE;
  }

  protected function getDefaultParameters(): array {
    return [];
  }

  /**
   * Subclasses may override to derive the listing definition's free-form
   * `params` array from the request (e.g. a `?type=standard` query arg that
   * routes to the same underlying repository). The default reuses
   * {@see getDefaultParameters()} so callers without per-request params keep
   * the existing behavior.
   *
   * @return array<string, mixed>
   */
  protected function getRequestParameters(Request $request): array {
    return $this->getDefaultParameters();
  }

  private function buildDefinition(Request $request): ListingDefinition {
    $perPageParam = $request->getParam('per_page') ?? $request->getParam('limit');
    $perPage = is_numeric($perPageParam)
      ? max(1, min(self::MAX_PER_PAGE, (int)$perPageParam))
      : $this->getDefaultPerPage();

    $pageParam = $request->getParam('page');
    $offsetParam = $request->getParam('offset');
    $offset = is_numeric($pageParam)
      ? (min(self::MAX_PAGE, max(1, (int)$pageParam)) - 1) * $perPage
      : (is_numeric($offsetParam) ? (int)$offsetParam : 0);

    $orderByParam = $request->getParam('orderby') ?? $request->getParam('sort_by');
    $sortBy = is_string($orderByParam) && $orderByParam !== '' ? $orderByParam : $this->getDefaultSortBy();

    $orderParam = $request->getParam('order') ?? $request->getParam('sort_order');
    $sortOrder = is_string($orderParam) && $orderParam !== '' ? strtolower($orderParam) : $this->getDefaultSortOrder();

    $searchParam = $request->getParam('search');
    $search = is_string($searchParam) ? $searchParam : null;

    $groupParam = $request->getParam('group');
    $group = is_string($groupParam) && $groupParam !== '' ? $groupParam : $this->getDefaultGroup();

    $filterParam = $request->getParam('filter');
    $filters = is_array($filterParam) ? $filterParam : [];

    return $this->listingHandler->getListingDefinition([
      'offset' => $offset,
      'limit' => $perPage,
      'sort_by' => $sortBy,
      'sort_order' => $sortOrder,
      'search' => $search,
      'group' => $group,
      'filter' => $filters,
      'params' => $this->getRequestParameters($request),
    ]);
  }
}
