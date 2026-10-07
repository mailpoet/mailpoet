<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

use MailPoet\WP\Functions as WPFunctions;

class DotcomHelperFunctionsTest extends \MailPoetUnitTest {
  /*** @var DotcomHelperFunctions */
  private $dotcomHelper;

  /*** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->expects($this->any())
      ->method('applyFilters')
      ->willReturnCallback(function ($tag, $value) {
        return $value;
      });
    $this->dotcomHelper = new DotcomHelperFunctions($this->wp);
  }

  public function testItReturnsFalseIfNotDotcom() {
    verify($this->dotcomHelper->isDotcom())->false();
  }

  public function testItReturnsTrueIfDotcom() {
    define('IS_ATOMIC', true);
    define('ATOMIC_CLIENT_ID', '2');
    verify($this->dotcomHelper->isDotcom())->true();
  }

  public function testItReturnsEmptyStringIfNoPlan() {
    verify($this->dotcomHelper->getDotcomPlan())->equals('');
  }

  public function testItReturnsPerformanceIfWooExpressPerformance() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isWooExpressPerformance'])
      ->getMock();
    $dotcomHelper->method('isWooExpressPerformance')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('performance');
  }

  public function testItReturnsEssentialIfWooExpressEssential() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isWooExpressEssential'])
      ->getMock();
    $dotcomHelper->method('isWooExpressEssential')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('essential');
  }

  public function testItReturnsBusinessIfWooBusiness() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isBusiness'])
      ->getMock();
    $dotcomHelper->method('isBusiness')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('business');
  }

  public function testItReturnsEcommerceTrialIfEcommerceTrial() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerceTrial'])
      ->getMock();
    $dotcomHelper->method('isEcommerceTrial')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_trial');
  }

  public function testItReturnsEcommerceWPComIfEcommerceWPCom() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerceWPCom'])
      ->getMock();
    $dotcomHelper->method('isEcommerceWPCom')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce_wpcom');
  }

  public function testItReturnsEcommerceIfEcommerce() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isEcommerce'])
      ->getMock();
    $dotcomHelper->method('isEcommerce')->willReturn(true);
    verify($dotcomHelper->getDotcomPlan())->equals('ecommerce');
  }

  public function testIsDotcomReturnsFalseWhenNeitherPlatform() {
    $dotcomHelper = $this->getMockBuilder(DotcomHelperFunctions::class)
      ->setConstructorArgs([$this->wp])
      ->onlyMethods(['isAtomicPlatform'])
      ->getMock();
    $dotcomHelper->method('isAtomicPlatform')->willReturn(false);
    verify($dotcomHelper->isDotcom())->false();
  }
}
