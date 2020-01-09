<?php

namespace MailPoet\DynamicSegments;

use MailPoet\DynamicSegments\Filters\EmailAction;
use MailPoet\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Models\DynamicSegment;
use MailPoet\WooCommerce\Helper;

class RequirementsCheckerTest extends \MailPoetUnitTest {

  /** @var Helper|\PHPUnit_Framework_MockObject_MockObject */
  private $woocommerce_helper;

  /** @var RequirementsChecker */
  private $requirements_checker;

  public function _before() {
    parent::_before();
    if (!defined('MP_SEGMENTS_TABLE')) define('MP_SEGMENTS_TABLE', '');
    $this->woocommerceHelper = $this
      ->getMockBuilder(Helper::class)
      ->setMethods(['isWooCommerceActive'])
      ->getMock();
    $this->requirementsChecker = new RequirementsChecker($this->woocommerceHelper);
  }

  public function testShouldntBlockSegmentIfWooCommerceIsActive() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $segment = DynamicSegment::create();
    $segment->setFilters([new WooCommerceCategory(1)]);
    expect($this->requirementsChecker->shouldSkipSegment($segment))->false();
  }

  public function testShouldBlockWooCommerceSegmentIfWooCommerceIsInactive() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(false);
    $segment = DynamicSegment::create();
    $segment->setFilters([new WooCommerceCategory(1)]);
    expect($this->requirementsChecker->shouldSkipSegment($segment))->true();
  }

  public function testShouldntBlockNonWooCommerceSegmentIfWooCommerceIsInactive() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(false);
    $segment = DynamicSegment::create();
    $segment->setFilters([new EmailAction(EmailAction::ACTION_OPENED, 2)]);
    expect($this->requirementsChecker->shouldSkipSegment($segment))->false();
  }

}
