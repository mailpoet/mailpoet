<?php declare(strict_types = 1);

namespace MailPoet\Validator\Schema;

use MailPoet\InvalidStateException;
use MailPoetUnitTest;

class StringSchemaTest extends MailPoetUnitTest {
  public function testPlain(): void {
    $string = new StringSchema();
    $this->assertSame(['type' => 'string'], $string->toArray());
    $this->assertSame('{"type":"string"}', $string->toString());
  }

  public function testMinLength(): void {
    $string = (new StringSchema())->minLength(3);
    $this->assertSame(['type' => 'string', 'minLength' => 3], $string->toArray());
    $this->assertSame('{"type":"string","minLength":3}', $string->toString());
  }

  public function testMaxLength(): void {
    $string = (new StringSchema())->maxLength(10);
    $this->assertSame(['type' => 'string', 'maxLength' => 10], $string->toArray());
    $this->assertSame('{"type":"string","maxLength":10}', $string->toString());
  }

  public function testPattern(): void {
    $string = (new StringSchema())->pattern('[0-9]+');
    $this->assertSame(['type' => 'string', 'pattern' => '[0-9]+'], $string->toArray());
    $this->assertSame('{"type":"string","pattern":"[0-9]+"}', $string->toString());

    $this->assertInvalidPattern('\\', "Invalid regular expression '#\\#u'");
    $this->assertInvalidPattern('\\#', "Invalid regular expression '#\\\\##u'");
    $this->assertInvalidPattern('[', "Invalid regular expression '#[#u'");
    $this->assertInvalidPattern('[0-9', "Invalid regular expression '#[0-9#u'");
  }

  public function testFormat(): void {
    $string = (new StringSchema())->formatDateTime();
    $this->assertSame(['type' => 'string', 'format' => 'date-time'], $string->toArray());
    $this->assertSame('{"type":"string","format":"date-time"}', $string->toString());

    $string = (new StringSchema())->formatEmail();
    $this->assertSame(['type' => 'string', 'format' => 'email'], $string->toArray());
    $this->assertSame('{"type":"string","format":"email"}', $string->toString());

    $string = (new StringSchema())->formatHexColor();
    $this->assertSame(['type' => 'string', 'format' => 'hex-color'], $string->toArray());
    $this->assertSame('{"type":"string","format":"hex-color"}', $string->toString());

    $string = (new StringSchema())->formatIp();
    $this->assertSame(['type' => 'string', 'format' => 'ip'], $string->toArray());
    $this->assertSame('{"type":"string","format":"ip"}', $string->toString());

    $string = (new StringSchema())->formatUri();
    $this->assertSame(['type' => 'string', 'format' => 'uri'], $string->toArray());
    $this->assertSame('{"type":"string","format":"uri"}', $string->toString());

    $string = (new StringSchema())->formatUuid();
    $this->assertSame(['type' => 'string', 'format' => 'uuid'], $string->toArray());
    $this->assertSame('{"type":"string","format":"uuid"}', $string->toString());
  }

  public function testMixedProperties(): void {
    $string = (new StringSchema())
      ->minLength(3)
      ->maxLength(10)
      ->pattern('@gmail\.com$')
      ->formatEmail();

    $this->assertSame([
      'type' => 'string',
      'minLength' => 3,
      'maxLength' => 10,
      'pattern' => '@gmail\.com$',
      'format' => 'email',
    ], $string->toArray());

    $this->assertSame(
      '{"type":"string","minLength":3,"maxLength":10,"pattern":"@gmail\\\\.com$","format":"email"}',
      $string->toString()
    );
  }

  public function testImmutability(): void {
    $string = new StringSchema();
    $this->assertNotSame($string->minLength(3), $string);
    $this->assertNotSame($string->maxLength(10), $string);
    $this->assertNotSame($string->pattern('[0-9]+'), $string);
    $this->assertNotSame($string->formatDateTime(), $string);
    $this->assertNotSame($string->formatEmail(), $string);
    $this->assertNotSame($string->formatHexColor(), $string);
    $this->assertNotSame($string->formatIp(), $string);
    $this->assertNotSame($string->formatUri(), $string);
    $this->assertNotSame($string->formatUuid(), $string);
  }

  private function assertInvalidPattern(string $pattern, string $message): void {
    try {
      (new StringSchema())->pattern($pattern);
    } catch (InvalidStateException $e) {
      $this->assertSame($message, $e->getMessage());
      return;
    }
    $class = InvalidStateException::class;
    $this->fail("Exception '$class' with message '$message' was not thrown.");
  }
}
