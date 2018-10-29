<?php
namespace MailPoet\Test\Util;

use MailPoet\Util\Security;

class SecurityTest extends \MailPoetTest {

  function testItCanGenerateWPNonce() {
    $wp_nonce = Security::generateToken();
    // expect length of nonce to be exactly 10
    expect(strlen($wp_nonce))->equals(10);
    // expect only alphanumerical characters
    expect(ctype_alnum($wp_nonce))->true();
  }

  function testItCanGenerateARandomString() {
    // it has a default length of 5
    $hash = Security::generateRandomString();
    expect(strlen($hash))->equals(5);

    // it has a min length of 5
    $short_hash = Security::generateRandomString(1);
    expect(strlen($short_hash))->equals(5);

    // it has a max length of 32
    $long_hash = Security::generateRandomString(64);
    expect(strlen($long_hash))->equals(32);

    // expect only alphanumerical characters
    expect(ctype_alnum($hash))->true();
    expect(ctype_alnum($short_hash))->true();
    expect(ctype_alnum($long_hash))->true();
  }

  function testItGeneratesRandomHash() {
    $hash_1 = Security::generateHash();
    $hash_2 = Security::generateHash();
    expect($hash_1)->notEquals($hash_2);
    expect(strlen($hash_1))->equals(Security::HASH_LENGTH);
  }

  function testItGeneratesRandomHashWithCustomLength() {
    expect(strlen(Security::generateHash(10)))->equals(10);
  }
}