<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\MailPoetSubjectFactory;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;

class MailPoetIntegration implements Integration {
  /** @var SegmentSubscribedTrigger */
  private $segmentSubscribedTrigger;

  /** @var SendWelcomeEmailAction */
  private $sendWelcomeEmailAction;

  /** @var MailPoetSubjectFactory */
  private $subjectFactory;

  public function __construct(
    SegmentSubscribedTrigger $segmentSubscribedTrigger,
    SendWelcomeEmailAction $sendWelcomeEmailAction,
    MailPoetSubjectFactory $subjectFactory
  ) {
    $this->segmentSubscribedTrigger = $segmentSubscribedTrigger;
    $this->sendWelcomeEmailAction = $sendWelcomeEmailAction;
    $this->subjectFactory = $subjectFactory;
  }

  public function register(Registry $registry): void {
    $registry->addTrigger($this->segmentSubscribedTrigger);
    $registry->addAction($this->sendWelcomeEmailAction);
    $registry->addSubjectFactory($this->subjectFactory);
  }
}
