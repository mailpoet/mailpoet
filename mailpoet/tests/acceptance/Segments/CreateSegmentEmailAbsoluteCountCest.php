<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsBounces;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

class CreateSegmentEmailAbsoluteCountCest {
  public function _before() {
    $newsletter1 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment number of opens Test1')->withSentStatus()->create();
    $newsletter2 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment number of opens Test2')->withSentStatus()->create();
    $newsletter3 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment number of opens Test3')->withSentStatus()->create();

    $subscriber1 = (new Subscriber())
      ->withEmail('stats_test1@example.com')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('stats_test2@example.com')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('stats_test3@example.com')
      ->create();
    $subscriber4 = (new Subscriber())
      ->withEmail('stats_test4@example.com')
      ->create();
    $subscriber5 = (new Subscriber())
      ->withEmail('stats_test5@example.com')
      ->create();

    (new StatisticsOpens($newsletter1, $subscriber1))->create();
    (new StatisticsOpens($newsletter2, $subscriber1))->create();
    (new StatisticsOpens($newsletter3, $subscriber1))->create();

    (new StatisticsOpens($newsletter1, $subscriber2))->create();
    (new StatisticsOpens($newsletter2, $subscriber2))->create();
    (new StatisticsOpens($newsletter3, $subscriber2))->create();

    (new StatisticsOpens($newsletter2, $subscriber3))->create();
    (new StatisticsOpens($newsletter1, $subscriber5))->withMachineUserAgentType()->create();

    (new StatisticsBounces($newsletter1, $subscriber4))->create();
  }

  public function segmentWithNewsletterStats(\AcceptanceTester $i) {
    $i->wantTo('Create a new segment number of opens');

    $i->login();

    $i->amOnMailpoetPage('Segments');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->click('[data-automation-id="new-custom-segment"]');
    $segmentTitle = 'Number of opens segment';
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('number of opens', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-number-of-opens"]');
    $i->fillField('[data-automation-id="segment-number-of-opens"]', 2);
    $i->fillField('[data-automation-id="segment-number-of-days"]', 3);
    $i->waitForText('This segment has 2 subscribers');
    $i->click('Save');
    $i->waitForNoticeAndClose('Segment successfully added!');

    $i->wantTo('Edit the segment');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="segment-number-of-opens"]');
    $i->seeInField('[data-automation-id="segment-number-of-opens"]', '2');
    $i->seeInField('[data-automation-id="segment-number-of-days"]', '3');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();

    $i->fillField('[data-automation-id="segment-number-of-opens"]', 10);
    $i->fillField('[data-automation-id="segment-number-of-days"]', 1);
    $i->waitForText('This segment has 0 subscribers');
    $i->seeNoJSErrors();

    $i->selectOptionInReactSelect('number of machine-opens', '[data-automation-id="select-segment-action"]');
    $i->waitForText('This segment has 0 subscribers');
    $i->fillField('[data-automation-id="segment-number-of-opens"]', 0);
    $i->waitForText('This segment has 1 subscribers');
    $i->seeNoJSErrors();

    $i->selectOptionInReactSelect('machine-opened', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-email"]');
    $i->selectOptionInReactSelect('Segment number of opens Test1', '[data-automation-id="segment-email"]');
    $i->waitForText('This segment has 1 subscribers');
    $i->seeNoJSErrors();

    $i->click('Save');
    $i->waitForNoticeAndClose('Segment successfully updated!');
    $i->waitForListingItemsToLoad();
    $i->waitForText($segmentTitle);

    $i->wantTo('Check there is one subscriber on the segment');
    $i->clickWooTableActionByItemName($segmentTitle, 'View subscribers');
    $i->waitForText('stats_test5@example.com');

    $i->wantTo('Edit the segment again');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="segment-email"]');
    $i->waitForText('This segment has 1 subscribers');
    $i->selectOptionInReactSelect('number of opens', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-number-of-opens"]');
    $i->fillField('[data-automation-id="segment-number-of-opens"]', 1);
    $i->fillField('[data-automation-id="segment-number-of-days"]', 7);
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();

    $i->click('Save');
    $i->waitForNoticeAndClose('Segment successfully updated!');

    $i->wantTo('Check subscribers of the segment');
    $i->waitForText($segmentTitle);
    $i->clickWooTableActionByItemName($segmentTitle, 'View subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('stats_test1@example.com');
    $i->see('stats_test2@example.com');
    $i->dontSee('stats_test3@example.com');
  }
}
