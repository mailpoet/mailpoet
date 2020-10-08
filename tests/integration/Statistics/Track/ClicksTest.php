<?php

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Cookies;
use MailPoetVendor\Idiorm\ORM;

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
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $newsletter->subject = 'Subject';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->firstName = 'First';
    $subscriber->lastName = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$subscriber->id]);
    $queue->updateProcessedSubscribers([$subscriber->id]);
    $this->queue = $queue->save();
    // create link
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletterId = $newsletter->id;
    $link->queueId = $queue->id;
    $this->link = $link->save();
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'link' => $link,
      'preview' => false,
    ];
    // instantiate class
    $this->settingsController = Stub::makeEmpty(SettingsController::class, [
      'get' => false,
    ], $this);
    $this->clicks = new Clicks($this->settingsController, new Cookies());
  }

  public function testItAbortsWhenTrackDataIsEmptyOrMissingLink() {
    // abort function should be called twice:
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
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
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->isEmpty();
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItTracksClickAndOpenEvent() {
    $data = $this->trackData;
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
      'redirectToUrl' => null,
    ], $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->notEmpty();
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  public function testItRedirectsToUrlAfterTracking() {
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
      'redirectToUrl' => Expected::exactly(1),
    ], $this);
    $clicks->track($this->trackData);
  }

  public function testItIncrementsClickEventCount() {
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
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
    $clicks = Stub::construct($this->clicks, [$this->settingsController, new Cookies()], [
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

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
