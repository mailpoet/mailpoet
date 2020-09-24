<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class SubscribersCount {

  /** @var RequirementsChecker */
  private $requirementsChecker;

  public function __construct(RequirementsChecker $requirementsChecker = null) {
    if (!$requirementsChecker) {
      $requirementsChecker = new RequirementsChecker(new WooCommerceHelper());
    }
    $this->requirementsChecker = $requirementsChecker;
  }

  /**
   * @param DynamicSegment $dynamicSegment
   *
   * @return int
   */
  public function getSubscribersCount(DynamicSegment $dynamicSegment) {
    $orm = Subscriber::selectExpr('count(distinct ' . Subscriber::$_table . '.id) as cnt');
    if ($this->requirementsChecker->shouldSkipSegment($dynamicSegment)) {
      return 0;
    }
    foreach ($dynamicSegment->getFilters() as $filter) {
      $orm = $filter->toSql($orm);
    }
    $orm->where(MP_SUBSCRIBERS_TABLE . '.status', Subscriber::STATUS_SUBSCRIBED);
    return $orm->findOne()->cnt;
  }
}
