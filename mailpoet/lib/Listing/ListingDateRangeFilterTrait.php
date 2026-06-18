<?php declare(strict_types = 1);

namespace MailPoet\Listing;

use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * Applies an inclusive `from`/`to` date-range filter to a listing query. Shared
 * by DataViews-backed listing repositories so the day-boundary handling (whole
 * days, `00:00:00`–`23:59:59`) stays identical across listings.
 */
trait ListingDateRangeFilterTrait {
  /**
   * @param array<string, mixed> $filters
   * @param string $field Fully-qualified DQL field, e.g. `f.createdAt`.
   */
  protected function applyDateRangeFilter(
    QueryBuilder $queryBuilder,
    string $field,
    array $filters,
    string $fromKey = 'from',
    string $toKey = 'to'
  ): void {
    // Derive parameter names from the filter keys so multiple ranges on the
    // same query (e.g. created + modified) don't clobber each other's bindings.
    $fromParam = 'dateRange_' . $fromKey;
    $toParam = 'dateRange_' . $toKey;

    $from = $filters[$fromKey] ?? null;
    if (is_string($from) && $from !== '') {
      $queryBuilder
        ->andWhere("$field >= :$fromParam")
        ->setParameter($fromParam, $from . ' 00:00:00');
    }
    $to = $filters[$toKey] ?? null;
    if (is_string($to) && $to !== '') {
      $queryBuilder
        ->andWhere("$field <= :$toParam")
        ->setParameter($toParam, $to . ' 23:59:59');
    }
  }
}
