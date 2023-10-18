<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\DateTime;

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
    verify($result)->arrayHasKey('data');
    verify($result['data'])->equals([]);
    verify($result)->arrayHasKey('done');
    verify($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithNoSegments() {
    (new SubscriberFactory())->withEmail('email.that@has.no.segments')->create();
    $result = $this->exporter->export('email.that@has.no.segments');
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result['data'])->equals([]);
    verify($result)->arrayHasKey('done');
    verify($result['done'])->equals(true);
  }

  public function testExportWorksForSubscriberWithSegments() {
    $subscriber = (new SubscriberFactory())->create();

    $segment1 = (new SegmentFactory())->withName('List 1')->create();
    $segment2 = (new SegmentFactory())->withName('List 2')->create();

    $ss1 = new SubscriberSegmentEntity($segment1, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($ss1);
    $this->entityManager->flush();

    $ss2 = new SubscriberSegmentEntity($segment2, $subscriber, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($ss2);
    $this->entityManager->flush();

    /**
     * Make Doctrine update SubscriberSegment collections
     */
    $this->entityManager->refresh($subscriber);

    $result = $this->exporter->export($subscriber->getEmail());
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result)->arrayHasKey('done');
    $expected = [
       [
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment1->getId(),
        'data' => [
             ['name' => 'List name', 'value' => 'List 1'],
             ['name' => 'Subscription status', 'value' => 'subscribed'],
             [
               'name' => 'Timestamp of the subscription (or last change of the subscription status)',
               'value' => $ss1->getUpdatedAt()->format(DateTime::DEFAULT_DATE_TIME_FORMAT),
             ],
          ],
       ],
       [
        'group_id' => 'mailpoet-lists',
        'group_label' => 'MailPoet Mailing Lists',
        'item_id' => 'list-' . $segment2->getId(),
        'data' => [
             ['name' => 'List name', 'value' => 'List 2'],
             ['name' => 'Subscription status', 'value' => 'unsubscribed'],
             [
               'name' => 'Timestamp of the subscription (or last change of the subscription status)',
               'value' => $ss2->getUpdatedAt()->format(DateTime::DEFAULT_DATE_TIME_FORMAT),
             ],
          ],
       ],
    ];
    expect($result['data'])->array();
    expect($result['data'])->count(2);
    verify($result['done'])->equals(true);
    verify($result['data'][0])->arrayHasKey('group_id');
    verify($result['data'][0])->arrayHasKey('group_label');
    verify($result['data'][0])->arrayHasKey('item_id');
    verify($result['data'][0])->arrayHasKey('data');
    verify($result['data'][1])->arrayHasKey('group_id');
    verify($result['data'][1])->arrayHasKey('group_label');
    verify($result['data'][1])->arrayHasKey('item_id');
    verify($result['data'][1])->arrayHasKey('data');
    verify($result['data'])->equals($expected);
  }
}
