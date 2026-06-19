<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Tags\RestApi\TagApiException;
use MailPoet\Tags\TagRepository;
use MailPoet\Validator\Builder;

class TagsGetEndpoint extends TagsEndpoint {
  use ListingRequestValidationTrait;

  // Bounds the shared validation trait relies on. Kept in sync with
  // AbstractListingEndpoint so listing validation behaves the same everywhere.
  public const MAX_PER_PAGE = 100;
  public const MAX_PAGE = 100000;

  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    TagRepository $tagRepository
  ) {
    $this->tagRepository = $tagRepository;
  }

  public function handle(Request $request): Response {
    $search = is_string($request->getParam('search')) ? (string)$request->getParam('search') : '';
    $orderby = is_string($request->getParam('orderby')) ? (string)$request->getParam('orderby') : 'name';
    $order = is_string($request->getParam('order')) ? (string)$request->getParam('order') : 'asc';
    $page = is_numeric($request->getParam('page')) ? max(1, (int)$request->getParam('page')) : 1;
    $perPage = is_numeric($request->getParam('per_page')) ? max(1, min(100, (int)$request->getParam('per_page'))) : 25;
    $buckets = $this->tagRepository->getSubscriberCountBuckets();
    $filter = $this->parseFilter($request->getParam('filter'), $buckets);

    $result = $this->tagRepository->listWithCounts([
      'search' => $search,
      'orderby' => $orderby,
      'order' => $order,
      'page' => $page,
      'per_page' => $perPage,
      'filter' => $filter,
    ]);

    $items = array_map([$this, 'buildItemFromRow'], $result['items']);
    $pages = $result['total'] === 0 ? 0 : (int)ceil($result['total'] / max(1, $perPage));

    return new Response([
      'items' => $items,
      'meta' => [
        'count' => $result['total'],
        'pages' => $pages,
        'subscriber_count_buckets' => $buckets,
      ],
    ]);
  }

  /**
   * @param mixed $rawFilter
   * @param array<int, array{value: string, min: int, max: ?int}> $buckets
   * @return array{from?: string, to?: string, subscriber_ranges?: array<int, array{min: int, max: ?int}>}
   */
  private function parseFilter($rawFilter, array $buckets): array {
    if ($rawFilter === null || $rawFilter === '' || $rawFilter === []) {
      return [];
    }
    if (!is_array($rawFilter)) {
      throw new TagApiException(
        __('Filters must be an object.', 'mailpoet'),
        400,
        'mailpoet_tags_invalid_filter'
      );
    }

    $allowed = ['from', 'to', 'subscribers'];
    $normalized = [];
    foreach ($rawFilter as $key => $value) {
      if (!is_string($key) || !in_array($key, $allowed, true)) {
        throw new TagApiException(
          __('Unsupported tags filter.', 'mailpoet'),
          400,
          'mailpoet_tags_invalid_filter'
        );
      }
      $normalized[$key] = $value;
    }

    $filter = [];
    $from = $this->validateDateFilter($normalized, 'from');
    $to = $this->validateDateFilter($normalized, 'to');
    $this->validateDateRange($from, $to);
    if ($from !== null) {
      $filter['from'] = $from->format('Y-m-d');
    }
    if ($to !== null) {
      $filter['to'] = $to->format('Y-m-d');
    }
    if (array_key_exists('subscribers', $normalized)) {
      $ranges = $this->parseSubscriberRanges($normalized['subscribers'], $buckets);
      if ($ranges) {
        $filter['subscriber_ranges'] = $ranges;
      }
    }

    return $filter;
  }

  /**
   * Resolve the selected bucket values into `{min, max}` ranges.
   *
   * Buckets are data-driven and change as the site's subscriber counts change,
   * so a bookmarked or stale value may no longer match a current bucket. Unknown
   * values are ignored rather than rejected, so a deep-linked listing still
   * loads (e.g. `subscribers=0` on a site where no tag has subscribers and there
   * are no buckets at all) instead of failing with a 400.
   *
   * @param mixed $rawValue
   * @param array<int, array{value: string, min: int, max: ?int}> $buckets
   * @return array<int, array{min: int, max: ?int}>
   */
  private function parseSubscriberRanges($rawValue, array $buckets): array {
    if ($rawValue === '' || $rawValue === null || $rawValue === []) {
      return [];
    }
    $values = is_array($rawValue) ? $rawValue : [$rawValue];

    $bucketsByValue = [];
    foreach ($buckets as $bucket) {
      $bucketsByValue[$bucket['value']] = $bucket;
    }

    $ranges = [];
    $seen = [];
    foreach ($values as $value) {
      $key = is_scalar($value) ? (string)$value : '';
      if (!isset($bucketsByValue[$key]) || isset($seen[$key])) {
        continue;
      }
      $seen[$key] = true;
      $ranges[] = ['min' => $bucketsByValue[$key]['min'], 'max' => $bucketsByValue[$key]['max']];
    }
    return $ranges;
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'tags';
  }

  public static function getRequestSchema(): array {
    return [
      'search' => Builder::string(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'filter' => Builder::object(),
    ];
  }
}
