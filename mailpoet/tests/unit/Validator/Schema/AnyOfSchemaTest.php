<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoet\Validator\Schema;
use MailPoetUnitTest;

class AnyOfSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $anyOf = new AnyOfSchema([]);
    $this->assertSame(['anyOf' => []], $anyOf->toArray());
    $this->assertSame('{"anyOf":[]}', $anyOf->toString());
  }

  public function testValues(): void {
    $anyOf = new AnyOfSchema([
      $this->getNumberSchemaMock(),
      $this->getStringSchemaMock(),
    ]);
    $this->assertSame(['anyOf' => [['type' => 'number'], ['type' => 'string']]], $anyOf->toArray());
    $this->assertSame('{"anyOf":[{"type":"number"},{"type":"string"}]}', $anyOf->toString());
  }

  public function testNullable(): void {
    $anyOf = (new AnyOfSchema([]))->nullable();
    $this->assertSame(['anyOf' => [['type' => 'null']]], $anyOf->toArray());
    $this->assertSame('{"anyOf":[{"type":"null"}]}', $anyOf->toString());

    $anyOf = (new AnyOfSchema([
      $this->getNumberSchemaMock(),
      $this->getStringSchemaMock(),
    ]))->nullable();

    $this->assertSame(['anyOf' => [['type' => 'number'], ['type' => 'string'], ['type' => 'null']]], $anyOf->toArray());
    $this->assertSame('{"anyOf":[{"type":"number"},{"type":"string"},{"type":"null"}]}', $anyOf->toString());
  }

  public function testNonNullable(): void {
    $anyOf = (new AnyOfSchema([]))->nullable()->nonNullable();
    $this->assertSame(['anyOf' => []], $anyOf->toArray());
    $this->assertSame('{"anyOf":[]}', $anyOf->toString());
  }

  public function testImmutability(): void {
    $anyOf = new AnyOfSchema([]);
    $this->assertNotSame($anyOf->nullable(), $anyOf);
    $this->assertNotSame($anyOf->nonNullable(), $anyOf);
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
