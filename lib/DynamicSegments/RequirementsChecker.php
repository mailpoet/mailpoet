<?php

namespace MailPoet\DynamicSegments;

use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\Models\DynamicSegment;
use MailPoet\WooCommerce\Helper;

class RequirementsChecker {

  /** @var Helper */
  private $woocommerceHelper;

  public function __construct(Helper $woocommerceHelper = null) {
    if (!$woocommerceHelper) {
      $woocommerceHelper = new Helper();
    }
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function shouldSkipSegment(DynamicSegment $segment) {
    foreach ($segment->getFilters() as $filter) {
      if ($this->shouldSkipFilter($filter)) {
        return true;
      }
    }
    return false;
  }

  private function shouldSkipFilter(Filter $filter) {
    if ($this->woocommerceHelper->isWooCommerceActive()) {
      return false;
    }

    $className = get_class($filter);
    $ref = new \ReflectionClass($className);
    $constants = $ref->getConstants() ?? [];
    if (!array_key_exists('SEGMENT_TYPE', $constants)) {
      return true;
    }

    return $constants['SEGMENT_TYPE'] === 'woocommerce';
  }
}
