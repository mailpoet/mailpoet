<?php

namespace MailPoet\PHPStan\Extensions\PHPUnit5CompatExtension\Type;

class GetMockBuilderDynamicReturnTypeExtension extends \PHPStan\Type\PHPUnit\GetMockBuilderDynamicReturnTypeExtension {
  public function getClass(): string {
    return 'PHPUnit_Framework_TestCase';
  }
}
