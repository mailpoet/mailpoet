<?php

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportCest {
  function __construct() {
    $this->JSONdata = json_decode(file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscribersData = array(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        1 => 'Brazil'
      ),
      array(
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com'
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
    $this->export = new Export($this->JSONdata);
  }

  /*  function _before() {

    }*/


  function itCanConstruct() {
    expect($this->export->exportConfirmedOption)
      ->equals(false);
    expect($this->export->exportFormatOption)
      ->equals('csv');
    expect($this->export->groupBySegmentOption)
      ->equals(true);
    expect($this->export->segments)
      ->equals(
        array(
          0,
          1
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

  function itCanGetSubscribers() {


  }

  function itCanGetSubscriberCustomFields() {
    $customFields = $this->export->getSubscriberCustomFields();
    expect($customFields)->equals(
      array(
        1 => $this->customFieldsData[0]['name']
      )
    );
  }

  function itCanFormatSubscriberFields() {
    $formattedSubscriberFields = $this->export->formatSubscriberFields(
      $this->subscriberFields,
      $this->export->getSubscriberCustomFields()
    );

    !d($formattedSubscriberFields);exit;

  }

  function itCanProcess() {
  }

  function _after() {
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
    ORM::forTable(Segment::$_table)
      ->deleteMany();
  }
}