<?php
namespace MailPoet\Util;
use \phpseclib\Crypt\RSA;

class DKIM {
  static function generateKeys() {
    try {
      $rsa = new RSA();
      $rsa_keys = $rsa->createKey();

      return array(
        'public' => self::trimKey($rsa_keys['publickey']),
        'private' => self::trimKey($rsa_keys['privatekey'])
      );
    } catch(Exception $e) {
       return false;
    }
  }

  private static function trimKey($key) {
    $lines = explode("\n", trim($key));
    // remove first line
    array_shift($lines);
    // remove last line
    array_pop($lines);
    return join('', $lines);
  }
}