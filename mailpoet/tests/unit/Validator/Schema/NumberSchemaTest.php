<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoetUnitTest;

class NumberSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $number = new NumberSchema();
    $this->assertSame(['type' => 'number'], $number->toArray());
    $this->assertSame('{"type":"number"}', $number->toString());
  }

  public function testMin(): void {
    $number = (new NumberSchema())->minimum(1.111);
    $this->assertSame(['type' => 'number', 'minimum' => 1.111], $number->toArray());
    $this->assertSame('{"type":"number","minimum":1.111}', $number->toString());
  }

  public function testMax(): void {
    $number = (new NumberSchema())->maximum(99.999);
    $this->assertSame(['type' => 'number', 'maximum' => 99.999], $number->toArray());
    $this->assertSame('{"type":"number","maximum":99.999}', $number->toString());
  }

  public function testExclusiveMin(): void {
    $number = (new NumberSchema())->exclusiveMinimum(1.111);
    $this->assertSame(['type' => 'number', 'minimum' => 1.111, 'exclusiveMinimum' => true], $number->toArray());
    $this->assertSame('{"type":"number","minimum":1.111,"exclusiveMinimum":true}', $number->toString());
  }

  public function testExclusiveMax(): void {
    $number = (new NumberSchema())->exclusiveMaximum(99.999);
    $this->assertSame(['type' => 'number', 'maximum' => 99.999, 'exclusiveMaximum' => true], $number->toArray());
    $this->assertSame('{"type":"number","maximum":99.999,"exclusiveMaximum":true}', $number->toString());
  }

  public function testMultipleOf(): void {
    $number = (new NumberSchema())->multipleOf(0.1);
    $this->assertSame(['type' => 'number', 'multipleOf' => 0.1], $number->toArray());
    $this->assertSame('{"type":"number","multipleOf":0.1}', $number->toString());
  }

  public function testMixedProperties(): void {
    $number = (new NumberSchema())
      ->minimum(0.111)
      ->exclusiveMaximum(0.999)
      ->multipleOf(0.2);

    $this->assertSame([
      'type' => 'number',
      'minimum' => 0.111,
      'maximum' => 0.999,
      'exclusiveMaximum' => true,
      'multipleOf' => 0.2,
    ], $number->toArray());

    $this->assertSame(
      '{"type":"number","minimum":0.111,"maximum":0.999,"exclusiveMaximum":true,"multipleOf":0.2}',
      $number->toString()
    );
  }

  public function testImmutability(): void {
    $number = new NumberSchema();
    $this->assertNotSame($number->minimum(0.111), $number);
    $this->assertNotSame($number->maximum(0.999), $number);
    $this->assertNotSame($number->exclusiveMinimum(0.111), $number);
    $this->assertNotSame($number->exclusiveMaximum(9.999), $number);
    $this->assertNotSame($number->multipleOf(0.2), $number);
  }
}
