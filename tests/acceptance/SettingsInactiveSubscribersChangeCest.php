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

  public function inactiveSubscribersSettingsChange(\AcceptanceTester $i) {
    $i->wantTo('Change inactive users settings and reactivate all subscribers');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement('[data-automation-id="inactive-subscribers-enabled"]');
    $i->click('[data-automation-id="inactive-subscribers-option-never"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->amOnMailPoetPage('Subscribers');
    // Subscribers are activated in background so we do a couple of reloads
    for ($i = 0; $i < 15; $i++) {
      try {
        $i->wait(2);
        $i->reloadPage();
        $i->waitForListingItemsToLoad();
        $i->see('Inactive (0)');
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $i->see('Inactive (0)');
  }
}
