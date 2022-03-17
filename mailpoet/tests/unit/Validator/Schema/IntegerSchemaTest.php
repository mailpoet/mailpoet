<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoetUnitTest;

class IntegerSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $integer = new IntegerSchema();
    $this->assertSame(['type' => 'integer'], $integer->toArray());
    $this->assertSame('{"type":"integer"}', $integer->toString());
  }

  public function testMin(): void {
    $integer = (new IntegerSchema())->minimum(1);
    $this->assertSame(['type' => 'integer', 'minimum' => 1], $integer->toArray());
    $this->assertSame('{"type":"integer","minimum":1}', $integer->toString());
  }

  public function testMax(): void {
    $integer = (new IntegerSchema())->maximum(99);
    $this->assertSame(['type' => 'integer', 'maximum' => 99], $integer->toArray());
    $this->assertSame('{"type":"integer","maximum":99}', $integer->toString());
  }

  public function testExclusiveMin(): void {
    $integer = (new IntegerSchema())->exclusiveMinimum(1);
    $this->assertSame(['type' => 'integer', 'minimum' => 1, 'exclusiveMinimum' => true], $integer->toArray());
    $this->assertSame('{"type":"integer","minimum":1,"exclusiveMinimum":true}', $integer->toString());
  }

  public function testExclusiveMax(): void {
    $integer = (new IntegerSchema())->exclusiveMaximum(99);
    $this->assertSame(['type' => 'integer', 'maximum' => 99, 'exclusiveMaximum' => true], $integer->toArray());
    $this->assertSame('{"type":"integer","maximum":99,"exclusiveMaximum":true}', $integer->toString());
  }

  public function testMultipleOf(): void {
    $integer = (new IntegerSchema())->multipleOf(2);
    $this->assertSame(['type' => 'integer', 'multipleOf' => 2], $integer->toArray());
    $this->assertSame('{"type":"integer","multipleOf":2}', $integer->toString());
  }

  public function testMixedProperties(): void {
    $integer = (new IntegerSchema())
      ->minimum(0)
      ->exclusiveMaximum(10)
      ->multipleOf(3);

    $this->assertSame([
      'type' => 'integer',
      'minimum' => 0,
      'maximum' => 10,
      'exclusiveMaximum' => true,
      'multipleOf' => 3,
    ], $integer->toArray());

    $this->assertSame(
      '{"type":"integer","minimum":0,"maximum":10,"exclusiveMaximum":true,"multipleOf":3}',
      $integer->toString()
    );
  }

  public function testImmutability(): void {
    $integer = new IntegerSchema();
    $this->assertNotSame($integer->minimum(0), $integer);
    $this->assertNotSame($integer->maximum(10), $integer);
    $this->assertNotSame($integer->exclusiveMinimum(0), $integer);
    $this->assertNotSame($integer->exclusiveMaximum(10), $integer);
    $this->assertNotSame($integer->multipleOf(3), $integer);
  }
}
