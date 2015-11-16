<?php

use MailPoet\Config\Env;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Export\Export;

class ExportCest {
  function __construct() {
    $this->JSONdata = json_decode(file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscribersData = array(
      'first_name' => array(
        'Adam',
        'Mary',
        'John',
        'Paul'
      ),
      'last_name' => array(
        'Smith',
        'Jane',
        'Kookoo',
        'Newman'
      ),
      'email' => array(
        'adam@smith.com',
        'mary@jane.com',
        'john@kookoo.com',
        'paul@newman.com'
      ),
      1 => array(
        'Brazil'
      )
    );
    $this->segments = array(
      array(
        'name' => 'Newspapers'
      ),
      array(
        'name' => 'Journals'
      )
    );
    $this->export = new Export($this->JSONdata);
  }

  function itCanConstruct() {
    expect($this->export->exportConfirmedOption)
      ->equals(false);
    expect($this->export->exportFormatOption)
      ->equals('csv');
    expect($this->export->groupBySegmentOption)
      ->equals(true);
    expect($this->export->segments)
      ->equals(array(0, 1));
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
        '|'.
        Env::$temp_path.'/MailPoet_export_[a-f0-9]{4}.'.
        $this->export->exportFormatOption .
        '|', $this->export->exportFile)
    )->equals(1);
    expect(
      preg_match(
        '|'.
        Env::$plugin_url . '/' .
        Env::$temp_name . '/' .
        basename($this->export->exportFile) .
        '|'
        , $this->export->exportFileURL)
    )->equals(1);
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