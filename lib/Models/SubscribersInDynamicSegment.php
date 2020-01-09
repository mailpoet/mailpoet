<?php

namespace MailPoet\Models;

use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class SubscribersInDynamicSegment extends Subscriber {

  public static function listingQuery(array $data = []) {
    $query = self::select(self::$_table . '.*');
    $singleSegmentLoader = new SingleSegmentLoader(new DBMapper());
    $dynamicSegment = $singleSegmentLoader->load($data['filter']['segment']);
    if (self::shouldSkip($dynamicSegment)) {
      return $query->whereRaw('0=1');
    }
    foreach ($dynamicSegment->getFilters() as $filter) {
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

  private static function shouldSkip($dynamicSegment) {
    $requirementsChecker = new RequirementsChecker(new WooCommerceHelper());
    return $requirementsChecker->shouldSkipSegment($dynamicSegment);
  }


}
