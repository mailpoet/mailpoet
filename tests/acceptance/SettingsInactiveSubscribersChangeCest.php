<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class SettingsInactiveSubscribersChangeCest {

  const INACTIVE_SUBSCRIBERS_COUNT = 2;
  const INACTIVE_LIST_NAME = 'Lazy Subscribers';

  /** @var Settings */
  private $settings;

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  public function _before() {
    $segment = (new Segment())->withName(self::INACTIVE_LIST_NAME)->create();
    (new Subscriber())->withSegments([$segment])->create();
    for ($i = 0; $i < self::INACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('inactive')->withSegments([$segment])->create();
    }
    $this->settings
      ->withDeactivateSubscriberAfter6Months()
      ->withTrackingEnabled()
      ->withCronTriggerMethod('WordPress');
  }

  function inactiveSubscribersSettingsChange(\AcceptanceTester $I) {
    $I->wantTo('Change inactive users settings and reactivate all subscribers');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement('[data-automation-id="inactive-subscribers-enabled"]');
    $I->click('[data-automation-id="inactive-subscribers-option-never"]');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
    $I->amOnMailPoetPage('Subscribers');
    // Subscribers are activated in background so we do a couple of reloads
    for ($i = 0; $i < 15; $i++) {
      try {
        $I->wait(2);
        $I->reloadPage();
        $I->waitForListingItemsToLoad();
        $I->see('Inactive (0)');
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $I->see('Inactive (0)');
  }
}
