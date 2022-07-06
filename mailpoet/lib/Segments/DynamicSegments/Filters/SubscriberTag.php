<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberTag implements Filter {
  const TYPE = 'subscriberTag';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $this->wp->applyFilters('mailpoet_dynamic_segments_filter_subscriber_tag_apply', $queryBuilder, $filter);
    return $queryBuilder;
  }
}
