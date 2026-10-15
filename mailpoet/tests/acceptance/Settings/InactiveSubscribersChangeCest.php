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
        $i->triggerMailPoetActionScheduler();
        $i->reloadPage();
        $i->waitForListingItemsToLoad();
        $this->waitForNoInactiveSubscribersInStatusFilter($i);
        return;
      } catch (Exception $e) {
        continue;
      }
    }
    $this->waitForNoInactiveSubscribersInStatusFilter($i);
  }

  // The count lives in the Status filter option label, e.g. "Inactive (0)".
  private function waitForNoInactiveSubscribersInStatusFilter(\AcceptanceTester $i): void {
    $i->click(['xpath' => '//button[@aria-label="Add filter"]']);
    $i->click(['xpath' => '//*[@role="menuitem"][normalize-space(.)="Status"]']);
    try {
      $i->waitForJS(
        'return Array.from(document.querySelectorAll(".dataviews-filters__search-widget-listitem")).some(function (item) { return item.textContent.indexOf("Inactive (0)") !== -1; });',
        3
      );
    } catch (\Facebook\WebDriver\Exception\TimeoutException $e) {
      // Reported as an assertion failure so the caller's retry loop reloads and tries again.
      throw new Exception('Status filter still shows inactive subscribers.');
    }
    $i->pressKey('body', \Facebook\WebDriver\WebDriverKeys::ESCAPE);
  }
}
