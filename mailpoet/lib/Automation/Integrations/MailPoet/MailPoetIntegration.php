<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;

class MailPoetIntegration implements Integration {
  /** @var SegmentSubscribedTrigger */
  private $segmentSubscribedTrigger;

  public function __construct(
    SegmentSubscribedTrigger $segmentSubscribedTrigger
  ) {
    $this->segmentSubscribedTrigger = $segmentSubscribedTrigger;
  }

  public function register(Registry $registry): void {
    $registry->addTrigger($this->segmentSubscribedTrigger);
  }
}
