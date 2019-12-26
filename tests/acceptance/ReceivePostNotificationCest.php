<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class ReceivePostNotificationCest {

  /** @var Settings */
  private $settings;

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  public function receivePostNotification(\AcceptanceTester $I) {
    $I->wantTo('Receive a post notification email');
    $newsletter_subject = 'Post Notification Receive Test' . \MailPoet\Util\Security::generateRandomString();
    $post_title = 'A post ' . \MailPoet\Util\Security::generateRandomString();

    $this->settings
      ->withTrackingDisabled()
      ->withCronTriggerMethod('WordPress');

    $segment_factory = new Segment();
    $segment = $segment_factory->withName('Receive Post Notification List')->create();

    $subscriber_factory = new Subscriber();
    $subscriber_factory->withSegments([$segment])->create();

    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_subject)
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withImmediateSendingSettings()
      ->withSegments([$segment])
      ->create();
    $I->wait(1); //waiting 1 second so that post created time is after the newsletter

    $I->cli(['post', 'create', "--post_title=$post_title", '--post_content=Lorem Ipsum', '--post_status=publish']);

    $I->login();

    // scheduler will create a task and schedule run in the next whole minute, that can break the test
    // I move the task to the past
    // this workaround is not ideal, but we cannot wait another minute for the task to execute :(
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '` t '
      . ' JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` '
      . ' SET t.scheduled_at="2016-01-01 01:02:03", t.updated_at="2016-01-01 01:02:03" '
      . ' WHERE q.newsletter_id=' . $newsletter->id()
    );

    // confirm newsletter has been sent
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="tab-Post Notifications"]');
    $I->waitForText($newsletter_subject, 90);

    $I->waitForText('View history', 90);
    $selector = sprintf('[data-automation-id="history-%d"]', $newsletter->id());
    $I->click($selector);
    $I->waitForText('Sent to 1 of 1', 90);

    // confirm newsletter is received
    $I->amOnMailboxAppPage();
    $I->waitForText($newsletter_subject, 90);
    $I->click(Locator::contains('span.subject', $newsletter_subject));
    $I->switchToIframe('preview-html');
    $I->waitForText($post_title, 90);
  }

}
