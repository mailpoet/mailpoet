<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use Exception;
use MailPoet\Automation\Engine\Data\Automation as AutomationData;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\IfElseAction;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use WP_User;

class Automation {
  /** @var AutomationStorage */
  private $storage;

  /** @var AutomationData */
  private $automation;

  /** @var array<string|int, Step> */
  private $stepSeqeuence;

  public function __construct() {
    $this->storage = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationStorage::class);
    $this->automation = new AutomationData('Test automation', [], new WP_User(1));
    $this->stepSeqeuence = ['root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [])];
  }

  public function withName($name) {
    $this->automation->setName($name);
    return $this;
  }

  /** @param Step[] $steps */
  public function withSteps(array $steps): self {
    $this->stepSeqeuence = [];
    foreach ($steps as $step) {
      $this->stepSeqeuence[$step->getId()] = $step;
    }
    return $this;
  }

  public function withStep(Step $step): self {
    $lastStep = end($this->stepSeqeuence);
    if ($lastStep) {
      $nextSteps = $lastStep->getNextSteps();
      // This is to preserve manually set else branch in if-else step
      $nextSteps[0] = new NextStep($step->getId());
      $lastStep->setNextSteps($nextSteps);
    }
    $this->stepSeqeuence[$step->getId()] = $step;
    return $this;
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

  public function withIfElseStep(Step $elseStep = null): self {
    $subscribedFilter = new Filter(
      'f1',
      'enum',
      'mailpoet:subscriber:status',
      'is-any-of',
      ['value' => ['subscribed']]
    );
    $step = new Step(
      uniqid(),
      Step::TYPE_ACTION,
      IfElseAction::KEY,
      [],
      [],
      new Filters(
        Filters::OPERATOR_AND,
        [new FilterGroup('g1', FilterGroup::OPERATOR_AND, [$subscribedFilter])]
      ),
    );
    $lastStep = end($this->stepSeqeuence);
    if ($lastStep) {
      $lastStep->setNextSteps([new NextStep($step->getId())]);
    }
    if ($elseStep) {
      $step->setNextSteps([
        new NextStep(null),
        new NextStep($elseStep->getId()),
      ]);
      $this->stepSeqeuence[$elseStep->getId()] = $elseStep;
    } else {
      $step->setNextSteps([
        new NextStep(null),
        new NextStep(null),
      ]);
    }
    $this->stepSeqeuence[$step->getId()] = $step;
    return $this;
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
    $this->automation->setSteps($this->stepSeqeuence);
    $id = $this->storage->createAutomation($this->automation);
    $automation = $this->storage->getAutomation($id);
    if (!$automation) {
      throw new Exception('Automation not found.');
    }
    $this->automation = $automation;
    return $this->automation;
  }

  /** @return array<string|int, Step> */
  public function getStepSequence(): array {
    return $this->stepSeqeuence;
  }
}
