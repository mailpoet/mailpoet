<?php

class UtilDKIMCest {

  public function itCanGenerateKeys() {
    $keys = \MailPoet\Util\DKIM::generateKeys();
    $public_header = '-----BEGIN PUBLIC KEY-----';
    $private_header = '-----BEGIN PRIVATE KEY-----';

    expect($keys['public'])->notEmpty();
    expect($keys['private'])->notEmpty();

    expect($keys['public'])->contains($public_header);
    expect($keys['private'])->contains($private_header);
  }
}
