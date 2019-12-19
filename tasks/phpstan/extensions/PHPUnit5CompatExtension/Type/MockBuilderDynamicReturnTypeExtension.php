<?php

namespace MailPoet\PHPStan\Extensions\PHPUnit5CompatExtension\Type;

use PHPUnit_Framework_MockObject_MockBuilder;

class MockBuilderDynamicReturnTypeExtension extends \PHPStan\Type\PHPUnit\MockBuilderDynamicReturnTypeExtension {
  public function getClass(): string {
    return PHPUnit_Framework_MockObject_MockBuilder::class;
  }
}
