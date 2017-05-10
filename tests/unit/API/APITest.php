<?php

use MailPoet\API\API;

class APITest extends MailPoetTest {
  function testItCallsJSONAPI() {
    expect(API::JSON())->isInstanceOf('MailPoet\API\JSON\API');
  }

  function testItCallsMPAPI() {
    expect(API::MP('v1'))->isInstanceOf('MailPoet\API\MP\v1\API');
  }

  function testItThrowsErrorWhenWrongMPAPIVersionIsCalled() {
    try {
      API::MP('invalid_version');
      $this->fail('Incorrect API version exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Invalid API version.');
    }
  }
}