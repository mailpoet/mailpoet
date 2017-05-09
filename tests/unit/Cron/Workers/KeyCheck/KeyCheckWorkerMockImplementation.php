<?php
namespace MailPoet\Cron\Workers\KeyCheck;

if(!defined('ABSPATH')) exit;

class MockKeyCheckWorker extends KeyCheckWorker {
  const TASK_TYPE = 'mock_key_check_worker';

  function checkKey() {
    return array('code' => 12345); // bogus code
  }
}
