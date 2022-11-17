<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\KeyCheck;

class KeyCheckWorkerMockImplementation extends KeyCheckWorker {
  const TASK_TYPE = 'mock_key_check_worker';

  public function checkKey() {
    return ['code' => 12345]; // bogus code
  }
}
