<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use Exception;
use MailPoet\Automation\Engine\Data\Automation as AutomationData;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use WP_User;

class Automation {
  /** @var AutomationStorage */
  private $storage;

  /** @var AutomationData */
  private $automation;

  public function __construct() {
    $this->storage = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationStorage::class);
    $this->automation = new AutomationData(
      'Test automation',
      ['root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [])],
      new WP_User(1)
    );
  }

  public function withName($name) {
    $this->automation->setName($name);
    return $this;
  }

  /** @param Step[] $steps */
  public function withSteps(array $steps): self {
    $stepMap = [];
    foreach ($steps as $step) {
      $stepMap[$step->getId()] = $step;
    }
    $this->automation->setSteps($stepMap);
    return $this;
  }

  public function withStep(Step $step): self {
    $steps = $this->automation->getSteps();
    $lastStep = end($steps);
    if (!$lastStep) {
      return $this->withSteps([$step]);
    }
    $lastStep->setNextSteps([new NextStep($step->getId())]);
    $steps[$step->getId()] = $step;
    return $this->withSteps(array_values($steps));
  }

  public function withDelayAction(): self {
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
    return $this->withStep($step);
  }

  public function withSendEmailStep(NewsletterEntity $newsletter): self {
    $step = new Step(
      uniqid(),
      Step::TYPE_ACTION,
      'mailpoet:send-email',
      [
        'email_id' => $newsletter->getId(),
        'subject' => $newsletter->getSubject(),
        'sender_name' => $newsletter->getSenderName(),
        'sender_address' => $newsletter->getSenderAddress(),
      ],
      []
    );
    return $this->withStep($step);
  }

  public function withSomeoneSubscribesTrigger(): self {
    $step = new Step(
      uniqid(),
      Step::TYPE_TRIGGER,
      'mailpoet:someone-subscribes',
      [],
      []
    );
    return $this->withStep($step);
  }

  public function withMeta($key, $value): self {
    $this->automation->setMeta($key, $value);
    return $this;
  }

  public function withStatus($status): self {
    $this->automation->setStatus($status);
    return $this;
  }

  public function withStatusActive(): self {
    $this->automation->setStatus(AutomationData::STATUS_ACTIVE);
    return $this;
  }

  public function withCreatedAt(\DateTimeImmutable $createdAt): self {
    $this->automation->setCreatedAt($createdAt);
    return $this;
  }

  public function create(): AutomationData {
    $id = $this->storage->createAutomation($this->automation);
    $automation = $this->storage->getAutomation($id);
    if (!$automation) {
      throw new Exception('Automation not found.');
    }
    $this->automation = $automation;
    return $this->automation;
  }
}
