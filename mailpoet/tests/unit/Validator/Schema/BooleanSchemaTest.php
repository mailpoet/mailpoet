<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoetUnitTest;

class BooleanSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $boolean = new BooleanSchema();
    $this->assertSame(['type' => 'boolean'], $boolean->toArray());
    $this->assertSame('{"type":"boolean"}', $boolean->toString());
  }
}
