<?php declare(strict_types = 1);

namespace MailPoet\Test\API;

use MailPoet\API\API;
use MailPoet\Config\Env;
use MailPoet\Settings\SettingsController;

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

  public function testItThrowsWhenCalledBeforeMigrationsHaveRun() {
    $settings = $this->diContainer->get(SettingsController::class);
    $originalDbVersion = $settings->get('db_version');
    $settings->set('db_version', '0.0.1');

    try {
      API::MP('v1');
      $this->fail('Expected readiness exception was not thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->stringContainsString('still initializing');
    } finally {
      $settings->set('db_version', $originalDbVersion);
    }
  }

  public function testItDoesNotThrowOnceDbVersionMatches() {
    $settings = $this->diContainer->get(SettingsController::class);
    $originalDbVersion = $settings->get('db_version');
    $settings->set('db_version', Env::$version);

    try {
      verify(API::MP('v1'))->instanceOf('MailPoet\API\MP\v1\API');
    } finally {
      $settings->set('db_version', $originalDbVersion);
    }
  }
}
