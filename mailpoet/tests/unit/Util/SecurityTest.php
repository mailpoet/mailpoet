<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Security;

class SecurityTest extends \MailPoetUnitTest {
  public function testItCanGenerateARandomString() {
    // it has a default length of 5
    $hash = Security::generateRandomString();
    verify(strlen($hash))->equals(5);

    // it has a min length of 5
    $shortHash = Security::generateRandomString(1);
    verify(strlen($shortHash))->equals(5);

    $longHash = Security::generateRandomString(64);
    verify(strlen($longHash))->equals(64);

    // expect only alphanumerical characters
    verify(ctype_alnum($hash))->true();
    verify(ctype_alnum($shortHash))->true();
    verify(ctype_alnum($longHash))->true();
  }

  public function testItGeneratesRandomHash() {
    $hash1 = Security::generateHash();
    $hash2 = Security::generateHash();
    expect($hash1)->notEquals($hash2);
    verify(strlen($hash1))->equals(Security::HASH_LENGTH);
  }

  public function testItGeneratesRandomHashWithCustomLength() {
    verify(strlen(Security::generateHash(10)))->equals(10);
  }
}
