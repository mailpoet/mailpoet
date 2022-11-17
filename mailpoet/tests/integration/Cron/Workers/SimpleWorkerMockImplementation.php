<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

class SimpleWorkerMockImplementation extends SimpleWorker {
  const TASK_TYPE = 'mock_simple_worker';
  const SUPPORT_MULTIPLE_INSTANCES = false;

  public function init() {
    // to be mocked
  }
}
