<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\SubscribersRepository;

class SegmentsExporterTest extends \MailPoetTest {

  /** @var SegmentsExporter */
  private $exporter;

  public function _before() {
    parent::_before();
    $this->exporter = new SegmentsExporter(
      $this->diContainer->get(SubscribersRepository::class)
    );
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithNoSegments() {
    Subscriber::createOrUpdate([
      'email' => 'email.that@has.no.segments',
    ]);
    $result = $this->exporter->export('email.that@has.no.segments');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithSegments() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'email.that@has.some.segments',
    ]);
    $segment1 = Segment::createOrUpdate(['name' => 'List 1']);
    $segment2 = Segment::createOrUpdate(['name' => 'List 2']);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id(),
      'segment_id' => $segment1->id(),
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'updated_at' => '2018-05-02 15:26:52',
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id(),
      'segment_id' => $segment2->id(),
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
      'updated_at' => '2018-05-02 15:26:00',
    ]);
    $result = $this->exporter->export('email.that@has.some.segments');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result)->hasKey('done');
    $expected = [
       [
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment1->id(),
        'data' => [
             ['name' => 'List name', 'value' => 'List 1'],
             ['name' => 'Subscription status', 'value' => 'subscribed'],
             ['name' => 'Timestamp of the subscription (or last change of the subscription status)', 'value' => '2018-05-02 15:26:52'],
          ],
       ],
       [
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment2->id(),
        'data' => [
             ['name' => 'List name', 'value' => 'List 2'],
             ['name' => 'Subscription status', 'value' => 'unsubscribed'],
             ['name' => 'Timestamp of the subscription (or last change of the subscription status)', 'value' => '2018-05-02 15:26:00'],
          ],
       ],
    ];
    expect($result['data'])->array();
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
