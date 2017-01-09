<?php
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Security;

class SecurityTest extends MailPoetTest {
  function testItGeneratesWPNonceForAPIRequests() {
    $wp_nonce = Security::generateToken('API');
    // expect length of nonce to be exactly 10
    expect(strlen($wp_nonce))->equals(10);
    // expect only alphanumerical characters
    expect(ctype_alnum($wp_nonce))->true();
  }

  function testItGeneratesSubscriberToken() {
    $token = Security::generateToken('subscriber', 6, 'test@email.com');
    expect(strlen($token))->equals(6);
    expect(Subscriber::verifyToken('test@email.com', $token))->true();
  }

  function testItGeneratesNewsletterHash() {
    $hash = Security::generateToken('subscriber', 6);
    expect(strlen($hash))->equals(6);
  }

  function testItCanGenerateARandomString() {
    // it has a default length of 15
    $hash = Security::generateRandomString(15);
    expect(strlen($hash))->equals(15);

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

  function testItCreatesAndSavesNewSecurityKeyWhenWPAUthKeyDoesNotExist() {
    expect(Setting::getValue('security_key'))->null();
    $security_key = Security::getOrCreateSecurityKey($wp_auth_key = 'undefined');
    expect(Setting::getValue('security_key'))->equals($security_key);
  }

  function testItUpdatesDBWithWPAuthKeyWhenOneExistst() {
    expect(Setting::getValue('security_key'))->null();
    $security_key = Security::getOrCreateSecurityKey();
    expect(Setting::getValue('security_key'))->equals(AUTH_KEY);
  }

  function testItRetrievesExistingSecurityKey() {
    Setting::setValue('security_key', 'test');
    expect(Security::getOrCreateSecurityKey())->equals('test');
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}