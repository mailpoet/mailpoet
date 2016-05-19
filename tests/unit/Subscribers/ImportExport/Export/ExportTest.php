<?php

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportTest extends MailPoetTest {
  function _before() {
    $this->JSON_data = json_decode(file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscriber_fields = array(
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'email' => 'Email',
      1 => 'Country'
    );

    $this->subscribers_data = array(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com'
      ),
      array(
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => 'subscribed',
        1 => 'Brazil'
      ),
      array(
        'first_name' => 'John',
        'last_name' => 'Kookoo',
        'email' => 'john@kookoo.com'
      ),
      array(
        'first_name' => 'Paul',
        'last_name' => 'Newman',
        'email' => 'paul@newman.com'
      )
    );
    $this->custom_fields_data = array(
      array(
        'name' => 'Country',
        'type' => 'text'
      )
    );
    $this->segments_data = array(
      array(
        'name' => 'Newspapers'
      ),
      array(
        'name' => 'Journals'
      )
    );
    foreach($this->subscribers_data as $subscriber) {
      if(isset($subscriber[1])) {
        unset($subscriber[1]);
      }
      $entity = Subscriber::create();
      $entity->hydrate($subscriber);
      $entity->save();
    }
    foreach($this->segments_data as $segment) {
      $entity = Segment::create();
      $entity->hydrate($segment);
      $entity->save();
    }
    foreach($this->custom_fields_data as $custom_field) {
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

  function testItCanConstruct() {
    expect($this->export->export_confirmed_option)
      ->equals(false);
    expect($this->export->export_format_option)
      ->equals('csv');
    expect($this->export->group_by_segment_option)
      ->equals(false);
    expect($this->export->segments)
      ->equals(
        array(
          1,
          2
        )
      );
    expect($this->export->subscribers_without_segment)
      ->equals(0);
    expect($this->export->subscriber_fields)
      ->equals(
        array(
          'email',
          'first_name',
          '1'
        )
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
        Env::$temp_path . '/MailPoet_export_[a-f0-9]{4}.' .
        $this->export->export_format_option .
        '|', $this->export->export_file)
    )->equals(1);
    expect(
      preg_match(
        '|' .
        Env::$temp_url . '/' .
        basename($this->export->export_file) .
        '|'
        , $this->export->export_file_URL)
    )->equals(1);
    expect($this->export->subscriber_batch_size)->notNull();
  }

  function testItCanGetSubscriberCustomFields() {
    $source = CustomField::where('name', $this->custom_fields_data[0]['name'])
      ->findOne();
    $target = $this->export->getSubscriberCustomFields();
    expect($target)->equals(array($source->id => $source->name));
  }

  function testItCanFormatSubscriberFields() {
    $formatted_subscriber_fields = $this->export->formatSubscriberFields(
      array_keys($this->subscriber_fields),
      $this->export->getSubscriberCustomFields()
    );
    expect($formatted_subscriber_fields)
      ->equals(array_values($this->subscriber_fields));
  }

  function testItProperlyReturnsSubscriberCustomFields() {
    $subscribers = $this->export->getSubscribers(0, 10);
    foreach($subscribers as $subscriber) {
      if($subscriber['email'] === $this->subscribers_data[1]) {
        expect($subscriber['Country'])
          ->equals($this->subscribers_data[1][1]);
      }
    }
  }

  function testItCanGetSubscribers() {
    $this->export->segments = array(1);
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(2);
    $this->export->segments = array(2);
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(2);
    $this->export->segments = array(
      1,
      2
    );
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(3);
  }

  function testItCanGroupSubscribersBySegments() {
    $this->export->group_by_segment_option = true;
    $this->export->subscribers_without_segment = true;
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(5);
  }

  function testItCanGetSubscribersOnlyWithoutSegments() {
    $this->export->segments = array(0);
    $this->export->subscribers_without_segment = true;
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(1);
    expect($subscribers[0]['segment_name'])->equals('Not In Segment');
  }

  function testItCanGetOnlyConfirmedSubscribers() {
    $this->export->export_confirmed_option = true;
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(1);
    expect($subscribers[0]['email'])
      ->equals($this->subscribers_data[1]['email']);
  }

  function testItCanGetSubscribersOnlyInSegments() {
    SubscriberSegment::where('subscriber_id', 3)
      ->findOne()
      ->delete();
    $subscribers = $this->export->getSubscribers(0, 10);
    expect(count($subscribers))->equals(2);
  }

  function testItRequiresWritableExportFile() {
    $this->export->export_path = '/fake_folder';
    $result = $this->export->process();
    expect($result['errors'][0])
      ->equals("Couldn't save export file on the server.");
  }

  function testItCanProcess() {
    $this->export->export_file = $this->export->getExportFile('csv');
    $this->export->export_format_option = 'csv';
    $result = $this->export->process();
    expect($result['result'])->true();
    $this->export->export_file = $this->export->getExportFile('xlsx');
    $this->export->export_format_option = 'xlsx';
    $result = $this->export->process();
    expect($result['result'])->true();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}
