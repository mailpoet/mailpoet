<?php

namespace MailPoet\Models;

use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\Models\Subscriber;

class SubscribersInDynamicSegment extends Subscriber {

  static function listingQuery(array $data = []) {
    $query = self::select(self::$_table . '.*');
    $single_segment_loader = new SingleSegmentLoader(new DBMapper());
    $dynamic_segment = $single_segment_loader->load($data['filter']['segment']);
    foreach ($dynamic_segment->getFilters() as $filter) {
      $query = $filter->toSql($query);
    }
    if (isset($data['group'])) {
      $query->filter('groupBy', $data['group']);
    }
    if (isset($data['search']) && $data['search']) {
      $query->filter('search', $data['search']);
    }
    return $query;
  }

}
