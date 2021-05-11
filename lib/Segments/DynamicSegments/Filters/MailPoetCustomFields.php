<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class MailPoetCustomFields implements Filter {
  const TYPE = 'mailpoetCustomField';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $customFieldType = $filterData->getParam('customFieldType');
    $customFieldId = $filterData->getParam('customFieldId');
    $customFieldIdParam = ':customFieldId' . $filter->getId();

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersCustomFieldTable = $this->entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();

    $queryBuilder->innerJoin(
      $subscribersTable,
      $subscribersCustomFieldTable,
      'subscribers_custom_field',
      "$subscribersTable.id = subscribers_custom_field.subscriber_id"
    );
    $queryBuilder->andWhere("subscribers_custom_field.custom_field_id = $customFieldIdParam");
    $queryBuilder->setParameter($customFieldIdParam, $customFieldId);

    if (
      ($customFieldType === CustomFieldEntity::TYPE_TEXT)
      || ($customFieldType === CustomFieldEntity::TYPE_TEXTAREA)
      || ($customFieldType === CustomFieldEntity::TYPE_RADIO)
      || ($customFieldType === CustomFieldEntity::TYPE_SELECT)
    ) {
      $queryBuilder = $this->applyEquality($queryBuilder, $filter);
    }
    if ($customFieldType === CustomFieldEntity::TYPE_CHECKBOX) {
      $queryBuilder = $this->applyForCheckbox($queryBuilder, $filter);
    }
    return $queryBuilder;
  }

  private function applyForCheckbox(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');

    if ($value === '1') {
      $queryBuilder->andWhere("subscribers_custom_field.value = 1");
    } else {
      $queryBuilder->andWhere("subscribers_custom_field.value <> 1");
    }
    return $queryBuilder;
  }

  private function applyEquality(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();

    $operator = $filterData->getParam('operator');
    $value = $filterData->getParam('value');
    $valueParam = ':value' . $filter->getId();

    if ($operator === 'equals') {
      $queryBuilder->andWhere("subscribers_custom_field.value = $valueParam");
      $queryBuilder->setParameter($valueParam, $value);
    } else {
      $queryBuilder->andWhere("subscribers_custom_field.value LIKE $valueParam");
      $queryBuilder->setParameter($valueParam, '%' . Helpers::escapeSearch($value)) . '%';
    }

    return $queryBuilder;
  }

}
