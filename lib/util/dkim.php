<?php
namespace MailPoet\Util;
class DKIM {

  public static function generate_keys() {
    try {
      $certificate = openssl_pkey_new(array('private_bits'  =>  1024));

      $keys = array('public' => '', 'private' => '');

      // get private key
      openssl_pkey_export($certificate, $keys['private']);

      // get public key
      $details = openssl_pkey_get_details($certificate);
      $keys['public'] = $details['key'];

      return $keys;
    } catch(Exception $e) {
       return false;
    }
  }
}