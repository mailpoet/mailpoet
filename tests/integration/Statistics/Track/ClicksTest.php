<?php

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Cookies;

class ClicksTest extends \MailPoetTest {
  public $trackData;
  public $link;
  public $queue;
  public $subscriber;
  public $newsletter;

  /** @var Clicks */
  private $clicks;

  private $settingsController;

  public function _before() {
    parent::_before();
    $this->cleanup();
    // create newsletter
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('Subject');
    $this->newsletter = $newsletter;
    $this->entityManager->persist($newsletter);
    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->subscriber = $subscriber;
    $this->entityManager->persist($subscriber);
    // create queue
    $task = new ScheduledTaskEntity();
    $task->setType('sending');
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $this->queue = $queue;
    $this->entityManager->persist($queue);

    // create link
    $link = new NewsletterLinkEntity($newsletter, $queue, 'url', 'hash');
    $this->link = $link;
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $subscriberModel = Subscriber::findOne($subscriber->getId());
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriberModel),
      'link' => $link,
      'preview' => false,
    ];
    $queue = SendingQueue::findOne($queue->getId());
    assert($queue instanceof SendingQueue);
    $queue = SendingTask::createFromQueue($queue);
    $queue->updateProcessedSubscribers([$subscriberModel->id]);
    // instantiate class
    $this->settingsController = Stub::makeEmpty(SettingsController::class, [
      'get' => false,
    ], $this);
    $this->clicks = new Clicks(
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class)
    );
  }

  public function testItAbortsWhenTrackDataIsEmptyOrMissingLink() {
    // abort function should be called twice:
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'abort' => Expected::exactly(2),
    ], $this);
    $data = $this->trackData;
    // 1. when tracking data does not exist
    $clicks->track(null);
    // 2. when link model object is missing
    unset($data->link);
    $clicks->track($data);
  }

  public function testItDoesNotTrackEventsFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $this->subscriber->setWpUserId(99);
    $this->entityManager->flush();
    $data->preview = true;
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->isEmpty();
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItTracksClickAndOpenEvent() {
    $data = $this->trackData;
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->notEmpty();
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  public function testItRedirectsToUrlAfterTracking() {
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($this->trackData);
  }

  public function testItIncrementsClickEventCount() {
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($this->trackData);
    expect(StatisticsClicks::findMany()[0]->count)->equals(1);
    $clicks->track($this->trackData);
    expect(StatisticsClicks::findMany()[0]->count)->equals(2);
  }

  public function testItConvertsShortcodesToUrl() {
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->stringContainsString('&endpoint=view_in_browser');
  }

  public function testItFailsToConvertsInvalidShortcodeToUrl() {
    $clicks = Stub::construct($this->clicks, [
      $this->settingsController,
      new Cookies(),
      $this->diContainer->get(Shortcodes::class),
      $this->diContainer->get(Opens::class),
      $this->diContainer->get(LinkShortcodeCategory::class),
    ], [
      'abort' => Expected::exactly(1),
    ], $this);
    // should call abort() method if shortcode action does not exist
    $link = $clicks->processUrl(
      '[link:]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
  }

  public function testItDoesNotConvertNonexistentShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:unknown_shortcode]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('[link:unknown_shortcode]');
  }

  public function testItDoesNotConvertRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com');
  }

  public function testItProcessesShortcodesInRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com/?email=[subscriber:email]&newsletter_subject=[newsletter:subject]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com/?email=test@example.com&newsletter_subject=Subject');
  }

  public function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
  }
}
