<?php

namespace MailPoet\Form\Util;

class FieldNameObfuscator {

  const OBFUSCATED_FIELD_PREFIX = 'form_field_';

  public function obfuscate($name) {
    return FieldNameObfuscator::OBFUSCATED_FIELD_PREFIX.base64_encode($name);
  }

  public function deobfuscate($name) {
    $prefixLength = strlen(FieldNameObfuscator::OBFUSCATED_FIELD_PREFIX);
    return base64_decode(substr($name, $prefixLength));
  }

  public function deobfuscateFormPayload($data) {
    $result = array();
    foreach($data as $key => $value) {
      $result[$this->deobfuscateField($key)] = $value;
    }
    return $result;
  }

  private function deobfuscateField($name) {
    if($this->wasFieldObfuscated($name)) {
      return $this->deobfuscate($name);
    } else {
      return $name;
    }
  }

  private function wasFieldObfuscated($name) {
    return strpos($name, FieldNameObfuscator::OBFUSCATED_FIELD_PREFIX) === 0;
  }

}