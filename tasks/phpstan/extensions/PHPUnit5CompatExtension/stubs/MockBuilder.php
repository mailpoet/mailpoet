<?php

/**
 * @template TMockedClass
 */
class PHPUnit_Framework_MockObject_MockBuilder { // phpcs:ignore

  /**
   * @phpstan-param PHPUnit_Framework_TestCase $testCase
   * @phpstan-param class-string<TMockedClass> $type
   */
  public function __construct(PHPUnit_Framework_TestCase $testCase, $type) {
  }

  /**
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&TMockedClass
   */
  public function getMock() {
  }

  /**
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&TMockedClass
   */
  public function getMockForAbstractClass() {
  }
}
