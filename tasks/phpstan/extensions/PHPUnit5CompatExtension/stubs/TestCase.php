<?php

class PHPUnit_Framework_TestCase { // phpcs:ignore
  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  public function createStub($originalClassName) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  public function createMock($originalClassName) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $className
   * @phpstan-return PHPUnit_Framework_MockObject_MockBuilder<T>
   */
  public function getMockBuilder(string $className) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  public function createConfiguredMock($originalClassName) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-param string[] $methods
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  public function createPartialMock($originalClassName, array $methods) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  public function createTestProxy($originalClassName) {
  }

  /**
   * @template T
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-param string $mockClassName
   * @phpstan-param bool $callOriginalConstructor
   * @phpstan-param bool $callOriginalClone
   * @phpstan-param bool $callAutoload
   * @phpstan-param string[] $mockedMethods
   * @phpstan-param bool $cloneArguments
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  protected function getMockForAbstractClass($originalClassName, array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $mockedMethods = [], $cloneArguments = false) {
  }

  /**
   * @template T
   * @phpstan-param string $wsdlFile
   * @phpstan-param class-string<T> $originalClassName
   * @phpstan-param string $mockClassName
   * @phpstan-param bool $callOriginalConstructor
   * @phpstan-param array $options
   * @phpstan-return PHPUnit_Framework_MockObject_MockObject&T
   */
  protected function getMockFromWsdl($wsdlFile, $originalClassName = '', $mockClassName = '', array $methods = [], $callOriginalConstructor = true, array $options = []) {
  }
}
