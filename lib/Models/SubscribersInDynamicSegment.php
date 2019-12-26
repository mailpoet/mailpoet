<?php

namespace MailPoet\Models;

use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class SubscribersInDynamicSegment extends Subscriber {

  public static function listingQuery(array $data = []) {
    $query = self::select(self::$_table . '.*');
    $single_segment_loader = new SingleSegmentLoader(new DBMapper());
    $dynamic_segment = $single_segment_loader->load($data['filter']['segment']);
    if (self::shouldSkip($dynamic_segment)) {
      return $query->whereRaw('0=1');
    }
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

  private static function shouldSkip($dynamic_segment) {
    $requirements_checker = new RequirementsChecker(new WooCommerceHelper());
    return $requirements_checker->shouldSkipSegment($dynamic_segment);
  }


}
