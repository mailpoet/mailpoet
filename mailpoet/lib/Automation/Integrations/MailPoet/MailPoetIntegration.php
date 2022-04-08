<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmail\Action as SendWelcomeEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;

class MailPoetIntegration implements Integration {
  /** @var SegmentSubscribedTrigger */
  private $segmentSubscribedTrigger;
  
  /** @var SendWelcomeEmailAction */
  private $sendWelcomeEmailAction;
  
  public function __construct(
    SegmentSubscribedTrigger $segmentSubscribedTrigger, 
    SendWelcomeEmailAction $sendWelcomeEmailAction
  ) {
    $this->segmentSubscribedTrigger = $segmentSubscribedTrigger;
    $this->sendWelcomeEmailAction = $sendWelcomeEmailAction;
  }

  public function register(Registry $registry): void {
    $registry->addTrigger($this->segmentSubscribedTrigger);
    $registry->addAction($this->sendWelcomeEmailAction);
  }
}
