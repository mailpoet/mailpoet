<?php

namespace MailPoet\Test\Subscribers\ImportExport\Export;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\DefaultSubscribersGetter;
use MailPoetVendor\Idiorm\ORM;

class DefaultSubscribersGetterTest extends \MailPoetTest {
  public $segmentsData;
  public $customFieldsData;
  public $subscribersData;
  public $subscriberFields;

  public function _before() {
    parent::_before();
    $this->subscriberFields = [
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'email' => 'Email',
      1 => 'Country',
    ];

    $this->subscribersData = [
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
      ],
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        1 => 'Brazil',
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Kookoo',
        'email' => 'john@kookoo.com',
      ],
      [
        'first_name' => 'Paul',
        'last_name' => 'Newman',
        'email' => 'paul@newman.com',
      ],
    ];

    $this->customFieldsData = [
      [
        'name' => 'Country',
        'type' => 'text',
      ],
    ];

    $this->segmentsData = [
      [
        'name' => 'Newspapers',
      ],
      [
        'name' => 'Journals',
      ],
    ];

    foreach ($this->subscribersData as $subscriber) {
      if (isset($subscriber[1])) {
        unset($subscriber[1]);
      }
      $entity = Subscriber::create();
      $entity->hydrate($subscriber);
      $entity->save();
    }

    foreach ($this->segmentsData as $segment) {
      $entity = Segment::create();
      $entity->hydrate($segment);
      $entity->save();
    }

    foreach ($this->customFieldsData as $customField) {
      $entity = CustomField::create();
      $entity->hydrate($customField);
      $entity->save();
    }

    $entity = SubscriberCustomField::create();
    $entity->subscriberId = 2;
    $entity->customFieldId = 1;
    $entity->value = $this->subscribersData[1][1];
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriberId = 1;
    $entity->segmentId = 1;
    $entity->status = Subscriber::STATUS_UNSUBSCRIBED;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriberId = 1;
    $entity->segmentId = 2;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriberId = 2;
    $entity->segmentId = 1;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriberId = 3;
    $entity->segmentId = 2;
    $entity->save();
  }

  protected function filterSubscribersData($subscribers) {
    return array_map(function($subscriber) {
      $data = [];
      foreach ($subscriber as $key => $value) {
        if (in_array($key, [
          'first_name', 'last_name', 'email', 'global_status',
          'status', 'list_status', 'segment_name', 1,
        ]))
          $data[$key] = $value;
      }
      return $data;
    }, $subscribers);
  }

  public function testItGetsSubscribersInOneSegment() {
    $getter = new DefaultSubscribersGetter([1], 10);
    $subscribers = $getter->get();
    expect($this->filterSubscribersData($subscribers))->equals([
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_UNSUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => null,
      ],
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'global_status' => Subscriber::STATUS_SUBSCRIBED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => 'Brazil',
      ],
    ]);

    expect($getter->get())->equals(false);
  }

  public function testItGetsSubscribersInMultipleSegments() {
    $getter = new DefaultSubscribersGetter([1, 2], 10);
    $subscribers = $getter->get();
    expect($this->filterSubscribersData($subscribers))->equals([
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_UNSUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => null,
      ],
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Journals',
        1 => null,
      ],
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'global_status' => Subscriber::STATUS_SUBSCRIBED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => 'Brazil',
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Kookoo',
        'email' => 'john@kookoo.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Journals',
        1 => null,
      ],
    ]);

    expect($getter->get())->equals(false);
  }

  public function testItGetsSubscribersInBatches() {
    $getter = new DefaultSubscribersGetter([1, 2], 2);
    expect($this->filterSubscribersData($getter->get()))->equals([
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_UNSUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => null,
      ],
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Journals',
        1 => null,
      ],
    ]);

    expect($this->filterSubscribersData($getter->get()))->equals([
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => Subscriber::STATUS_SUBSCRIBED,
        'global_status' => Subscriber::STATUS_SUBSCRIBED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Newspapers',
        1 => 'Brazil',
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Kookoo',
        'email' => 'john@kookoo.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => Subscriber::STATUS_SUBSCRIBED,
        'segment_name' => 'Journals',
        1 => null,
      ],
    ]);

    expect($getter->get())->equals([]);
    expect($getter->get())->equals(false);
  }

  public function testItGetsSubscribersWithoutSegment() {
    $getter = new DefaultSubscribersGetter([0], 10);
    $subscribers = $getter->get();
    expect($this->filterSubscribersData($subscribers))->equals([
      [
        'first_name' => 'Paul',
        'last_name' => 'Newman',
        'email' => 'paul@newman.com',
        'status' => Subscriber::STATUS_UNCONFIRMED,
        'global_status' => Subscriber::STATUS_UNCONFIRMED,
        'list_status' => null,
        'segment_name' => 'Not In Segment',
        1 => null,
      ],
    ]);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}
