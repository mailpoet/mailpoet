<?php

namespace MailPoet\Premium\Models;

use MailPoet\Models\Subscriber;
use MailPoet\Premium\DynamicSegments\Mappers\DBMapper;
use MailPoet\Premium\DynamicSegments\Persistence\Loading\SingleSegmentLoader;

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
