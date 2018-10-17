<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Config\Populator;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;

class PopulatorTest extends \MailPoetTest {
  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    Setting::setValue('db_version', MAILPOET_VERSION);
  }
}