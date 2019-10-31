<?php

namespace MailPoet\DynamicSegments;

use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\Models\DynamicSegment;
use MailPoet\WooCommerce\Helper;

class RequirementsChecker {

  /** @var Helper */
  private $woocommerce_helper;

  function __construct(Helper $woocommerce_helper = null) {
    if (!$woocommerce_helper) {
      $woocommerce_helper = new Helper();
    }
    $this->woocommerce_helper = $woocommerce_helper;
  }

  function shouldSkipSegment(DynamicSegment $segment) {
    foreach ($segment->getFilters() as $filter) {
      if ($this->shouldSkipFilter($filter)) {
        return true;
      }
    }
    return false;
  }

  private function shouldSkipFilter(Filter $filter) {
    if ($this->woocommerce_helper->isWooCommerceActive()) {
      return false;
    }

    $class_name = get_class($filter);
    $ref = new \ReflectionClass($class_name);
    $constants = $ref->getConstants();
    if (!array_key_exists('SEGMENT_TYPE', $constants)) {
      return true;
    }

    return $constants['SEGMENT_TYPE'] === 'woocommerce';
  }

}
