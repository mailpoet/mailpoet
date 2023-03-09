<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\SubscriberEntity;
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
    expect($defaultResult)->equals("SELECT id FROM $this->subscribersTable");
    expect($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals($defaultResult);
  }

  public function testItCanReturnInterpolatedSQL(): void {
    $queryBuilder = $this->getSubscribersQueryBuilder();
    $queryBuilder->where("$this->subscribersTable.created_at < :date");
    $queryBuilder->setParameter('date', '2023-03-09');
    expect($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals("SELECT id FROM $this->subscribersTable WHERE $this->subscribersTable.created_at < '2023-03-09'");
  }

  public function testItProperlyInterpolatesArrayValues(): void {
    $queryBuilder = $this->getSubscribersQueryBuilder();
    $queryBuilder->where("$this->subscribersTable.status IN (:statuses)");
    $queryBuilder->setParameter('statuses', ['subscribed', 'inactive']);
    expect($this->filterHelper->getInterpolatedSQL($queryBuilder))->equals("SELECT id FROM $this->subscribersTable WHERE $this->subscribersTable.status IN ('subscribed','inactive')");
  }

  private function getSubscribersQueryBuilder(): QueryBuilder {
    return $this->entityManager->getConnection()->createQueryBuilder()->select('id')->from($this->subscribersTable);
  }
}
