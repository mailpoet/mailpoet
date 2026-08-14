<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Subscriber;

class CreateTrackingConsentSegmentCest {
  private const ENGAGEMENT_NOTICE = 'MailPoet has no open or click data for subscribers who opted out of tracking';
  private const OMITTED_NOTICE = 'Subscribers who opted out of tracking are not counted here';

  public function _before() {
    (new Subscriber())
      ->withEmail('consent_granted@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED)
      ->create();
    (new Subscriber())
      ->withEmail('consent_denied@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED)
      ->create();
    (new Subscriber())
      ->withEmail('consent_unknown@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_UNKNOWN)
      ->create();
  }

  public function testTrackingOptOutSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a segment of subscribers who opted out of tracking');
    $segmentTitle = 'Tracking opt-outs';
    $this->startNewSegment($i, $segmentTitle);
    $i->selectOption('[data-automation-id="segment-tracking-consent-operator"]', 'is');
    $i->selectOption('[data-automation-id="segment-tracking-consent-value"]', 'opted out');
    $i->waitForText('This segment has');
    $i->dontSee(self::ENGAGEMENT_NOTICE);
    $i->click('Save');

    $i->wantTo('Edit the segment and see the saved values');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForText('Edit segment');
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->waitForElementVisible('[data-automation-id="segment-tracking-consent-operator"]');
    $i->seeOptionIsSelected('[data-automation-id="segment-tracking-consent-operator"]', 'is');
    $i->seeOptionIsSelected('[data-automation-id="segment-tracking-consent-value"]', 'opted out');
    $i->waitForText('This segment has 1 subscribers');
    $i->seeNoJSErrors();

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'View subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('consent_denied@example.com');
    $i->dontSee('consent_granted@example.com');
    $i->dontSee('consent_unknown@example.com');
  }

  public function testNotOptedOutSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a segment of everyone who did not opt out of tracking');
    $segmentTitle = 'Trackable subscribers';
    $this->startNewSegment($i, $segmentTitle);
    $i->selectOption('[data-automation-id="segment-tracking-consent-operator"]', 'is not');
    $i->selectOption('[data-automation-id="segment-tracking-consent-value"]', 'opted out');
    $i->waitForText('This segment has');
    $i->click('Save');

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'View subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('consent_granted@example.com');
    $i->see('consent_unknown@example.com');
    $i->dontSee('consent_denied@example.com');
  }

  public function testEngagementFilterShowsTheTrackingConsentNotice(\AcceptanceTester $i) {
    $i->wantTo('See the tracking consent notice on an engagement filter');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], 'Engagement notice segment');
    $i->fillField(['name' => 'description'], 'description');

    $i->wantTo('Confirm the notice is absent before an engagement filter is chosen');
    $i->selectOptionInReactSelect('tracking consent', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-tracking-consent-value"]');
    $i->waitForText('This segment has');
    $i->dontSee(self::ENGAGEMENT_NOTICE);

    $i->wantTo('Switch to an engagement filter and see the notice');
    $i->selectOptionInReactSelect('number of opens', '[data-automation-id="select-segment-action"]');
    $i->waitForText(self::ENGAGEMENT_NOTICE);
    $i->seeNoJSErrors();
  }

  /**
   * "clicked / any of" does not mislabel anyone — opted-out subscribers are
   * simply left out — so it gets the softer wording rather than the
   * "counts them as not engaged" one, which would be untrue here.
   */
  public function testAPositiveEngagementFilterShowsTheOmittedNotice(\AcceptanceTester $i) {
    $i->wantTo('See the omitted-subscribers notice on a positive engagement filter');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], 'Omitted notice segment');
    $i->fillField(['name' => 'description'], 'description');

    $i->wantTo('Choose "clicked", which defaults to the "any of" operator');
    $i->selectOptionInReactSelect('clicked', '[data-automation-id="select-segment-action"]');
    $i->waitForText(self::OMITTED_NOTICE);
    $i->dontSee(self::ENGAGEMENT_NOTICE);

    $i->wantTo('Switch to "none of" and see the stronger wording instead');
    $i->selectOption('[data-automation-id="select-operator"]', 'none of');
    $i->waitForText(self::ENGAGEMENT_NOTICE);
    $i->dontSee(self::OMITTED_NOTICE);
    $i->seeNoJSErrors();
  }

  private function startNewSegment(\AcceptanceTester $i, string $segmentTitle): void {
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('tracking consent', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-tracking-consent-operator"]');
    $i->waitForElementVisible('[data-automation-id="segment-tracking-consent-value"]');
  }
}
