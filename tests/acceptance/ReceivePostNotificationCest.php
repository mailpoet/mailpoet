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

  public function _before() {
    $this->settings = new Settings();
  }

  public function receivePostNotification(\AcceptanceTester $i) {
    $i->wantTo('Receive a post notification email');
    $newsletterSubject = 'Post Notification Receive Test' . \MailPoet\Util\Security::generateRandomString();
    $postTitle = 'A post ' . \MailPoet\Util\Security::generateRandomString();

    $this->settings
      ->withTrackingDisabled()
      ->withCronTriggerMethod('WordPress');

    $segmentFactory = new Segment();
    $segment = $segmentFactory->withName('Receive Post Notification List')->create();

    $subscriberFactory = new Subscriber();
    $subscriberFactory->withSegments([$segment])->create();

    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterSubject)
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withImmediateSendingSettings()
      ->withSegments([$segment])
      ->create();
    $i->wait(1); //waiting 1 second so that post created time is after the newsletter

    $i->cli(['post', 'create', "--post_title=$postTitle", '--post_content=Lorem Ipsum', '--post_status=publish']);

    $i->login();

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
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="tab-Post Notifications"]');
    $i->waitForText($newsletterSubject, 90);

    $i->waitForText('View history', 90);
    $selector = sprintf('[data-automation-id="history-%d"]', $newsletter->id());
    $i->click($selector);
    $i->waitForText('Processed 1 of 1', 90);

    // confirm newsletter is received
    $i->checkEmailWasReceived($newsletterSubject);
    $i->click(Locator::contains('span.subject', $newsletterSubject));
    $i->switchToIframe('#preview-html');
    $i->waitForText($postTitle, 90);
  }
}
