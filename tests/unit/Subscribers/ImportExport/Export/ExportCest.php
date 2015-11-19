<?php

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportCest {
  function _before() {
    $this->JSONdata = json_decode(file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscriberFields = array(
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'email' => 'Email',
      1 => 'Country'
    );

    $this->subscribersData = array(
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
    $this->customFieldsData = array(
      array(
        'name' => 'Country',
        'type' => 'text'
      )
    );
    $this->segmentsData = array(
      array(
        'name' => 'Newspapers'
      ),
      array(
        'name' => 'Journals'
      )
    );
    foreach ($this->subscribersData as $subscriber) {
      if(isset($subscriber[1])) {
        unset($subscriber[1]);
      }
      $entity = Subscriber::create();
      $entity->hydrate($subscriber);
      $entity->save();
    }
    foreach ($this->segmentsData as $customField) {
      $entity = Segment::create();
      $entity->hydrate($customField);
      $entity->save();
    }
    foreach ($this->customFieldsData as $customField) {
      $entity = CustomField::create();
      $entity->hydrate($customField);
      $entity->save();
    }
    $entity = SubscriberCustomField::create();
    $entity->subscriber_id = 2;
    $entity->custom_field_id = 1;
    $entity->value = $this->subscribersData[1][1];
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
    $this->export = new Export($this->JSONdata);
  }

  function itCanConstruct() {
    expect($this->export->exportConfirmedOption)
      ->equals(false);
    expect($this->export->exportFormatOption)
      ->equals('csv');
    expect($this->export->groupBySegmentOption)
      ->equals(false);
    expect($this->export->segments)
      ->equals(
        array(
          1,
          2
        )
      );
    expect($this->export->subscribersWithoutSegment)
      ->equals(0);
    expect($this->export->subscriberFields)
      ->equals(
        array(
          'email',
          'first_name',
          '1'
        )
      );
    expect(
      preg_match(
        '|' .
        Env::$temp_path . '/MailPoet_export_[a-f0-9]{4}.' .
        $this->export->exportFormatOption .
        '|', $this->export->exportFile)
    )->equals(1);
    expect(
      preg_match(
        '|' .
        Env::$plugin_url . '/' .
        Env::$temp_name . '/' .
        basename($this->export->exportFile) .
        '|'
        , $this->export->exportFileURL)
    )->equals(1);
  }

  function itCanGetSubscriberCustomFields() {
    $source = CustomField::where('name', $this->customFieldsData[0]['name'])
      ->findOne();
    $target = $this->export->getSubscriberCustomFields();
    expect($target)->equals(array($source->id => $source->name));
  }

  function itCanFormatSubscriberFields() {
    $formattedSubscriberFields = $this->export->formatSubscriberFields(
      array_keys($this->subscriberFields),
      $this->export->getSubscriberCustomFields()
    );
    expect($formattedSubscriberFields)
      ->equals(array_values($this->subscriberFields));
  }

  function itProperlyReturnsSubscriberCustomFields() {
    $subscribers = $this->export->getSubscribers();
    foreach ($subscribers as $subscriber) {
      if($subscriber['email'] === $this->subscribersData[1]) {
        expect($subscriber['Country'])
          ->equals($this->subscribersData[1][1]);
      }
    }
  }

  function itCanGetSubscribers() {
    $this->export->segments = array(1);
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(2);
    $this->export->segments = array(2);
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(2);
    $this->export->segments = array(
      1,
      2
    );
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(3);
  }

  function itCanGroupSubscribersBySegments() {
    $this->export->groupBySegmentOption = true;
    $this->export->subscribersWithoutSegment = true;
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(5);
  }

  function itCanGetSubscribersOnlyWithoutSegments() {
    $this->export->segments = array(0);
    $this->export->subscribersWithoutSegment = true;
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(1);
    expect($subscribers[0]['segment_name'])->equals('Not In List');
  }

  function itCanGetOnlyConfirmedSubscribers() {
    $this->export->exportConfirmedOption = true;
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(1);
    expect($subscribers[0]['email'])
      ->equals($this->subscribersData[1]['email']);
  }

  function itCanGetSubscribersOnlyInSegments() {
    SubscriberSegment::where('subscriber_id', 3)
      ->findOne()
      ->delete();
    $subscribers = $this->export->getSubscribers();
    expect(count($subscribers))->equals(2);
  }

  function itCanProcess() {
    $this->export->exportFile = $this->export->getExportFile('csv');
    $this->export->exportFormatOption = 'csv';
    $this->export->process();
    $CSVFileSize = filesize($this->export->exportFile);
    $this->export->exportFile = $this->export->getExportFile('xls');
    $this->export->exportFormatOption = 'xls';
    $this->export->process();
    $XLSFileSize = filesize($this->export->exportFile);
    expect($CSVFileSize)->greaterThan(0);
    expect($XLSFileSize)->greaterThan(0);
    expect($XLSFileSize)->greaterThan($CSVFileSize);

  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}