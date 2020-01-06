<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class SubscribersIds {

  /** @var RequirementsChecker */
  private $requirementsChecker;

  public function __construct(RequirementsChecker $requirementsChecker = null) {
    if (!$requirementsChecker) {
      $requirementsChecker = new RequirementsChecker(new WooCommerceHelper());
    }
    $this->requirementsChecker = $requirementsChecker;
  }

  /**
   * Finds subscribers in a dynamic segment and returns their ids.
   *
   * @param DynamicSegment $dynamicSegment
   * @param array $limitToSubscribersIds If passed the result will be limited only to ids within this array
   *
   * @return Subscriber[]
   */
  public function load(DynamicSegment $dynamicSegment, $limitToSubscribersIds = null) {
    $orm = Subscriber::selectExpr(Subscriber::$_table . '.id');
    if ($this->requirementsChecker->shouldSkipSegment($dynamicSegment)) {
      return [];
    }
    foreach ($dynamicSegment->getFilters() as $filter) {
      $orm = $filter->toSql($orm);
    }
    if ($limitToSubscribersIds) {
      $orm->whereIn(Subscriber::$_table . '.id', $limitToSubscribersIds);
    }
    return $orm->findMany();
  }

}
