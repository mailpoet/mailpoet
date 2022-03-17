<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoet\Validator\Schema;
use MailPoetUnitTest;

class OneOfSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $oneOf = new OneOfSchema([]);
    $this->assertSame(['oneOf' => []], $oneOf->toArray());
    $this->assertSame('{"oneOf":[]}', $oneOf->toString());
  }

  public function testValues(): void {
    $oneOf = new OneOfSchema([
      $this->getNumberSchemaMock(),
      $this->getStringSchemaMock(),
    ]);
    $this->assertSame(['oneOf' => [['type' => 'number'], ['type' => 'string']]], $oneOf->toArray());
    $this->assertSame('{"oneOf":[{"type":"number"},{"type":"string"}]}', $oneOf->toString());
  }

  public function testNullable(): void {
    $oneOf = (new OneOfSchema([]))->nullable();
    $this->assertSame(['oneOf' => [['type' => 'null']]], $oneOf->toArray());
    $this->assertSame('{"oneOf":[{"type":"null"}]}', $oneOf->toString());

    $oneOf = (new OneOfSchema([
      $this->getNumberSchemaMock(),
      $this->getStringSchemaMock(),
    ]))->nullable();

    $this->assertSame(['oneOf' => [['type' => 'number'], ['type' => 'string'], ['type' => 'null']]], $oneOf->toArray());
    $this->assertSame('{"oneOf":[{"type":"number"},{"type":"string"},{"type":"null"}]}', $oneOf->toString());
  }

  public function testNonNullable(): void {
    $oneOf = (new OneOfSchema([]))->nullable()->nonNullable();
    $this->assertSame(['oneOf' => []], $oneOf->toArray());
    $this->assertSame('{"oneOf":[]}', $oneOf->toString());
  }

  public function testImmutability(): void {
    $oneOf = new OneOfSchema([]);
    $this->assertNotSame($oneOf->nullable(), $oneOf);
    $this->assertNotSame($oneOf->nonNullable(), $oneOf);
  }

  private function getNumberSchemaMock(): Schema {
    return new class extends Schema {
      protected $schema = ['type' => 'number'];
    };
  }

  private function getStringSchemaMock(): Schema {
    return new class extends Schema {
      protected $schema = ['type' => 'string'];
    };
  }
}
