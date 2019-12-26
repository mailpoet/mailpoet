<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class SubscribersCount {

  /** @var RequirementsChecker */
  private $requirements_checker;

  public function __construct(RequirementsChecker $requirements_checker = null) {
    if (!$requirements_checker) {
      $requirements_checker = new RequirementsChecker(new WooCommerceHelper());
    }
    $this->requirements_checker = $requirements_checker;
  }

  /**
   * @param DynamicSegment $dynamic_segment
   *
   * @return int
   */
  public function getSubscribersCount(DynamicSegment $dynamic_segment) {
    $orm = Subscriber::selectExpr('count(distinct ' . Subscriber::$_table . '.id) as cnt');
    if ($this->requirements_checker->shouldSkipSegment($dynamic_segment)) {
      return 0;
    }
    foreach ($dynamic_segment->getFilters() as $filter) {
      $orm = $filter->toSql($orm);
    }
    return $orm->findOne()->cnt;
  }

}
