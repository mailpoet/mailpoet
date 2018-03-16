<?php
namespace MailPoet\Test\Statistics\Track;

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Clicks;

class ClicksTest extends \MailPoetTest {
  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $newsletter->subject = 'Subject';
    $this->newsletter = $newsletter->save();
    // create subscriber
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->first_name = 'First';
    $subscriber->last_name = 'Last';
    $this->subscriber = $subscriber->save();
    // create queue
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->subscribers = array('processed' => array($subscriber->id));
    $this->queue = $queue->save();
    // create link
    $link = NewsletterLink::create();
    $link->hash = 'hash';
    $link->url = 'url';
    $link->newsletter_id = $newsletter->id;
    $link->queue_id = $queue->id;
    $this->link = $link->save();
    // build track data
    $this->track_data = (object)array(
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => Subscriber::generateToken($subscriber->email),
      'link' => $link,
      'preview' => false
    );
    // instantiate class
    $this->clicks = new Clicks();
  }

  function testItAbortsWhenTrackDataIsEmptyOrMissingLink() {
    // abort function should be called twice:
    $clicks = Stub::make($this->clicks, array(
      'abort' => Stub::exactly(2)
    ), $this);
    $data = $this->track_data;
    // 1. when tracking data does not exist
    $clicks->track(false);
    // 2. when link model object is missing
    unset($data->link);
    $clicks->track($data);
  }

  function testItDoesNotTrackEventsFromWpUserWhenPreviewIsEnabled() {
    $data = $this->track_data;
    $data->subscriber->wp_user_id = 99;
    $data->preview = true;
    $clicks = Stub::make($this->clicks, array(
      'redirectToUrl' => null
    ), $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->isEmpty();
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  function testItTracksClickAndOpenEvent() {
    $data = $this->track_data;
    $clicks = Stub::make($this->clicks, array(
      'redirectToUrl' => null
    ), $this);
    $clicks->track($data);
    expect(StatisticsClicks::findMany())->notEmpty();
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  function testItRedirectsToUrlAfterTracking() {
    $clicks = Stub::make($this->clicks, array(
      'redirectToUrl' => Stub::exactly(1)
    ), $this);
    $clicks->track($this->track_data);
  }

  function testItIncrementsClickEventCount() {
    $clicks = Stub::make($this->clicks, array(
      'redirectToUrl' => null
    ), $this);
    $clicks->track($this->track_data);
    expect(StatisticsClicks::findMany()[0]->count)->equals(1);
    $clicks->track($this->track_data);
    expect(StatisticsClicks::findMany()[0]->count)->equals(2);
  }

  function testItConvertsShortcodesToUrl() {
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->contains('&endpoint=view_in_browser');
  }

  function testItFailsToConvertsInvalidShortcodeToUrl() {
    $clicks = Stub::make($this->clicks, array(
      'abort' => Stub::exactly(1)
    ), $this);
    // should call abort() method if shortcode action does not exist
    $link = $clicks->processUrl(
      '[link:]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
  }

  function testItDoesNotConvertNonexistentShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:unknown_shortcode]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('[link:unknown_shortcode]');
  }

  function testItDoesNotConvertRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com');
  }

  function testItProcessesShortcodesInRegularUrls() {
    $link = $this->clicks->processUrl(
      'http://example.com/?email=[subscriber:email]&newsletter_subject=[newsletter:subject]',
      $this->newsletter,
      $this->subscriber,
      $this->queue,
      $preview = false
    );
    expect($link)->equals('http://example.com/?email=test@example.com&newsletter_subject=Subject');
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}