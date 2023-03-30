<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;

class AutomationRunLogStorageTest extends \MailPoetTest {

  /** @var AutomationRunLogStorage */
  private $storage;

  public function _before() {
    $this->storage = $this->diContainer->get(AutomationRunLogStorage::class);
  }

  public function testItSavesAndRetrievesAsExpected() {
    $log = new AutomationRunLog(1, 'step-id');
    $log->setData('key', 'value');
    $log->setData('key2', ['arrayData']);
    $preSave = $log->toArray();
    $id = $this->storage->createAutomationRunLog($log);
    $fromDatabase = $this->storage->getAutomationRunLog($id);
    $this->assertInstanceOf(AutomationRunLog::class, $fromDatabase);
    expect($preSave)->equals($fromDatabase->toArray());
  }

  public function testItCanStoreAnError() {
    $log = new AutomationRunLog(1, 'step-id');
    $log->setError(new \Exception('test'));
    $id = $this->storage->createAutomationRunLog($log);
    $log = $this->storage->getAutomationRunLog($id);
    $this->assertInstanceOf(AutomationRunLog::class, $log);
    $errors = $log->getError();
    expect($errors)->array();
    expect(array_keys($errors))->equals([
      'message',
      'errorClass',
      'code',
      'trace',
    ]);
    expect($errors['trace'])->array();
    expect(count($errors['trace']))->greaterThan(0);
  }

  public function _after() {
    parent::_after();
    $this->storage->truncate();
  }
}
