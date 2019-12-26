<?php

namespace MailPoet\Test\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\DefaultSubscribersGetter;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoetVendor\Idiorm\ORM;

class ExportTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->JSON_data = json_decode(file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscriber_fields = [
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'email' => 'Email',
      1 => 'Country',
    ];
    $this->subscribers_data = [
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
    $this->custom_fields_data = [
      [
        'name' => 'Country',
        'type' => 'text',
      ],
    ];
    $this->segments_data = [
      [
        'name' => 'Newspapers',
      ],
      [
        'name' => 'Journals',
      ],
    ];
    foreach ($this->subscribers_data as $subscriber) {
      if (isset($subscriber[1])) {
        unset($subscriber[1]);
      }
      $entity = Subscriber::create();
      $entity->hydrate($subscriber);
      $entity->save();
    }
    foreach ($this->segments_data as $segment) {
      $entity = Segment::create();
      $entity->hydrate($segment);
      $entity->save();
    }
    foreach ($this->custom_fields_data as $custom_field) {
      $entity = CustomField::create();
      $entity->hydrate($custom_field);
      $entity->save();
    }
    $entity = SubscriberCustomField::create();
    $entity->subscriber_id = 2;
    $entity->custom_field_id = 1;
    $entity->value = $this->subscribers_data[1][1];
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriber_id = 1;
    $entity->segment_id = 1;
    $entity->status = Subscriber::STATUS_UNSUBSCRIBED;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriber_id = 1;
    $entity->segment_id = 2;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriber_id = 2;
    $entity->segment_id = 1;
    $entity->save();
    $entity = SubscriberSegment::create();
    $entity->subscriber_id = 3;
    $entity->segment_id = 2;
    $entity->save();
    $this->export = new Export($this->JSON_data);
  }

  public function testItCanConstruct() {
    expect($this->export->export_format_option)
      ->equals('csv');
    expect($this->export->subscriber_fields)
      ->equals(
        [
          'email',
          'first_name',
          '1',
        ]
      );
    expect($this->export->subscriber_custom_fields)
      ->equals($this->export->getSubscriberCustomFields());
    expect($this->export->formatted_subscriber_fields)
      ->equals(
        $this->export->formatSubscriberFields(
          $this->export->subscriber_fields,
          $this->export->subscriber_custom_fields
        )
      );
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$temp_path, '|') . '/MailPoet_export_[a-z0-9]{15}.' .
        $this->export->export_format_option .
        '|', $this->export->export_file)
    )->equals(1);
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$temp_url, '|') . '/' .
        basename($this->export->export_file) .
        '|', $this->export->export_file_URL)
    )->equals(1);
  }

  public function testItCanGetSubscriberCustomFields() {
    $source = CustomField::where('name', $this->custom_fields_data[0]['name'])
      ->findOne();
    $target = $this->export->getSubscriberCustomFields();
    expect($target)->equals([$source->id => $source->name]);
  }

  public function testItCanFormatSubscriberFields() {
    $formatted_subscriber_fields = $this->export->formatSubscriberFields(
      array_keys($this->subscriber_fields),
      $this->export->getSubscriberCustomFields()
    );
    expect($formatted_subscriber_fields)
      ->equals(array_values($this->subscriber_fields));
  }

  public function testItProperlyReturnsSubscriberCustomFields() {
    $subscribers = $this->export->getSubscribers(0, 10);
    foreach ($subscribers as $subscriber) {
      if ($subscriber['email'] === $this->subscribers_data[1]) {
        expect($subscriber['Country'])
          ->equals($this->subscribers_data[1][1]);
      }
    }
  }

  public function testItCanGetSubscribers() {
    $this->export->default_subscribers_getter = new DefaultSubscribersGetter([1], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(2);

    $this->export->default_subscribers_getter = new DefaultSubscribersGetter([2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(2);

    $this->export->default_subscribers_getter = new DefaultSubscribersGetter([1, 2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(4);

  }

  public function testItAlwaysGroupsSubscribersBySegments() {
    $this->export->default_subscribers_getter = new DefaultSubscribersGetter([0, 1, 2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(5);
  }

  public function testItCanGetSubscribersOnlyWithoutSegments() {
    $this->export->default_subscribers_getter = new DefaultSubscribersGetter([0], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(1);
    expect($subscribers[0]['segment_name'])->equals('Not In Segment');
  }

  public function testItRequiresWritableExportFile() {
    try {
      $this->export->export_path = '/fake_folder';
      $this->export->process();
      $this->fail('Export did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())
        ->equals("The export file could not be saved on the server.");
    }
  }

  public function testItCanProcess() {
    try {
      $this->export->export_file = $this->export->getExportFile('csv');
      $this->export->export_format_option = 'csv';
      $result = $this->export->process();
    } catch (\Exception $e) {
      $this->fail('Export to .csv process threw an exception');
    }
    expect($result['totalExported'])->equals(4);
    expect($result['exportFileURL'])->notEmpty();

    try {
      $this->export->export_file = $this->export->getExportFile('xlsx');
      $this->export->export_format_option = 'xlsx';
      $result = $this->export->process();
    } catch (\Exception $e) {
      $this->fail('Export to .xlsx process threw an exception');
    }
    expect($result['totalExported'])->equals(4);
    expect($result['exportFileURL'])->notEmpty();
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}
