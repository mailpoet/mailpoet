<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class MailPoetCustomFields implements Filter {
  const TYPE = 'mailpoetCustomField';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $customFieldType = $filterData->getParam('custom_field_type');
    $customFieldId = $filterData->getParam('custom_field_id');
    $parameterSuffix = (string)($filter->getId() ?? Security::generateRandomString());
    $customFieldIdParam = ':customFieldId' . $parameterSuffix;

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

    $valueParam = ':value' . $parameterSuffix;
    if (
      ($customFieldType === CustomFieldEntity::TYPE_TEXT)
      || ($customFieldType === CustomFieldEntity::TYPE_TEXTAREA)
      || ($customFieldType === CustomFieldEntity::TYPE_RADIO)
      || ($customFieldType === CustomFieldEntity::TYPE_SELECT)
    ) {
      $queryBuilder = $this->applyEquality($queryBuilder, $filter, $valueParam);
    }
    if ($customFieldType === CustomFieldEntity::TYPE_CHECKBOX) {
      $queryBuilder = $this->applyForCheckbox($queryBuilder, $filter);
    }
    if ($customFieldType === CustomFieldEntity::TYPE_DATE) {
      $queryBuilder = $this->applyForDate($queryBuilder, $filter, $valueParam);
    }
    return $queryBuilder;
  }

  private function applyForDate(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter, string $valueParam): QueryBuilder {
    $filterData = $filter->getFilterData();
    $dateType = $filterData->getParam('date_type');
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');
    $queryBuilder->setParameter($valueParam, $value);
    if ($dateType === 'month') {
      return $this->applyForDateMonth($queryBuilder, $valueParam);
    } elseif ($dateType === 'year') {
      return $this->applyForDateYear($queryBuilder, $operator, $valueParam);
    }
    return $this->applyForDateEqual($queryBuilder, $operator, $valueParam);
  }

  private function applyForDateMonth(QueryBuilder $queryBuilder, string $valueParam): QueryBuilder {
    $queryBuilder->andWhere("month(subscribers_custom_field.value) = month($valueParam)");
    return $queryBuilder;
  }

  private function applyForDateYear(QueryBuilder $queryBuilder, ?string $operator, string $valueParam): QueryBuilder {
    if ($operator === 'before') {
      $queryBuilder->andWhere("year(subscribers_custom_field.value) < year($valueParam)");
    } elseif ($operator === 'after') {
      $queryBuilder->andWhere("year(subscribers_custom_field.value) > year($valueParam)");
    } else {
      $queryBuilder->andWhere("year(subscribers_custom_field.value) = year($valueParam)");
    }
    return $queryBuilder;
  }

  private function applyForDateEqual(QueryBuilder $queryBuilder, ?string $operator, string $valueParam): QueryBuilder {
    if ($operator === 'before') {
      $queryBuilder->andWhere("subscribers_custom_field.value < $valueParam");
    } elseif ($operator === 'after') {
      $queryBuilder->andWhere("subscribers_custom_field.value > $valueParam");
    } else {
      // we always save full date in the database: 2018-03-01 00:00:00
      // so this works even for year_month where we save the first day of the month
      $queryBuilder->andWhere("subscribers_custom_field.value = $valueParam");
    }
    return $queryBuilder;
  }

  private function applyForCheckbox(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');

    if ($value === '1') {
      $queryBuilder->andWhere('subscribers_custom_field.value = 1');
    } else {
      $queryBuilder->andWhere('subscribers_custom_field.value <> 1');
    }
    return $queryBuilder;
  }

  private function applyEquality(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter, string $valueParam): QueryBuilder {
    $filterData = $filter->getFilterData();

    $operator = $filterData->getParam('operator');
    $value = $filterData->getParam('value');

    if ($operator === 'equals') {
      $queryBuilder->andWhere("subscribers_custom_field.value = $valueParam");
      $queryBuilder->setParameter($valueParam, $value);
    } else {
      $queryBuilder->andWhere("subscribers_custom_field.value LIKE $valueParam");
      $queryBuilder->setParameter($valueParam, '%' . Helpers::escapeSearch($value) . '%');
    }

    return $queryBuilder;
  }
}
