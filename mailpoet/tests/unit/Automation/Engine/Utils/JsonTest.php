<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Utils;

use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Utils\Json;

class JsonTest extends \MailPoetUnitTest {
  public function testEncodeAlwaysReturnsObjectJson(): void {
    $this->assertSame('{}', Json::encode([]));
  }

  public function testEncodePreservesZeroFractionAndUnescapedSlashes(): void {
    $json = Json::encode([
      'price' => 1.0,
      'url' => 'https://example.com/newsletters/1',
    ]);

    $this->assertSame('{"price":1.0,"url":"https://example.com/newsletters/1"}', $json);
  }

  public function testEncodeEscapesHtmlTags(): void {
    $json = Json::encode([
      'html' => '<strong>Hi</strong>',
    ]);

    $this->assertSame('{"html":"\u003Cstrong\u003EHi\u003C/strong\u003E"}', $json);
  }

  public function testDecodeReturnsAssociativeArray(): void {
    $decoded = Json::decode('{"newsletter_id":123,"filters":{"status":"active"}}');

    $this->assertSame([
      'newsletter_id' => 123,
      'filters' => [
        'status' => 'active',
      ],
    ], $decoded);
  }

  public function testDecodeRejectsInvalidJson(): void {
    $this->expectException(InvalidStateException::class);

    Json::decode('{"newsletter_id":');
  }

  public function testDecodeRejectsScalarJson(): void {
    $this->expectException(UnexpectedValueException::class);

    Json::decode('"newsletter"');
  }
}
