<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FilterHelper {
  /**
   * Minimum valid year for MySQL DATE/DATETIME fields.
   * Used to clamp dates when subtracting large day values to prevent negative dates.
   */
  private const MIN_DATE_YEAR = 1000;

  /** @var EntityManager */
  private $entityManager;

  private TrackingConsentController $trackingConsentController;

  private TrackingConfig $trackingConfig;

  private ?bool $hasSentWithTrackingColumn = null;

  public function __construct(
    EntityManager $entityManager,
    TrackingConsentController $trackingConsentController,
    TrackingConfig $trackingConfig
  ) {
    $this->entityManager = $entityManager;
    $this->trackingConsentController = $trackingConsentController;
    $this->trackingConfig = $trackingConfig;
  }

  public function isOnlyTrackable(DynamicSegmentFilterData $filterData): bool {
    return $filterData->getParam(DynamicSegmentFilterData::ONLY_TRACKABLE) === true;
  }

  /**
   * For filters with the "only subscribers we can track" option, leaves out
   * subscribers whose opens and clicks we are not allowed to record today.
   */
  public function applyOnlyTrackable(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filterData): void {
    if (!$this->isOnlyTrackable($filterData)) {
      return;
    }
    $queryBuilder->andWhere($this->getTrackableConsentCondition());
  }

  /**
   * For "did not open/click this email" with the "only subscribers we can track"
   * option: keep subscribers the email went out to with tracking who we can still
   * track today. Sends from before the flag existed are all marked as tracked, so
   * the consent check is needed as well. With engagement tracking off, or before
   * the flag's column exists, only the consent check is used.
   */
  public function getOnlyTrackableSendCondition(DynamicSegmentFilterData $filterData, string $sentAlias): ?string {
    if (!$this->isOnlyTrackable($filterData)) {
      return null;
    }
    $consentCondition = $this->getTrackableConsentCondition();
    if (!$this->trackingConfig->isEmailTrackingEnabled() || !$this->hasSentWithTrackingColumn()) {
      return $consentCondition;
    }
    return "$sentAlias.sent_with_tracking = 1 AND $consentCondition";
  }

  private function hasSentWithTrackingColumn(): bool {
    if ($this->hasSentWithTrackingColumn !== null) {
      return $this->hasSentWithTrackingColumn;
    }
    global $wpdb;
    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->entityManager->getConnection()->executeQuery("SELECT sent_with_tracking FROM `$table` LIMIT 0");
      $this->hasSentWithTrackingColumn = true;
    } catch (\Throwable $e) {
      $this->hasSentWithTrackingColumn = false;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
    return $this->hasSentWithTrackingColumn;
  }

  private function getTrackableConsentCondition(): string {
    return $this->trackingConsentController->getTrackableConsentCondition($this->getSubscribersTable() . '.tracking_consent');
  }

  public function getPrefixedTable(string $table): string {
    global $wpdb;
    return sprintf('%s%s', $wpdb->prefix, $table);
  }

  public function getNewSubscribersQueryBuilder(): QueryBuilder {
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select($this->getSubscribersTable() . '.id')
      ->from($this->getSubscribersTable());
  }

  public function getSubscribersTable(): string {
    return $this->getTableForEntity(SubscriberEntity::class);
  }

  /**
   * @param class-string<object> $entityClass
   */
  public function getTableForEntity(string $entityClass): string {
    return $this->entityManager->getClassMetadata($entityClass)->getTableName();
  }

  public function getInterpolatedSQL(QueryBuilder $query): string {
    $sql = $query->getSQL();
    $params = $query->getParameters();
    $search = array_map(function($key) {
      return ":$key";
    }, array_keys($params));
    $replace = array_map(function($value) use ($query) {
      if (is_array($value)) {
        $quotedValues = array_map(function($arrayValue) use ($query) {
          return $query->expr()->literal($arrayValue);
        }, $value);
        return implode(',', $quotedValues);
      }
      return $query->expr()->literal($value);
    }, array_values($params));
    return str_replace($search, $replace, $sql);
  }

  public function getUniqueParameterName(string $parameter): string {
    $suffix = Security::generateRandomString();
    return sprintf("%s_%s", $parameter, $suffix);
  }

  public function validateDaysPeriodData(array $data): void {
    if (!isset($data['timeframe']) || !in_array($data['timeframe'], DynamicSegmentFilterData::TIMEFRAMES, true)) {
      throw new InvalidFilterException('Missing timeframe type', InvalidFilterException::MISSING_VALUE);
    }

    if ($data['timeframe'] === DynamicSegmentFilterData::TIMEFRAME_ALL_TIME) {
      return;
    }

    if ($data['timeframe'] === DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST) {
      $days = intval($data['days'] ?? null);

      if ($days < 1) {
        throw new InvalidFilterException('Missing number of days', InvalidFilterException::MISSING_VALUE);
      }
      return;
    }

    $this->getValidDateValue($data['value'] ?? null);
    if ($data['timeframe'] === DynamicSegmentFilterData::TIMEFRAME_BETWEEN) {
      $this->getValidDateValue($data['value2'] ?? null);
    }
  }

  public function getDatePeriodCondition(QueryBuilder $queryBuilder, string $dateExpression, DynamicSegmentFilterData $filterData, bool $startOfDayForInTheLast = false, string $defaultTimeframe = DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST): ?string {
    $timeframe = $filterData->getParam('timeframe') ?? $defaultTimeframe;
    if (!is_string($timeframe) || !in_array($timeframe, DynamicSegmentFilterData::TIMEFRAMES, true)) {
      throw new InvalidFilterException('Missing timeframe type', InvalidFilterException::MISSING_VALUE);
    }

    if ($timeframe === DynamicSegmentFilterData::TIMEFRAME_ALL_TIME) {
      return null;
    }

    $dateParam = $this->getUniqueParameterName('date');
    if ($timeframe === DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST) {
      $days = $filterData->getParam('days');
      if (!is_int($days) && !is_string($days)) {
        throw new InvalidFilterException('Missing number of days', InvalidFilterException::MISSING_VALUE);
      }
      $days = intval($days);
      $date = $this->getDateNDaysAgoImmutable($days);
      if ($startOfDayForInTheLast) {
        $date = $date->startOfDay();
      }
      $queryBuilder->setParameter($dateParam, $date->toDateTimeString());
      return "$dateExpression >= :$dateParam";
    }

    $startOfDay = $this->getDayBoundary($filterData, 'value');
    $startOfNextDay = $this->getNextDayBoundary($filterData, 'value');

    switch ($timeframe) {
      case DynamicSegmentFilterData::TIMEFRAME_BEFORE:
        $queryBuilder->setParameter($dateParam, $startOfDay);
        return "$dateExpression < :$dateParam";
      case DynamicSegmentFilterData::TIMEFRAME_AFTER:
        $queryBuilder->setParameter($dateParam, $startOfNextDay);
        return "$dateExpression >= :$dateParam";
      case DynamicSegmentFilterData::TIMEFRAME_ON:
        $endParam = $this->getUniqueParameterName('date');
        $queryBuilder->setParameter($dateParam, $startOfDay);
        $queryBuilder->setParameter($endParam, $startOfNextDay);
        return "$dateExpression >= :$dateParam AND $dateExpression < :$endParam";
      case DynamicSegmentFilterData::TIMEFRAME_BETWEEN:
        $endParam = $this->getUniqueParameterName('date');
        $queryBuilder->setParameter($dateParam, $startOfDay);
        $queryBuilder->setParameter($endParam, $this->getNextDayBoundary($filterData, 'value2'));
        return "$dateExpression >= :$dateParam AND $dateExpression < :$endParam";
      default:
        throw new InvalidFilterException('Missing timeframe type', InvalidFilterException::MISSING_VALUE);
    }
  }

  private function getDayBoundary(DynamicSegmentFilterData $filterData, string $paramName): string {
    return $this->parseValidDate($filterData->getParam($paramName))->startOfDay()->toDateTimeString();
  }

  private function getNextDayBoundary(DynamicSegmentFilterData $filterData, string $paramName): string {
    return $this->parseValidDate($filterData->getParam($paramName))->addDay()->startOfDay()->toDateTimeString();
  }

  public function applyDatePeriodFilter(QueryBuilder $queryBuilder, string $dateExpression, DynamicSegmentFilterData $filterData, bool $startOfDayForInTheLast = false, string $defaultTimeframe = DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST): void {
    $condition = $this->getDatePeriodCondition($queryBuilder, $dateExpression, $filterData, $startOfDayForInTheLast, $defaultTimeframe);
    if ($condition !== null) {
      $queryBuilder->andWhere($condition);
    }
  }

  /**
   * @param mixed $value
   */
  private function parseValidDate($value): CarbonImmutable {
    if (!is_string($value)) {
      throw new InvalidFilterException('Invalid date value', InvalidFilterException::INVALID_DATE_VALUE);
    }
    $date = CarbonImmutable::createFromFormat('Y-m-d', $value);
    if (!$date instanceof CarbonImmutable || $date->toDateString() !== $value) {
      throw new InvalidFilterException('Invalid date value', InvalidFilterException::INVALID_DATE_VALUE);
    }
    return $date;
  }

  /**
   * @param mixed $value
   */
  private function getValidDateValue($value): string {
    return $this->parseValidDate($value)->toDateString();
  }

  /**
   * Get a date by subtracting days from now, clamped to a minimum valid date.
   * This prevents negative dates when users set very large day values,
   * which can cause errors on some database engines like MySQL.
   *
   * @param int $days Number of days to subtract from current date
   * @return Carbon The calculated date, clamped to minimum 1000-01-01
   */
  public function getDateNDaysAgo(int $days): Carbon {
    return Carbon::now()->subDays($days)->max(Carbon::createFromDate(self::MIN_DATE_YEAR, 1, 1));
  }

  /**
   * Get an immutable date by subtracting days from now, clamped to a minimum valid date.
   * This prevents negative dates when users set very large day values,
   * which can cause errors on some database engines like MySQL.
   *
   * @param int $days Number of days to subtract from current date
   * @return CarbonImmutable The calculated date, clamped to minimum 1000-01-01
   */
  public function getDateNDaysAgoImmutable(int $days): CarbonImmutable {
    return CarbonImmutable::now()->subDays($days)->max(CarbonImmutable::createFromDate(self::MIN_DATE_YEAR, 1, 1));
  }
}
