<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class UserRole implements Filter {
  const TYPE = 'wordpressRole';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();
    $role = $filterData->getParam('wordpressRole');
    if (!$role) {
      throw new InvalidFilterException('Missing role', InvalidFilterException::MISSING_ROLE);
    }

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    return $queryBuilder->join($subscribersTable, $wpdb->users, 'wpusers', "$subscribersTable.wp_user_id = wpusers.id")
      ->join('wpusers', $wpdb->usermeta, 'wpusermeta', 'wpusers.id = wpusermeta.user_id')
      ->andWhere("wpusermeta.meta_key = '{$wpdb->prefix}capabilities' AND wpusermeta.meta_value LIKE :role" . $parameterSuffix)
      ->setParameter(':role' . $parameterSuffix, '%"' . $role . '"%');
  }
}
