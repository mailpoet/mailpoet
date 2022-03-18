<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoetUnitTest;

class NullSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $null = new NullSchema();
    $this->assertSame(['type' => 'null'], $null->toArray());
    $this->assertSame('{"type":"null"}', $null->toString());
  }
}
