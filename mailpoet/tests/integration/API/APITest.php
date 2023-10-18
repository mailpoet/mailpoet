<?php declare(strict_types = 1);

namespace MailPoet\Test\API;

use MailPoet\API\API;

class APITest extends \MailPoetTest {
  public function testItCallsMPAPI() {
    verify(API::MP('v1'))->instanceOf('MailPoet\API\MP\v1\API');
  }

  public function testItThrowsErrorWhenWrongMPAPIVersionIsCalled() {
    try {
      API::MP('invalid_version');
      $this->fail('Incorrect API version exception should have been thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals('Invalid API version.');
    }
  }
}
