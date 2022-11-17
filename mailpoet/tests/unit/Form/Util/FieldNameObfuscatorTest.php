<?php declare(strict_types = 1);

namespace MailPoet\Form\Util;

use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class FieldNameObfuscatorTest extends \MailPoetUnitTest {

  /** @var MockObject | WPFunctions */
  private $wpMock;

  /** @var FieldNameObfuscator */
  private $obfuscator;

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->wpMock->method('homeUrl')->willReturn('http://example.com');
    $this->obfuscator = new FieldNameObfuscator($this->wpMock);
  }

  public function testObfuscateWorks() {
    expect($this->obfuscator->obfuscate('email'))->stringNotContainsString('email');
  }

  public function testObfuscateDeobfuscateWorks() {
    $obfuscated = $this->obfuscator->obfuscate('email');
    expect($this->obfuscator->deobfuscate($obfuscated))->equals('email');
  }

  public function testObfuscatePayloadWorks() {
    $obfuscated = $this->obfuscator->obfuscate('email');
    $data = [
      'regularField' => 'regularValue',
      $obfuscated => 'obfuscatedFieldValue',
    ];
    $deobfuscatedPayload = $this->obfuscator->deobfuscateFormPayload($data);
    expect($deobfuscatedPayload)->equals([
      'regularField' => 'regularValue',
      'email' => 'obfuscatedFieldValue',
    ]);
  }
}
