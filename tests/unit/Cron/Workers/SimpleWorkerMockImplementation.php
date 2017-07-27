<?php
namespace MailPoet\Cron\Workers;

if(!defined('ABSPATH')) exit;

class SimpleWorkerMockImplementation extends SimpleWorker {
  const TASK_TYPE = 'mock_simple_worker';

  function init() {
    // to be mocked
  }
}
