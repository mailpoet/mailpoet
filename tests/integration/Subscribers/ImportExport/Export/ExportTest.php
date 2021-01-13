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
  public $export;
  public $segmentsData;
  public $customFieldsData;
  public $subscribersData;
  public $subscriberFields;
  public $jSONData;

  public function _before() {
    parent::_before();
    $this->jSONData = json_decode((string)file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
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
    $this->export = new Export($this->jSONData);
  }

  public function testItCanConstruct() {
    expect($this->export->exportFormatOption)
      ->equals('csv');
    expect($this->export->subscriberFields)
      ->equals(
        [
          'email',
          'first_name',
          '1',
        ]
      );
    expect($this->export->subscriberCustomFields)
      ->equals($this->export->getSubscriberCustomFields());
    expect($this->export->formattedSubscriberFields)
      ->equals(
        $this->export->formatSubscriberFields(
          $this->export->subscriberFields,
          $this->export->subscriberCustomFields
        )
      );
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$tempPath, '|') . '/MailPoet_export_[a-z0-9]{15}.' .
        $this->export->exportFormatOption .
        '|', $this->export->exportFile)
    )->equals(1);
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$tempUrl, '|') . '/' .
        basename($this->export->exportFile) .
        '|', $this->export->exportFileURL)
    )->equals(1);
  }

  public function testItCanGetSubscriberCustomFields() {
    $source = CustomField::where('name', $this->customFieldsData[0]['name'])
      ->findOne();
    assert($source instanceof CustomField);
    $target = $this->export->getSubscriberCustomFields();
    expect($target)->equals([$source->id => $source->name]);
  }

  public function testItCanFormatSubscriberFields() {
    $formattedSubscriberFields = $this->export->formatSubscriberFields(
      array_keys($this->subscriberFields),
      $this->export->getSubscriberCustomFields()
    );
    expect($formattedSubscriberFields)
      ->equals(array_values($this->subscriberFields));
  }

  public function testItProperlyReturnsSubscriberCustomFields() {
    $subscribers = $this->export->getSubscribers(0, 10);
    foreach ($subscribers as $subscriber) {
      if ($subscriber['email'] === $this->subscribersData[1]) {
        expect($subscriber['Country'])
          ->equals($this->subscribersData[1][1]);
      }
    }
  }

  public function testItCanGetSubscribers() {
    $this->export->defaultSubscribersGetter = new DefaultSubscribersGetter([1], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(2);

    $this->export->defaultSubscribersGetter = new DefaultSubscribersGetter([2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(2);

    $this->export->defaultSubscribersGetter = new DefaultSubscribersGetter([1, 2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(4);

  }

  public function testItAlwaysGroupsSubscribersBySegments() {
    $this->export->defaultSubscribersGetter = new DefaultSubscribersGetter([0, 1, 2], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(5);
  }

  public function testItCanGetSubscribersOnlyWithoutSegments() {
    $this->export->defaultSubscribersGetter = new DefaultSubscribersGetter([0], 100);
    $subscribers = $this->export->getSubscribers();
    expect($subscribers)->count(1);
    expect($subscribers[0]['segment_name'])->equals('Not In Segment');
  }

  public function testItRequiresWritableExportFile() {
    try {
      $this->export->exportPath = '/fake_folder';
      $this->export->process();
      $this->fail('Export did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())
        ->equals("The export file could not be saved on the server.");
    }
  }

  public function testItCanProcess() {
    try {
      $this->export->exportFile = $this->export->getExportFile('csv');
      $this->export->exportFormatOption = 'csv';
      $result = $this->export->process();
    } catch (\Exception $e) {
      $this->fail('Export to .csv process threw an exception');
    }
    expect($result['totalExported'])->equals(4);
    expect($result['exportFileURL'])->notEmpty();

    try {
      $this->export->exportFile = $this->export->getExportFile('xlsx');
      $this->export->exportFormatOption = 'xlsx';
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
