<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\Subscriber;

class NewsletterClicksExporterTest extends \MailPoetTest {

  /** @var NewsletterClicksExporter */
  private $exporter;

  function _before() {
    parent::_before();
    $this->exporter = new NewsletterClicksExporter();
  }

  function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result['data'])->equals(array());
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testExportWorksForSubscriberWithNoNewsletters() {
    Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.no.newsletters',
    ));
    $result = $this->exporter->export('email.that@has.no.newsletters');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result['data'])->equals(array());
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testExportReturnsData() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'email@with.clicks',
    ));
    $queue = SendingQueue::createOrUpdate(array(
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => 1,
      'newsletter_id' => 8,
    ));
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD
    ));
    $link = NewsletterLink::createOrUpdate(array(
      'url' => 'Link url',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'hash' => 'xyz',
    ));
    StatisticsClicks::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'subscriber_id' => $subscriber->id(),
      'link_id' => $link->id(),
      'count' => 1,
      'created_at' => '2018-01-02 15:16:17',
    ));
    $result = $this->exporter->export('email@with.clicks');
    expect($result['data'])->internalType('array');
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    expect($result['data'][0]['data'])->contains(array('name' => 'Email subject', 'value' => 'Email Subject'));
    expect($result['data'][0]['data'])->contains(array('name' => 'URL', 'value' => 'Link url'));
    expect($result['data'][0]['data'])->contains(array('name' => 'Timestamp of the click event', 'value' => '2018-01-02 15:16:17'));
  }

}
