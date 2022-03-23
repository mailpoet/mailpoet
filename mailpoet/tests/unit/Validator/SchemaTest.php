<?php declare(strict_types = 1);

namespace MailPoet\Validator;

use MailPoet\InvalidStateException;
use MailPoetUnitTest;

class SchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $schema = $this->getTestingSchema();
    $this->assertSame(['type' => 'test'], $schema->toArray());
    $this->assertSame('{"type":"test"}', $schema->toString());
  }

  public function testNullable(): void {
    $schema = $this->getTestingSchema()->nullable();
    $this->assertSame(['type' => ['test', 'null']], $schema->toArray());
    $this->assertSame('{"type":["test","null"]}', $schema->toString());
  }

  public function testNonNullable(): void {
    $schema = $this->getTestingSchema()->nullable()->nonNullable();
    $this->assertSame(['type' => 'test'], $schema->toArray());
    $this->assertSame('{"type":"test"}', $schema->toString());
  }

  public function testRequired(): void {
    $schema = $this->getTestingSchema()->required();
    $this->assertSame(['type' => 'test', 'required' => true], $schema->toArray());
    $this->assertSame('{"type":"test","required":true}', $schema->toString());
  }

  public function testOptional(): void {
    $schema = $this->getTestingSchema()->required()->optional();
    $this->assertSame(['type' => 'test'], $schema->toArray());
    $this->assertSame('{"type":"test"}', $schema->toString());
  }

  public function testTitle(): void {
    $schema = $this->getTestingSchema()->title('Schema title');
    $this->assertSame(['type' => 'test', 'title' => 'Schema title'], $schema->toArray());
    $this->assertSame('{"type":"test","title":"Schema title"}', $schema->toString());
  }

  public function testDescription(): void {
    $schema = $this->getTestingSchema()->description('Schema description');
    $this->assertSame(['type' => 'test', 'description' => 'Schema description'], $schema->toArray());
    $this->assertSame('{"type":"test","description":"Schema description"}', $schema->toString());
  }

  public function testDefault(): void {
    $schema = $this->getTestingSchema()->default('Default value');
    $this->assertSame(['type' => 'test', 'default' => 'Default value'], $schema->toArray());
    $this->assertSame('{"type":"test","default":"Default value"}', $schema->toString());

    $schema = $this->getTestingSchema()->default(null);
    $this->assertSame(['type' => 'test', 'default' => null], $schema->toArray());
    $this->assertSame('{"type":"test","default":null}', $schema->toString());
  }

  public function testField(): void {
    $schema = $this->getTestingSchema()
      ->field('bool', true)
      ->field('int', 123)
      ->field('float', 5.2)
      ->field('string', 'abc')
      ->field('null', null);

    $this->assertSame([
      'type' => 'test',
      'bool' => true,
      'int' => 123,
      'float' => 5.2,
      'string' => 'abc',
      'null' => null,
    ], $schema->toArray());

    $this->assertSame('{"type":"test","bool":true,"int":123,"float":5.2,"string":"abc","null":null}', $schema->toString());
  }

  public function testReservedField(): void {
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage("Field name 'type' is reserved");
    $this->getTestingSchema()->field('type', 'invalid');
  }

  public function testMixedProperties(): void {
    $schema = $this->getTestingSchema()
      ->required()
      ->nullable()
      ->title('Schema title')
      ->description('Schema description')
      ->default('Default value');

    $this->assertSame([
      'type' => ['test', 'null'],
      'required' => true,
      'title' => 'Schema title',
      'description' => 'Schema description',
      'default' => 'Default value',
    ], $schema->toArray());

    $this->assertSame(
      '{"type":["test","null"],"required":true,"title":"Schema title","description":"Schema description","default":"Default value"}',
      $schema->toString()
    );
  }

  public function testImmutability(): void {
    $schema = $this->getTestingSchema();
    $this->assertNotSame($schema->nullable(), $schema);
    $this->assertNotSame($schema->nonNullable(), $schema);
    $this->assertNotSame($schema->required(), $schema);
    $this->assertNotSame($schema->optional(), $schema);
    $this->assertNotSame($schema->title('Title'), $schema);
    $this->assertNotSame($schema->description('Description'), $schema);
    $this->assertNotSame($schema->default(null), $schema);
    $this->assertNotSame($schema->field('name', 'value'), $schema);
  }

  private function getTestingSchema(): Schema {
    return new class extends Schema {
      protected $schema = ['type' => 'test'];

      protected function getReservedKeywords(): array {
        // See: rest_get_allowed_schema_keywords()
        return [
          'title',
          'description',
          'default',
          'type',
          'format',
          'enum',
          'items',
          'properties',
          'additionalProperties',
          'patternProperties',
          'minProperties',
          'maxProperties',
          'minimum',
          'maximum',
          'exclusiveMinimum',
          'exclusiveMaximum',
          'multipleOf',
          'minLength',
          'maxLength',
          'pattern',
          'minItems',
          'maxItems',
          'uniqueItems',
          'anyOf',
          'oneOf',
        ];
      }
    };
  }
}
