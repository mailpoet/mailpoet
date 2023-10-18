<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Assert;

class CreateSubscriberScoreSegmentCest {
  public function _before() {
    $subscriber1 = (new Subscriber())
      ->withEmail('score_test1@example.com')
      ->withEngagementScore(0)
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('score_test2@example.com')
      ->withEngagementScore(50)
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('score_test3@example.com')
      // Engagement score not set, should be NULL
      ->create();
  }

  public function testSubscriberScoreSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new score segment');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $segmentTitle = 'Subscriber score segment';
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('engagement score', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-subscriber-score-operator"]');
    $i->selectOption('[data-automation-id="segment-subscriber-score-operator"]', 'lower than');
    $i->fillField('[data-automation-id="segment-subscriber-score-value"]', '20.51');
    $i->waitForText('This segment has');
    $i->click('Save');

    $i->wantTo('Edit the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="segment-subscriber-score-operator"]');
    $i->seeInField('[data-automation-id="segment-subscriber-score-value"]', '20.51');
    $i->waitForText('This segment has');
    $this->checkSubscriberCountGreaterThanZero($i);
    $i->seeNoJSErrors();

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('score_test1@example.com');
    $i->dontSee('score_test2@example.com');
    $i->dontSee('score_test3@example.com');
  }

  public function testSubscriberUnknownScoreSegment(\AcceptanceTester $i) {
    $i->wantTo('Create a new unknown score segment');
    $i->login();
    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $segmentTitle = 'Subscriber unknown score segment';
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('engagement score', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-subscriber-score-operator"]');
    $i->selectOption('[data-automation-id="segment-subscriber-score-operator"]', 'unknown');
    $i->dontSeeElement('[data-automation-id="segment-subscriber-score-value"]');
    $i->waitForText('This segment has');
    $i->click('Save');

    $i->wantTo('Edit the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="segment-subscriber-score-operator"]');
    $i->dontSeeElement('[data-automation-id="segment-subscriber-score-value"]');
    $i->waitForText('This segment has');
    $this->checkSubscriberCountGreaterThanZero($i);
    $i->seeNoJSErrors();

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->dontSee('score_test1@example.com');
    $i->dontSee('score_test2@example.com');
    $i->see('score_test3@example.com');
  }

  private function checkSubscriberCountGreaterThanZero(\AcceptanceTester $i) {
    $i->dontSee('This segment has 0 subscribers.');
    $subscribersCountText = $i->grabTextFrom('.mailpoet-segments-counter-section');
    Assert::assertIsString($subscribersCountText);
    preg_match('/has (\d+) subscribers/i', $subscribersCountText, $matches);
    verify((int)$matches[1])->greaterThan(0);
  }
}
