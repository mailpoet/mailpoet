<?php declare(strict_types = 1);

namespace MailPoet\Entities;

class WpPostEntityTest extends \MailPoetTest {
  public function testItIsReadOnlyAndCanNotBeInstantiated() {
    $this->expectException(\MailPoet\RuntimeException::class);
    $this->expectExceptionMessage('WpPostEntity is read only and cannot be instantiated.');
    new WpPostEntity();
  }
}
