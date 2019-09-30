<?php

namespace MailPoet\Test\API;

use MailPoet\API\API;

class APITest extends \MailPoetTest {

  function testItCallsMPAPI() {
    expect(API::MP('v1'))->isInstanceOf('MailPoet\API\MP\v1\API');
  }

  function testItThrowsErrorWhenWrongMPAPIVersionIsCalled() {
    try {
      API::MP('invalid_version');
      $this->fail('Incorrect API version exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Invalid API version.');
    }
  }
}
