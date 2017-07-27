<?php
namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;

if(!defined('ABSPATH')) exit;

class ShortcodesTest extends \MailPoetTest {
  function testItCanReplaceShortcodes() {
    $queue = $newsletter = (object)array(
      'id' => 1
    );
    $subscriber = (object)array(
      'email' => 'test@xample. com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    );
    $rendered_body = '[subscriber:firstname] [subscriber:lastname]';
    $result = Shortcodes::process($rendered_body, $newsletter, $subscriber, $queue);
    expect($result)->equals('John Doe');
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}