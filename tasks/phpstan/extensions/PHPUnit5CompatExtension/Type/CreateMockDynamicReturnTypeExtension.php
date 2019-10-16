<?php

namespace MailPoet\PHPStan\Extensions\PHPUnit5CompatExtension\Type;

class CreateMockDynamicReturnTypeExtension extends \PHPStan\Type\PHPUnit\CreateMockDynamicReturnTypeExtension {
  public function getClass(): string {
    return 'PHPUnit_Framework_TestCase';
  }
}
