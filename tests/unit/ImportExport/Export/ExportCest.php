<?php

use MailPoet\ImportExport\Export;

class ExportCest {
  function __construct() {
  }

  function itCanConstruct() {
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
  }
}