<?php

namespace MailPoet\Form\Util;

use Codeception\Stub;
use MailPoet\WP\Functions as WPFunctions;

class FieldNameObfuscatorTest extends \MailPoetUnitTest {
  function _before() {
    parent::_before();
    WPFunctions::set(
      Stub::make(WPFunctions::class, [
        'homeUrl' => 'http://example.com',
      ])
    );
  }

  public function testObfuscateWorks() {
    $obfuscator = new FieldNameObfuscator();
    expect($obfuscator->obfuscate('email'))->notContains('email');
  }

  public function testObfuscateDeobfuscateWorks() {
    $obfuscator = new FieldNameObfuscator();
    $obfuscated = $obfuscator->obfuscate('email');
    expect($obfuscator->deobfuscate($obfuscated))->equals('email');
  }

  public function testObfuscatePayloadWorks() {
    $obfuscator = new FieldNameObfuscator();
    $obfuscated = $obfuscator->obfuscate('email');
    $data = array(
      'regularField' => 'regularValue',
      $obfuscated => 'obfuscatedFieldValue',
    );
    $deobfuscatedPayload = $obfuscator->deobfuscateFormPayload($data);
    expect($deobfuscatedPayload)->equals(array(
      'regularField' => 'regularValue',
      'email' => 'obfuscatedFieldValue',
    ));
  }

  function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions());
  }
}
