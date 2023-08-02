<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Migrator\AppMigration;

class Migration_20230802_174831_App extends AppMigration {
  public function run(): void {
    $subscribedDateFilters = $this->entityManager->createQueryBuilder()
      ->select('dsf')
      ->from(DynamicSegmentFilterEntity::class, 'dsf')
      ->where('dsf.filterData.filterType = :filterType')
      ->andWhere('dsf.filterData.action = :action')
      ->setParameter('filterType', 'userRole')
      ->setParameter('action', 'subscribedDate')
      ->getQuery()
      ->getResult();

    /** @var DynamicSegmentFilterEntity $subscribedDateFilter */
    foreach ($subscribedDateFilters as $subscribedDateFilter) {
      $filterData = $subscribedDateFilter->getFilterData();
      $data = $filterData->getData();
      if (isset($data['action']) && $data['action'] === 'subscribedDate') {
        continue;
      }
      $data['action'] = 'subscribedDate';
      if (!is_string($filterData->getFilterType()) || !is_string($filterData->getAction())) {
        continue;
      }
      $newFilterData = new DynamicSegmentFilterData(
        $filterData->getFilterType(),
        $filterData->getAction(),
        $data
      );
      $subscribedDateFilter->setFilterData($newFilterData);
      $this->entityManager->persist($subscribedDateFilter);
    }

    $this->entityManager->flush();
  }
}
