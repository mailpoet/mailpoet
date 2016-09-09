<?php

namespace MailPoet\Cron\Triggers;

class MockMethodWithException {
  static function run() {
    throw new \Exception('Exception thrown');
  }
}