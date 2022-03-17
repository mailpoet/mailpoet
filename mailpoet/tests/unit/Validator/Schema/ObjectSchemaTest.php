<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoet\Validator\Schema;
use MailPoetUnitTest;

class ObjectSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $object = new ObjectSchema();
    $this->assertSame(['type' => 'object'], $object->toArray());
    $this->assertSame('{"type":"object"}', $object->toString());
  }

  public function testProperties(): void {
    $object = (new ObjectSchema())->properties([
      'one' => $this->getNumberSchemaMock(),
      'two' => $this->getStringSchemaMock(),
    ]);

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'one' => ['type' => 'number'],
        'two' => ['type' => 'string'],
      ],
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","properties":{"one":{"type":"number"},"two":{"type":"string"}}}',
      $object->toString()
    );
  }

  public function testRequiredProperties(): void {
    $object = (new ObjectSchema())->properties([
      'one' => $this->getNumberSchemaMock()->required(),
      'two' => $this->getStringSchemaMock()->optional(),
    ]);

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'one' => ['type' => 'number', 'required' => true],
        'two' => ['type' => 'string'],
      ],
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","properties":{"one":{"type":"number","required":true},"two":{"type":"string"}}}',
      $object->toString()
    );
  }

  public function testAdditionalProperties(): void {
    // enable additional properties (default)
    $object = new ObjectSchema();

    $this->assertSame(['type' => 'object'], $object->toArray());
    $this->assertSame('{"type":"object"}', $object->toString());

    // define type of additional properties
    $object = (new ObjectSchema())->additionalProperties($this->getNumberSchemaMock());

    $this->assertSame([
      'type' => 'object',
      'additionalProperties' => ['type' => 'number'],
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","additionalProperties":{"type":"number"}}',
      $object->toString()
    );

    // disable additional properties
    $object = (new ObjectSchema())->disableAdditionalProperties();

    $this->assertSame([
      'type' => 'object',
      'additionalProperties' => false,
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","additionalProperties":false}',
      $object->toString()
    );
  }

  public function testPatternProperties(): void {
    $object = (new ObjectSchema())->patternProperties([
      '^number-[0-9]+' => $this->getNumberSchemaMock(),
      '^string-[0-9]+' => $this->getStringSchemaMock(),
    ]);

    $this->assertSame([
      'type' => 'object',
      'patternProperties' => [
        '^number-[0-9]+' => ['type' => 'number'],
        '^string-[0-9]+' => ['type' => 'string'],
      ],
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","patternProperties":{"^number-[0-9]+":{"type":"number"},"^string-[0-9]+":{"type":"string"}}}',
      $object->toString()
    );
  }

  public function testMinProperties(): void {
    $object = (new ObjectSchema())->minProperties(1);
    $this->assertSame(['type' => 'object', 'minProperties' => 1], $object->toArray());
    $this->assertSame('{"type":"object","minProperties":1}', $object->toString());
  }

  public function testMaxProperties(): void {
    $object = (new ObjectSchema())->maxProperties(10);
    $this->assertSame(['type' => 'object', 'maxProperties' => 10], $object->toArray());
    $this->assertSame('{"type":"object","maxProperties":10}', $object->toString());
  }

  public function testMixedProperties(): void {
    $object = (new ObjectSchema())
      ->properties([
        'one' => $this->getNumberSchemaMock()->required(),
        'two' => $this->getStringSchemaMock()->optional(),
      ])
      ->additionalProperties($this->getNumberSchemaMock())
      ->patternProperties([
        '^number-[0-9]+' => $this->getNumberSchemaMock(),
        '^string-[0-9]+' => $this->getStringSchemaMock(),
      ])
      ->minProperties(2)
      ->maxProperties(10);

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'one' => ['type' => 'number', 'required' => true],
        'two' => ['type' => 'string'],
      ],
      'additionalProperties' => ['type' => 'number'],
      'patternProperties' => [
        '^number-[0-9]+' => ['type' => 'number'],
        '^string-[0-9]+' => ['type' => 'string'],
      ],
      'minProperties' => 2,
      'maxProperties' => 10,
    ], $object->toArray());

    $this->assertSame(
      '{"type":"object","properties":{"one":{"type":"number","required":true},"two":{"type":"string"}},"additionalProperties":{"type":"number"},"patternProperties":{"^number-[0-9]+":{"type":"number"},"^string-[0-9]+":{"type":"string"}},"minProperties":2,"maxProperties":10}',
      $object->toString()
    );
  }

  public function testImmutability(): void {
    $object = new ObjectSchema();
    $this->assertNotSame($object->properties(['one' => $this->getNumberSchemaMock()]), $object);
    $this->assertNotSame($object->additionalProperties($this->getNumberSchemaMock()), $object);
    $this->assertNotSame($object->disableAdditionalProperties(), $object);
    $this->assertNotSame($object->patternProperties(['.+' => $this->getStringSchemaMock()]), $object);
    $this->assertNotSame($object->minProperties(2), $object);
    $this->assertNotSame($object->maxProperties(10), $object);
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
