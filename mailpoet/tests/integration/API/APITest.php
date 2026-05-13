<?php declare(strict_types = 1);

namespace MailPoet\Test\API;

use MailPoet\API\API;
use MailPoet\Settings\SettingsController;

class APITest extends \MailPoetTest {
  public function testItCallsMPAPI() {
    verify(API::MP('v1'))->instanceOf('MailPoet\API\MP\v1\API');
  }

  public function testItAllowsSafeMPAPICallsWhenDbVersionIsBehind() {
    $settings = $this->diContainer->get(SettingsController::class);
    $originalDbVersion = $settings->get('db_version');
    $settings->set('db_version', '0.0.1');

    try {
      $api = API::MP('v1');

      verify($api)->instanceOf('MailPoet\API\MP\v1\API');
      verify(is_bool($api->isSetupComplete()))->true();
    } finally {
      $settings->set('db_version', $originalDbVersion);
    }
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
