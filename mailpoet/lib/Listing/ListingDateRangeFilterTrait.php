<?php declare(strict_types = 1);

namespace MailPoet\Listing;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * Applies an inclusive `from`/`to` date-range filter to a listing query. Shared
 * by DataViews-backed listing repositories so the day-boundary handling stays
 * identical across listings. The `yyyy-MM-dd` days are site-local days, while
 * the columns are stored in UTC.
 */
trait ListingDateRangeFilterTrait {
  /**
   * @param array<string, mixed> $filters
   * @param string $field Fully-qualified DQL field, e.g. `f.createdAt`.
   * @param bool $siteLocalDays When false, the days are compared to the column as they are, without timezone conversion.
   */
  protected function applyDateRangeFilter(
    QueryBuilder $queryBuilder,
    string $field,
    array $filters,
    string $fromKey = 'from',
    string $toKey = 'to',
    bool $siteLocalDays = true
  ): void {
    // Derive parameter names from the filter keys so multiple ranges on the
    // same query (e.g. created + modified) don't clobber each other's bindings.
    $fromParam = 'dateRange_' . $fromKey;
    $toParam = 'dateRange_' . $toKey;

    $from = $filters[$fromKey] ?? null;
    if (is_string($from) && $from !== '') {
      $fromValue = $siteLocalDays ? $this->getSiteDayStartInUtc($from) : $from . ' 00:00:00';
      if ($fromValue !== null) {
        $queryBuilder
          ->andWhere("$field >= :$fromParam")
          ->setParameter($fromParam, $fromValue);
      }
    }
    $to = $filters[$toKey] ?? null;
    if (is_string($to) && $to !== '') {
      [$operator, $toValue] = $siteLocalDays
        ? ['<', $this->getSiteDayStartInUtc($to, 1)]
        : ['<=', $to . ' 23:59:59'];
      if ($toValue !== null) {
        $queryBuilder
          ->andWhere("$field $operator :$toParam")
          ->setParameter($toParam, $toValue);
      }
    }
  }

  /**
   * Returns the UTC `Y-m-d H:i:s` start of a site-local `yyyy-MM-dd` day, shifted by
   * `$addDays` days (1 gives the exclusive end of the day), or null for an invalid day.
   * Filter bounds use the offset in force on that date (DST aware), while the listing
   * display uses the site's current gmt_offset, so close to a DST change a row can land
   * on the neighbouring day. This is intentional.
   */
  protected function getSiteDayStartInUtc(string $day, int $addDays = 0): ?string {
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $day, WPFunctions::get()->wpTimezone());
    if ($date === false || $date->format('Y-m-d') !== $day) {
      return null;
    }
    if ($addDays !== 0) {
      $date = $date->modify(sprintf('%+d day', $addDays));
    }
    return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }
}
