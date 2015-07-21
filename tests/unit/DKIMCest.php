<?php
use \UnitTester;

class DKIMCest {

  public function it_can_generate_keys() {
    $keys = \MailPoet\Util\DKIM::generate_keys();
    $public_header = '-----BEGIN PUBLIC KEY-----';
    $private_header = '-----BEGIN RSA PRIVATE KEY-----';

    expect($keys['public'])->notEmpty();
    expect($keys['private'])->notEmpty();

    expect($keys['public'])->contains($public_header);
    expect($keys['private'])->contains($private_header);
  }
}
