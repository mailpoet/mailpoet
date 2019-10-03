<?php

namespace MailPoet\Premium\DynamicSegments\Persistence\Loading;

use MailPoet\Models\Subscriber;
use MailPoet\Premium\Models\DynamicSegment;

class SubscribersCount {

  /**
   * @param DynamicSegment $dynamic_segment
   *
   * @return int
   */
  function getSubscribersCount(DynamicSegment $dynamic_segment) {
    $orm = Subscriber::selectExpr('count(distinct ' . Subscriber::$_table . '.id) as cnt');
    foreach ($dynamic_segment->getFilters() as $filter) {
      $orm = $filter->toSql($orm);
    }
    return $orm->findOne()->cnt;
  }

}
