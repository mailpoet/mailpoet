<?php

namespace MailPoet\Form\Util;

class FieldNameObfuscatorTest extends \MailPoetTest {

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
}
