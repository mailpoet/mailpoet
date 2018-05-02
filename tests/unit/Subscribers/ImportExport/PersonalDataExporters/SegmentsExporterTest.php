<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SegmentsExporterTest extends \MailPoetTest {

  /** @var SegmentsExporter */
  private $exporter;

  function _before() {
    $this->exporter = new SegmentsExporter();
  }

  function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result['data'])->equals(array());
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testExportWorksForSubscriberWithNoSegments() {
    Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.no.segments',
    ));
    $result = $this->exporter->export('email.that@has.no.segments');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result['data'])->equals(array());
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testExportWorksForSubscriberWithSegments() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.some.segments',
    ));
    $segment1 = Segment::createOrUpdate(array('name' => 'List 1'));
    $segment2 = Segment::createOrUpdate(array('name' => 'List 2'));
    SubscriberSegment::createOrUpdate(array(
      'subscriber_id' => $subscriber->id(),
      'segment_id' => $segment1->id(),
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'updated_at' => '2018-05-02 15:26:52',
    ));
    SubscriberSegment::createOrUpdate(array(
      'subscriber_id' => $subscriber->id(),
      'segment_id' => $segment2->id(),
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
      'updated_at' => '2018-05-02 15:26:00',
    ));
    $result = $this->exporter->export('email.that@has.some.segments');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result)->hasKey('done');
    $expected = array (
      array (
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment1->id(),
        'data' => array (
            array ('name' => 'List name', 'value' => 'List 1'),
            array ('name' => 'Subscription status', 'value' => 'subscribed'),
            array ('name' => 'Timestamp of the subscription (or last change of the subscription status)', 'value' => '2018-05-02 15:26:52'),
          ),
      ),
      array (
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment2->id(),
        'data' => array (
            array ('name' => 'List name', 'value' => 'List 2'),
            array ('name' => 'Subscription status', 'value' => 'unsubscribed'),
            array ('name' => 'Timestamp of the subscription (or last change of the subscription status)', 'value' => '2018-05-02 15:26:00'),
          ),
      ),
    );
    expect($result['data'])->internalType('array');
    expect($result['data'])->count(2);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    expect($result['data'][1])->hasKey('group_id');
    expect($result['data'][1])->hasKey('group_label');
    expect($result['data'][1])->hasKey('item_id');
    expect($result['data'][1])->hasKey('data');
    expect($result['data'])->equals($expected);
  }

}
