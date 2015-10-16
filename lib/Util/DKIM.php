<?php
namespace MailPoet\Util;

class DKIM {
  static function generateKeys() {
    try {
      $certificate = openssl_pkey_new(array('private_bits'  =>  1024));

      $keys = array('public' => '', 'private' => '');

      // get private key
      openssl_pkey_export($certificate, $keys['private']);

      // get public key
      $public = openssl_pkey_get_details($certificate);

      // trim keys by removing BEGIN/END lines
      $keys['public'] = self::trimKey($public['key']);
      $keys['private'] = self::trimKey($keys['private']);

      return $keys;
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