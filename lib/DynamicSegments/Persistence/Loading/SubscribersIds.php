<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;

class SubscribersIds {

  /**
   * Finds subscribers in a dynamic segment and returns their ids.
   *
   * @param DynamicSegment $dynamic_segment
   * @param array $limit_to_subscribers_ids If passed the result will be limited only to ids within this array
   *
   * @return Subscriber[]
   */
  function load(DynamicSegment $dynamic_segment, $limit_to_subscribers_ids = null) {
    $orm = Subscriber::selectExpr(Subscriber::$_table . '.id');
    foreach ($dynamic_segment->getFilters() as $filter) {
      $orm = $filter->toSql($orm);
    }
    if ($limit_to_subscribers_ids) {
      $orm->whereIn(Subscriber::$_table . '.id', $limit_to_subscribers_ids);
    }
    return $orm->findMany();
  }

}
