<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

class NewslettersExporterTest extends \MailPoetTest {

  /** @var NewslettersExporter */
  private $exporter;

  function _before() {
    parent::_before();
    $this->exporter = new NewslettersExporter();
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

  function testExportReturnsRenderedSubjects() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'user@with.newsletters',
    ));
    $queue = SendingQueue::createOrUpdate(array(
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => 1,
      'newsletter_id' => 8,
    ));
    StatisticsNewsletters::createMultiple(array(array(
      'newsletter_id' => 8,
      'subscriber_id' => $subscriber->id(),
      'queue_id' => $queue->id(),
    )));
    $result = $this->exporter->export('user@with.newsletters');
    expect($result['data'])->internalType('array');
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    expect($result['data'][0]['data'])->contains(array('name' => 'Email subject', 'value' => 'Email Subject'));
  }

  function testExportReturnsUrl() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'user1@with.newsletters',
    ));
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD
    ));
    $queue = SendingQueue::createOrUpdate(array(
      'newsletter_rendered_subject' => 'Email Subject1',
      'task_id' => 2,
      'newsletter_id' => $newsletter->id(),
    ));
    StatisticsNewsletters::createMultiple(array(array(
      'newsletter_id' => $newsletter->id(),
      'subscriber_id' => $subscriber->id(),
      'queue_id' => $queue->id(),
    )));
    $result = $this->exporter->export('user1@with.newsletters');
    expect($result['data'][0]['data'][3]['name'])->equals('Email preview');
    expect($result['data'][0]['data'][3]['value'])->contains('mailpoet_router&endpoint=view_in_browser&action=view&data=');
  }

  function testExportOpens() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'user21@with.newsletters',
    ));
    $subscriber2 = Subscriber::createOrUpdate(array(
      'email' => 'user22@with.newsletters',
    ));
    $newsletter1 = Newsletter::createOrUpdate(array(
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD
    ));
    $newsletter2 = Newsletter::createOrUpdate(array(
      'subject' => 'Email Subject2',
      'type' => Newsletter::TYPE_STANDARD
    ));
    $queue1 = SendingQueue::createOrUpdate(array(
      'newsletter_rendered_subject' => 'Email Subject1',
      'task_id' => 2,
      'newsletter_id' => $newsletter1->id(),
    ));
    $queue2 = SendingQueue::createOrUpdate(array(
      'newsletter_rendered_subject' => 'Email Subject1',
      'task_id' => 2,
      'newsletter_id' => $newsletter1->id(),
    ));
    StatisticsNewsletters::createMultiple(array(array(
      'newsletter_id' => $newsletter1->id(),
      'subscriber_id' => $subscriber->id(),
      'queue_id' => $queue1->id(),
    ), array(
        'newsletter_id' => $newsletter1->id(),
        'subscriber_id' => $subscriber2->id(),
        'queue_id' => $queue1->id(),
    )));

    StatisticsNewsletters::createMultiple(array(array(
      'newsletter_id' => $newsletter2->id(),
      'subscriber_id' => $subscriber->id(),
      'queue_id' => $queue2->id(),
    )));
    StatisticsOpens::createOrUpdate(array(
      'subscriber_id' => $subscriber->id(),
      'newsletter_id' => $newsletter1->id(),
      'queue_id' => $queue1->id(),
      'created_at' => '2017-01-02 12:23:45',
    ));
    StatisticsOpens::createOrUpdate(array(
      'subscriber_id' => $subscriber2->id(),
      'newsletter_id' => $newsletter1->id(),
      'queue_id' => $queue1->id(),
      'created_at' => '2017-01-02 21:23:45',
    ));
    $result = $this->exporter->export('user21@with.newsletters');
    expect(count($result['data']))->equals(2);
    expect($result['data'][0]['data'])->contains(array('name' => 'Opened', 'value' => 'Yes'));
    expect($result['data'][0]['data'])->contains(array('name' => 'Opened at', 'value' => '2017-01-02 12:23:45'));
    expect($result['data'][1]['data'])->contains(array('name' => 'Opened', 'value' => 'No'));
  }
}
