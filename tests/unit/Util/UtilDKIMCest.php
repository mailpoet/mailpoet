<?php

class UtilDKIMCest {

  public function itCanGenerateKeys() {
    $keys = \MailPoet\Util\DKIM::generateKeys();

    $public_header = 'PUBLIC KEY-----';
    $private_header = 'PRIVATE KEY-----';

    expect($keys['public'])->notEmpty();
    expect($keys['private'])->notEmpty();

    expect($keys['public'])->notContains($public_header);
    expect($keys['private'])->notContains($private_header);
  }
}
