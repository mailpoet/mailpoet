<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\Automation\Engine\Data\Automation as Entity;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;

class Automation {


  /**
   * @var AutomationStorage
   */
  private $storage;

  /**
   * @var Entity
   */
  private $automation;

  public function __construct() {
    $this->storage = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationStorage::class);
    $this->automation = new Entity(
      '', [
      'root' => new Step(
        'root',

        Step::TYPE_ROOT,
        'core:root',
        [],
        []
      ),
      ], new \WP_User());
  }

  public function withName($name) {
    $this->automation->setName($name);
    return $this;
  }

  public function withSteps(Step ...$steps) {
    $sortedSteps = [];
    foreach ($steps as $step) {
      $sortedSteps[$step->getId()] = $step;
    }
    $this->automation->setSteps($sortedSteps);
    return $this;
  }

  public function addStep(Step $step) {
    $steps = $this->automation->getSteps();
    $lastStep = end($steps);
    if (!$lastStep) {
      return $this->withSteps($step);
    }
    $lastStep->setNextSteps([new NextStep($step->getId())]);
    $steps[$step->getId()] = $step;
    return $this->withSteps(...$steps);
  }

  public function withDelayAction() {
    $step = new Step(
      uniqid(),
      Step::TYPE_ACTION,
      'core:delay',
      [
        'delay_type' => 'MINUTES',
        'delay' => 1,
      ],
      []
    );
    return $this->addStep($step);
  }

  public function withSomeoneSubscribesTrigger() {
    $step = new Step(
      uniqid(),
      Step::TYPE_TRIGGER,
      'mailpoet:someone-subscribes',
      [],
      []
    );
    return $this->addStep($step);
  }

  public function withMeta($key, $value) {
    $this->automation->setMeta($key, $value);
    return $this;
  }

  public function withStatus($status) {
    $this->automation->setStatus($status);
    return $this;
  }

  public function withStatusActive() {
    $this->automation->setStatus(Entity::STATUS_ACTIVE);
    return $this;
  }

  public function create() {
    $id = $this->storage->createAutomation($this->automation);
    $automation = $this->storage->getAutomation($id);
    if (!$automation) {
      throw new \Exception('Automation not found.');
    }
    $this->automation = $automation;
    return $this->automation;
  }
}
