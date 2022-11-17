<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

class CreateSegmentEmailAbsoluteCountCest {
  public function _before() {
    $newsletter1 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment # of opens Test1')->withSentStatus()->create();
    $newsletter2 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment # of opens Test2')->withSentStatus()->create();
    $newsletter3 = (new Newsletter())
      ->withSendingQueue()->withSubject('Segment # of opens Test3')->withSentStatus()->create();

    $subscriber1 = (new Subscriber())
      ->withEmail('stats_test1@example.com')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('stats_test2@example.com')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('stats_test3@example.com')
      ->create();

    (new StatisticsOpens($newsletter1, $subscriber1))->create();
    (new StatisticsOpens($newsletter2, $subscriber1))->create();
    (new StatisticsOpens($newsletter3, $subscriber1))->create();

    (new StatisticsOpens($newsletter1, $subscriber2))->create();
    (new StatisticsOpens($newsletter2, $subscriber2))->create();
    (new StatisticsOpens($newsletter3, $subscriber2))->create();

    (new StatisticsOpens($newsletter2, $subscriber3))->create();
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Create a new segment # of opens');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $segmentTitle = 'Number of opens segment';
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'description');
    $i->selectOptionInReactSelect('# of opens', '[data-automation-id="select-segment-action"]');
    $i->waitForElementVisible('[data-automation-id="segment-number-of-opens"]');
    $i->fillField('[data-automation-id="segment-number-of-opens"]', 2);
    $i->fillField('[data-automation-id="segment-number-of-days"]', 3);
    $i->waitForText('This segment has 2 subscribers');
    $i->click('Save');

    $i->wantTo('Edit the segment');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'Edit');
    $i->waitForElementVisible('[data-automation-id="segment-number-of-opens"]');
    $i->seeInField('[data-automation-id="segment-number-of-opens"]', 2);
    $i->seeInField('[data-automation-id="segment-number-of-days"]', 3);
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();

    $i->wantTo('Check subscribers of the segment');
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->seeInCurrentUrl('mailpoet-subscribers#');
    $i->see($segmentTitle, ['css' => 'select[name=segment]']);
    $i->see('stats_test1@example.com');
    $i->see('stats_test2@example.com');
    $i->dontSee('stats_test3@example.com');
  }
}
