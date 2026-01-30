<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\SubscriberEntity;
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
}
