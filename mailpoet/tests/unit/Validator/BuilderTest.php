<?php declare(strict_types = 1);

namespace MailPoet\Validator;

use MailPoetUnitTest;

class BuilderTest extends MailPoetUnitTest {
  public function testBuildSchemas(): void {
    $this->assertSame(['type' => 'string'], Builder::string()->toArray());
    $this->assertSame(['type' => 'number'], Builder::number()->toArray());
    $this->assertSame(['type' => 'integer'], Builder::integer()->toArray());
    $this->assertSame(['type' => 'boolean'], Builder::boolean()->toArray());
    $this->assertSame(['type' => 'null'], Builder::null()->toArray());
    $this->assertSame(['type' => 'array'], Builder::array()->toArray());
    $this->assertSame(['type' => 'object'], Builder::object()->toArray());
    $this->assertSame(['oneOf' => []], Builder::oneOf([])->toArray());
    $this->assertSame(['anyOf' => []], Builder::anyOf([])->toArray());
  }
}
