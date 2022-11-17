<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Exception;

class InactiveSubscribersChangeCest {

  const INACTIVE_SUBSCRIBERS_COUNT = 2;
  const INACTIVE_LIST_NAME = 'Lazy Subscribers';

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
    $segment = (new Segment())->withName(self::INACTIVE_LIST_NAME)->create();
    (new Subscriber())->withSegments([$segment])->create();
    for ($i = 0; $i < self::INACTIVE_SUBSCRIBERS_COUNT; $i++) {
      (new Subscriber())->withStatus('inactive')->withSegments([$segment])->create();
    }
    $this->settings
      ->withDeactivateSubscriberAfter6Months()
      ->withTrackingEnabled()
      ->withCronTriggerMethod('Action Scheduler');
  }

  public function inactiveSubscribersSettingsChange(\AcceptanceTester $i) {
    $i->wantTo('Change inactive users settings and reactivate all subscribers');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement('[data-automation-id="inactive-subscribers-option-never"]');
    $i->click('[data-automation-id="inactive-subscribers-option-never"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->amOnMailPoetPage('Subscribers');
    // Subscribers are activated in background so we do a couple of reloads
    for ($index = 0; $index < 15; $index++) {
      try {
        $i->wait(2);
        $i->reloadPage();
        $i->waitForListingItemsToLoad();
        $i->see('Inactive');
        $i->dontSeeElement('[data-automation-id="filters_inactive"] .mailpoet-listing-groups-count');
        return;
      } catch (Exception $e) {
        continue;
      }
    }
    $i->see('Inactive');
    $i->dontSeeElement('[data-automation-id="filters_inactive"] .mailpoet-listing-groups-count');
  }
}
