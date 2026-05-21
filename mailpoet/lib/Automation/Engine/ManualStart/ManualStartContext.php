<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\ManualStart;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Entities\SegmentEntity;

class ManualStartContext {
  /** @var Automation */
  private $automation;

  /** @var Step */
  private $triggerStep;

  /** @var SegmentEntity */
  private $segment;

  /** @var SegmentEntity|null */
  private $filterSegment;

  public function __construct(
    Automation $automation,
    Step $triggerStep,
    SegmentEntity $segment,
    ?SegmentEntity $filterSegment
  ) {
    $this->automation = $automation;
    $this->triggerStep = $triggerStep;
    $this->segment = $segment;
    $this->filterSegment = $filterSegment;
  }

  public function getAutomation(): Automation {
    return $this->automation;
  }

  public function getTriggerStep(): Step {
    return $this->triggerStep;
  }

  public function getSegment(): SegmentEntity {
    return $this->segment;
  }

  public function getFilterSegment(): ?SegmentEntity {
    return $this->filterSegment;
  }

  public function getSegmentId(): int {
    return (int)$this->segment->getId();
  }

  public function getFilterSegmentId(): ?int {
    return $this->filterSegment ? (int)$this->filterSegment->getId() : null;
  }
}
