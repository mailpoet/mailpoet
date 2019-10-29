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

  function _before() {
    parent::_before();
    if (!defined('MP_SEGMENTS_TABLE')) define('MP_SEGMENTS_TABLE', '');
    $this->woocommerce_helper = $this
      ->getMockBuilder(Helper::class)
      ->setMethods(['isWooCommerceActive'])
      ->getMock();
    $this->requirements_checker = new RequirementsChecker($this->woocommerce_helper);
  }

  function testShouldntBlockSegmentIfWooCommerceIsActive() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(true);
    $segment = DynamicSegment::create();
    $segment->setFilters([new WooCommerceCategory(1)]);
    expect($this->requirements_checker->shouldSkipSegment($segment))->false();
  }

  function testShouldntBlockWooCommerceSegmentIfWooCommerceIsInactive() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(false);
    $segment = DynamicSegment::create();
    $segment->setFilters([new WooCommerceCategory(1)]);
    expect($this->requirements_checker->shouldSkipSegment($segment))->true();
  }

  function testShouldntBlockNonWooCommerceSegmentIfWooCommerceIsInactive() {
    $this->woocommerce_helper->method('isWooCommerceActive')->willReturn(false);
    $segment = DynamicSegment::create();
    $segment->setFilters([new EmailAction(EmailAction::ACTION_OPENED, 2)]);
    expect($this->requirements_checker->shouldSkipSegment($segment))->false();
  }

}
