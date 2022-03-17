<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoet\Validator\Schema;
use MailPoetUnitTest;

class ArraySchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $array = new ArraySchema();
    $this->assertSame(['type' => 'array'], $array->toArray());
    $this->assertSame('{"type":"array"}', $array->toString());
  }

  public function testItems(): void {
    $array = (new ArraySchema())->items($this->getNumberSchemaMock());
    $this->assertSame(['type' => 'array', 'items' => ['type' => 'number']], $array->toArray());
    $this->assertSame('{"type":"array","items":{"type":"number"}}', $array->toString());
  }

  public function testMinItems(): void {
    $array = (new ArraySchema())->minItems(1);
    $this->assertSame(['type' => 'array', 'minItems' => 1], $array->toArray());
    $this->assertSame('{"type":"array","minItems":1}', $array->toString());
  }

  public function testMaxItems(): void {
    $array = (new ArraySchema())->maxItems(10);
    $this->assertSame(['type' => 'array', 'maxItems' => 10], $array->toArray());
    $this->assertSame('{"type":"array","maxItems":10}', $array->toString());
  }

  public function testUniqueItems(): void {
    $array = (new ArraySchema())->uniqueItems();
    $this->assertSame(['type' => 'array', 'uniqueItems' => true], $array->toArray());
    $this->assertSame('{"type":"array","uniqueItems":true}', $array->toString());
  }

  public function testMixedProperties(): void {
    $array = (new ArraySchema())
      ->items($this->getNumberSchemaMock())
      ->minItems(3)
      ->maxItems(10)
      ->uniqueItems();

    $this->assertSame([
      'type' => 'array',
      'items' => ['type' => 'number'],
      'minItems' => 3,
      'maxItems' => 10,
      'uniqueItems' => true,
    ], $array->toArray());

    $this->assertSame(
      '{"type":"array","items":{"type":"number"},"minItems":3,"maxItems":10,"uniqueItems":true}',
      $array->toString()
    );
  }

  public function testImmutability(): void {
    $array = new ArraySchema();
    $this->assertNotSame($array->items($this->getNumberSchemaMock()), $array);
    $this->assertNotSame($array->minItems(3), $array);
    $this->assertNotSame($array->maxItems(10), $array);
    $this->assertNotSame($array->uniqueItems(), $array);
  }

  private function getNumberSchemaMock(): Schema {
    return new class extends Schema {
      protected $schema = ['type' => 'number'];
    };
  }
}
