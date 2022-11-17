<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Security;

class SecurityTest extends \MailPoetUnitTest {
  public function testItCanGenerateARandomString() {
    // it has a default length of 5
    $hash = Security::generateRandomString();
    expect(strlen($hash))->equals(5);

    // it has a min length of 5
    $shortHash = Security::generateRandomString(1);
    expect(strlen($shortHash))->equals(5);

    $longHash = Security::generateRandomString(64);
    expect(strlen($longHash))->equals(64);

    // expect only alphanumerical characters
    expect(ctype_alnum($hash))->true();
    expect(ctype_alnum($shortHash))->true();
    expect(ctype_alnum($longHash))->true();
  }

  public function testItGeneratesRandomHash() {
    $hash1 = Security::generateHash();
    $hash2 = Security::generateHash();
    expect($hash1)->notEquals($hash2);
    expect(strlen($hash1))->equals(Security::HASH_LENGTH);
  }

  public function testItGeneratesRandomHashWithCustomLength() {
    expect(strlen(Security::generateHash(10)))->equals(10);
  }
}
