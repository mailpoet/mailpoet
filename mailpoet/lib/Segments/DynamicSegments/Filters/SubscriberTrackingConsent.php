<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

/**
 * Filters on the tracking-consent state stored on the subscriber.
 *
 * This reads the stored value and nothing else. It deliberately does not ask
 * TrackingConsentController whether the subscriber can be tracked right now,
 * because that answer depends on site settings — a segment built on it would
 * change who is in it when someone edits an unrelated settings page.
 */
class SubscriberTrackingConsent implements Filter {
  const TYPE = 'trackingConsent';

  const VALID_OPERATORS = [
    DynamicSegmentFilterData::OPERATOR_IS,
    DynamicSegmentFilterData::OPERATOR_IS_NOT,
  ];

  const VALID_VALUES = [
    SubscriberEntity::TRACKING_CONSENT_GRANTED,
    SubscriberEntity::TRACKING_CONSENT_DENIED,
    SubscriberEntity::TRACKING_CONSENT_UNKNOWN,
  ];

  private FilterHelper $filterHelper;

  public function __construct(
    FilterHelper $filterHelper
  ) {
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');

    if (!in_array($value, self::VALID_VALUES, true)) {
      throw new InvalidFilterException('Invalid tracking consent value', InvalidFilterException::MISSING_VALUE);
    }
    if (!in_array($operator, self::VALID_OPERATORS, true)) {
      throw new InvalidFilterException('Invalid operator', InvalidFilterException::MISSING_OPERATOR);
    }

    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $parameter = $this->filterHelper->getUniqueParameterName('trackingConsent');
    // The column is NOT NULL DEFAULT 'unknown', so "is not" needs no NULL handling
    // and rows written before the consent migration read as 'unknown'.
    $comparison = $operator === DynamicSegmentFilterData::OPERATOR_IS ? '=' : '!=';

    $queryBuilder->andWhere("$subscribersTable.tracking_consent $comparison :$parameter");
    $queryBuilder->setParameter($parameter, $value);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
