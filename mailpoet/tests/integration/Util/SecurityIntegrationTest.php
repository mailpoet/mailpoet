<?php

namespace MailPoet\Test\Util;

use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class SecurityIntegrationTest extends \MailPoetTest {
  public function testItCanGenerateAndVerifyTokens() {
    $token = Security::generateToken();
    expect(Security::checkToken($token))->true();
  }

  public function testItRejectsIncorrectTokens() {
    expect(Security::checkToken('not a real token'))->false();
  }

  public function testItCanGenerateAndVerifyTokensForUniqueActions() {
    $token = Security::generateTokenForUniqueAction('uniqueAction');
    expect(Security::checkTokenForUniqueAction($token, 'uniqueAction'))->true();
  }

  public function testItRejectsTokensCreatedForADifferentAction() {
    $token = Security::generateTokenForUniqueAction('uniqueAction');
    expect(Security::checkToken($token, 'someOtherAction'))->false();
  }

  public function testItDoesNotAllowReUseOfTokensForUniqueActions() {
    $token = Security::generateTokenForUniqueAction('uniqueAction');
    expect(Security::checkTokenForUniqueAction($token, 'uniqueAction'))->true();
    expect(Security::checkTokenForUniqueAction($token, 'uniqueAction'))->false();
  }

  public function testItDoesAllowReuseOfTokensForNonUniqueActions() {
    $token = Security::generateToken();
    expect(Security::checkToken($token))->true();
    expect(Security::checkToken($token))->true();
  }

  public function _after() {
    global $wpdb;
    // Clean up transients used to check if a token has already been used because they persist between tests
    $results = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%mailpoetUsedNonce_%'" );
    foreach ($results as $result) {
      // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      WPFunctions::get()->deleteOption($result->option_name);
    }
  }
}
