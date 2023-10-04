<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

class DotcomHelperFunctionsTest extends \MailPoetUnitTest {
  /*** @var DotcomHelperFunctions */
  private $dotcomHelper;

  public function _before() {
    parent::_before();
    $this->dotcomHelper = new DotcomHelperFunctions();
  }

  public function testItReturnsFalseIfNotDotcom() {
    expect($this->dotcomHelper->isDotcom())->false();
  }

  public function testItReturnsTrueIfDotcom() {
    define('IS_ATOMIC', true);
    define('ATOMIC_CLIENT_ID', '2');
    expect($this->dotcomHelper->isDotcom())->true();
  }

  public function testItReturnsEmptyStringIfNoPlan() {
    expect($this->dotcomHelper->getDotcomPlan())->equals('');
  }

  public function testItReturnsPerformanceIfWooExpressPerformance() {
    $dotcomHelper = $this->createPartialMock(DotcomHelperFunctions::class, ['isWooExpressPerformance']);
    $dotcomHelper->method('isWooExpressPerformance')->willReturn(true);
    expect($dotcomHelper->getDotcomPlan())->equals('performance');
  }
}
