<?php

use Codeception\Util\Stub;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Clicks;

class ClicksTest extends MailPoetTest {
  function _before() {
    // create newsletter
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
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
    // instantiate class
    $this->clicks = new Clicks(true);
  }

  function testItCanConstruct() {
    $clicks = new Clicks('test');
    expect($clicks->data)->equals('test');
  }

  function testItCanGetNewsletter() {
    $newsletter = $this->clicks->getNewsletter($this->newsletter->id);
    expect(is_array($newsletter))->true();
    expect($newsletter['id'])->equals($this->newsletter->id);
  }

  function testItCanGetSubscriber() {
    $subscriber = $this->clicks->getSubscriber($this->subscriber->id);
    expect(is_array($subscriber))->true();
    expect($subscriber['id'])->equals($this->subscriber->id);
  }

  function testItCanGetQueue() {
    $queue = $this->clicks->getQueue($this->queue->id);
    expect(is_array($queue))->true();
    expect($queue['id'])->equals($this->queue->id);
  }

  function testItCanGetLink() {
    $link = $this->clicks->getLink($this->link->hash);
    expect(is_array($link))->true();
    expect($link['id'])->equals($this->link->id);
  }

  function testItTreatsUrlAsUrl() {
    $link = $this->clicks->processUrl(
      'http://example.com',
      (array) $this->newsletter,
      (array) $this->subscriber,
      (array) $this->queue
    );
    expect($link)->equals('http://example.com');
  }

  function testItConvertsShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:newsletter_view_in_browser_url]',
      (array) $this->newsletter,
      (array) $this->subscriber,
      (array) $this->queue
    );
    expect($link)->contains('&endpoint=view_in_browser');
  }

  function testItFailsToConvertsInvalidShortcodeToUrl() {
    $clicks = Stub::make(new Clicks(true), array(
      'abort' => Stub::exactly(1, function () { })
    ), $this);
    // should call abort() method if shortcode action does not exist
    $link = $clicks->processUrl(
      '[link:]',
      (array) $this->newsletter,
      (array) $this->subscriber,
      (array) $this->queue
    );
  }

  function testItFailsToConvertsNonexistentShortcodeToUrl() {
    $link = $this->clicks->processUrl(
      '[link:unknown_shortcode]',
      (array) $this->newsletter,
      (array) $this->subscriber,
      (array) $this->queue
    );
    expect($link)->equals('[link:unknown_shortcode]');
  }

  function testItAbortsWhenItCantFindData() {
    $clicks = Stub::make(new Clicks(true), array(
      'abort' => Stub::exactly(4, function () { }),
      'redirectToUrl' => function() { }
    ), $this);
    // should call abort() method when newsletter can't be found
    $data = array(
      'newsletter' => 999,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id,
      'hash' => $this->link->hash
    );
    $click = $clicks->track($data);
    // should call abort() method when subscriber can't be found
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => 999,
      'queue' => $this->queue->id,
      'hash' => $this->link->hash
    );
    $click = $clicks->track($data);
    // should call abort() method when queue can't be found
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => 999,
      'hash' => $this->link->hash
    );
    $click = $clicks->track($data);
    // should call abort() method when link can't be found
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' =>  $this->queue->id,
      'hash' => 999
    );
    $click = $clicks->track($data);
  }

  function testItShouldAlwaysTrackOpenEvent() {
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id,
      'hash' => $this->link->hash
    );
    $clicks = Stub::make(new Clicks(true), array(
      'redirectToUrl' => function() { }
    ), $this);
    $open_events = StatisticsOpens::findArray();
    expect(count($open_events))->equals(0);
    $click = $clicks->track($data);
    $open_events = StatisticsOpens::findArray();
    expect(count($open_events))->equals(1);
  }

  function testItTracksUniqueAndRepeatClickEvent() {
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id,
      'hash' => $this->link->hash
    );
    $clicks = Stub::make(new Clicks(true), array(
      'redirectToUrl' => function() { }
    ), $this);
    $click_events = StatisticsClicks::findArray();
    expect(count($click_events))->equals(0);
    // track unique click event
    $click = $clicks->track($data);
    $click_events = StatisticsClicks::findArray();
    expect(count($click_events))->equals(1);
    expect($click_events[0]['count'])->equals(1);
    // track repeat click event
    $click = $clicks->track($data);
    $click_events = StatisticsClicks::findArray();
    expect(count($click_events))->equals(1);
    expect($click_events[0]['count'])->equals(2);
  }

  function testItRedirectsAfterTracking() {
    $data = array(
      'newsletter' => $this->newsletter->id,
      'subscriber' => $this->subscriber->id,
      'queue' => $this->queue->id,
      'hash' => $this->link->hash
    );
    $clicks = Stub::make(new Clicks(true), array(
      'redirectToUrl' => Stub::exactly(1, function() { })
    ), $this);
    // should call redirectToUrl() method
    $click = $clicks->track($data);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
  }
}
