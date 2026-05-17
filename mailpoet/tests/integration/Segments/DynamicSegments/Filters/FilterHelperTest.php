<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class FilterHelperTest extends \MailPoetTest {
  /** @var FilterHelper */
  private $filterHelper;

  /** @var string */
  private $subscribersTable;

  public function _before() {
    parent::_before();
    $this->filterHelper = $this->diContainer->get(FilterHelper::class);
    $this->subscribersTable = $this->entityManager
      ->getClassMetadata(SubscriberEntity::class)
      ->getTableName();
  }

  public function testItCanReturnSQLThatDoesNotIncludeParams(): void {
    $queryBuilder = $this->getSubscribersQueryBuilder();
    $defaultResult = $queryBuilder->getSQL();
    verify($defaultResult)->equals("SELECT id FROM $this->subscribersTable");
    verify($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals($defaultResult);
  }

  public function testItCanReturnInterpolatedSQL(): void {
    $queryBuilder = $this->getSubscribersQueryBuilder();
    $queryBuilder->where("$this->subscribersTable.created_at < :date");
    $queryBuilder->setParameter('date', '2023-03-09');
    verify($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals("SELECT id FROM $this->subscribersTable WHERE $this->subscribersTable.created_at < '2023-03-09'");
  }

  public function testItProperlyInterpolatesArrayValues(): void {
    $queryBuilder = $this->getSubscribersQueryBuilder();
    $queryBuilder->where("$this->subscribersTable.status IN (:statuses)");
    $queryBuilder->setParameter('statuses', ['subscribed', 'inactive']);
    verify($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals("SELECT id FROM $this->subscribersTable WHERE $this->subscribersTable.status IN ('subscribed','inactive')");
  }

  private function getSubscribersQueryBuilder(): QueryBuilder {
    return $this->entityManager->getConnection()->createQueryBuilder()->select('id')->from($this->subscribersTable);
  }

  public function testGetDateNDaysAgoReturnsCorrectDateForNormalValue(): void {
    $days = 30;
    Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));
    $result = $this->filterHelper->getDateNDaysAgo($days);
    $expected = Carbon::now()->subDays($days);
    Carbon::setTestNow();
    verify($result->toDateString())->equals($expected->toDateString());
  }

  public function testGetDateNDaysAgoClampsToMinimumForVeryLargeDaysValue(): void {
    // A value large enough to produce a negative date (before year 0)
    $days = 999999999;
    Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));
    $result = $this->filterHelper->getDateNDaysAgo($days);
    Carbon::setTestNow();
    // Should be clamped to the minimum valid date (1000-01-01)
    verify($result->year)->greaterThanOrEqual(1000);
    verify($result->toDateString())->equals('1000-01-01');
  }

  public function testGetDateNDaysAgoImmutableReturnsCorrectDateForNormalValue(): void {
    $days = 30;
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 1, 0, 0, 0));
    $result = $this->filterHelper->getDateNDaysAgoImmutable($days);
    $expected = CarbonImmutable::now()->subDays($days);
    CarbonImmutable::setTestNow();
    verify($result->toDateString())->equals($expected->toDateString());
    verify($result)->instanceOf(CarbonImmutable::class);
  }

  public function testGetDateNDaysAgoImmutableClampsToMinimumForVeryLargeDaysValue(): void {
    // A value large enough to produce a negative date (before year 0)
    $days = 999999999;
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 1, 0, 0, 0));
    $result = $this->filterHelper->getDateNDaysAgoImmutable($days);
    CarbonImmutable::setTestNow();
    // Should be clamped to the minimum valid date (1000-01-01)
    verify($result->year)->greaterThanOrEqual(1000);
    verify($result->toDateString())->equals('1000-01-01');
    verify($result)->instanceOf(CarbonImmutable::class);
  }

  public function testGetDatePeriodConditionReturnsNullForAllTime(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_ALL_TIME,
    ]);
    verify($this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData))->null();
  }

  public function testGetDatePeriodConditionInTheLast(): void {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 5, 17, 12, 0, 0));
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
      'days' => 7,
    ]);
    $condition = $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData, true);
    CarbonImmutable::setTestNow();
    verify($condition)->stringContainsString('opens.created_at >= :date_');
    $qb->andWhere((string)$condition);
    verify($this->filterHelper->getInterpolatedSQL($qb))->stringContainsString("opens.created_at >= '2026-05-10 00:00:00'");
  }

  public function testGetDatePeriodConditionBefore(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_BEFORE,
      'value' => '2026-05-17',
    ]);
    $condition = $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
    $qb->andWhere((string)$condition);
    verify($this->filterHelper->getInterpolatedSQL($qb))->stringContainsString("opens.created_at < '2026-05-17 00:00:00'");
  }

  public function testGetDatePeriodConditionAfterUsesStartOfNextDay(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_AFTER,
      'value' => '2026-05-17',
    ]);
    $condition = $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
    $qb->andWhere((string)$condition);
    verify($this->filterHelper->getInterpolatedSQL($qb))->stringContainsString("opens.created_at >= '2026-05-18 00:00:00'");
  }

  public function testGetDatePeriodConditionOnIsHalfOpenDayRange(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_ON,
      'value' => '2026-05-17',
    ]);
    $condition = $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
    $qb->andWhere((string)$condition);
    $sql = $this->filterHelper->getInterpolatedSQL($qb);
    verify($sql)->stringContainsString("opens.created_at >= '2026-05-17 00:00:00'");
    verify($sql)->stringContainsString("opens.created_at < '2026-05-18 00:00:00'");
  }

  public function testGetDatePeriodConditionBetweenIsInclusiveOfBothDays(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_BETWEEN,
      'value' => '2026-05-10',
      'value2' => '2026-05-17',
    ]);
    $condition = $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
    $qb->andWhere((string)$condition);
    $sql = $this->filterHelper->getInterpolatedSQL($qb);
    verify($sql)->stringContainsString("opens.created_at >= '2026-05-10 00:00:00'");
    verify($sql)->stringContainsString("opens.created_at < '2026-05-18 00:00:00'");
  }

  public function testGetDatePeriodConditionUsesDefaultTimeframeWhenMissing(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('woocommerce', 'product', []);
    verify($this->filterHelper->getDatePeriodCondition($qb, 'orderStats.date_created', $filterData, false, DynamicSegmentFilterData::TIMEFRAME_ALL_TIME))->null();
  }

  public function testGetDatePeriodConditionThrowsOnInvalidTimeframe(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => 'nonsense',
    ]);
    $this->expectException(InvalidFilterException::class);
    $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
  }

  public function testGetDatePeriodConditionThrowsWhenInTheLastMissesDays(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST,
    ]);
    $this->expectException(InvalidFilterException::class);
    $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
  }

  public function testGetDatePeriodConditionThrowsOnMissingValueForAbsoluteTimeframe(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_BEFORE,
    ]);
    $this->expectException(InvalidFilterException::class);
    $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
  }

  public function testGetDatePeriodConditionThrowsOnMalformedDate(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_ON,
      'value' => '2026-5-1',
    ]);
    $this->expectException(InvalidFilterException::class);
    $this->filterHelper->getDatePeriodCondition($qb, 'opens.created_at', $filterData);
  }

  public function testApplyDatePeriodFilterAppendsWhereForBetween(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('email', 'opened', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_BETWEEN,
      'value' => '2026-05-10',
      'value2' => '2026-05-17',
    ]);
    $this->filterHelper->applyDatePeriodFilter($qb, 'opens.created_at', $filterData);
    verify($qb->getSQL())->stringContainsString('WHERE');
  }

  public function testApplyDatePeriodFilterIsNoOpForAllTime(): void {
    $qb = $this->getSubscribersQueryBuilder();
    $filterData = new DynamicSegmentFilterData('woocommerce', 'product', [
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_ALL_TIME,
    ]);
    $before = $qb->getSQL();
    $this->filterHelper->applyDatePeriodFilter($qb, 'orderStats.date_created', $filterData);
    verify($qb->getSQL())->equals($before);
  }

  public function testValidateDaysPeriodDataAcceptsAllNewTimeframes(): void {
    foreach (DynamicSegmentFilterData::TIMEFRAMES as $timeframe) {
      $data = ['timeframe' => $timeframe];
      if ($timeframe === DynamicSegmentFilterData::TIMEFRAME_IN_THE_LAST) {
        $data['days'] = 7;
      } elseif (in_array($timeframe, [DynamicSegmentFilterData::TIMEFRAME_BEFORE, DynamicSegmentFilterData::TIMEFRAME_AFTER, DynamicSegmentFilterData::TIMEFRAME_ON], true)) {
        $data['value'] = '2026-05-17';
      } elseif ($timeframe === DynamicSegmentFilterData::TIMEFRAME_BETWEEN) {
        $data['value'] = '2026-05-10';
        $data['value2'] = '2026-05-17';
      }
      // Should not throw.
      $this->filterHelper->validateDaysPeriodData($data);
    }
    verify(true)->true();
  }

  public function testValidateDaysPeriodDataRejectsBetweenMissingValue2(): void {
    $this->expectException(InvalidFilterException::class);
    $this->filterHelper->validateDaysPeriodData([
      'timeframe' => DynamicSegmentFilterData::TIMEFRAME_BETWEEN,
      'value' => '2026-05-17',
    ]);
  }
}
