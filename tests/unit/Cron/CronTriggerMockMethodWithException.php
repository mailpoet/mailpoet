<?php

namespace MailPoet\Cron\Triggers;

class CronTriggerMockMethodWithException {
  static function run() {
    throw new \Exception('Exception thrown');
  }
}