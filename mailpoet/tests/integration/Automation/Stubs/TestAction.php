<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Stubs;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Util\Security;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

class TestAction implements Action {

  private $subjectKeys = [];
  private $callback;
  private $key;

  public function __construct() {
    $this->key = Security::generateRandomString(10);
  }

  public function setCallback($callback) {
    $this->callback = $callback;
  }

  public function getSubjectKeys(): array {
    return $this->subjectKeys;
  }

  public function setSubjectKeys(string ...$subjectKeys): void {
    $this->subjectKeys = $subjectKeys;
  }

  public function validate(StepValidationArgs $args): void {
  }

  public function run(StepRunArgs $args): void {
    if ($this->callback) {
      ($this->callback)($args);
    }
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getName(): string {
    return 'Test Action';
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object();
  }
}
