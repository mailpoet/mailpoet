<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation;

require_once __DIR__ . '/../Test.php';

use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\REST\Test;

abstract class AutomationTest extends Test {
  public function _before() {
    parent::_before();
    $migrator = $this->diContainer->get(Migrator::class);
    if ($migrator->hasSchema()) {
      $migrator->deleteSchema();
    }
    $migrator->createSchema();
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }
}
