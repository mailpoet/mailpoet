<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\Core\Triggers\EmptyTrigger;

class CoreIntegration implements Integration {
  /** @var DelayAction */
  private $delayAction;

  /** @var EmptyTrigger */
  private $emptyTrigger;

  public function __construct(
    DelayAction $delayAction,
    EmptyTrigger $emptyTrigger
  ) {
    $this->delayAction = $delayAction;
    $this->emptyTrigger = $emptyTrigger;
  }

  public function register(Registry $registry): void {
    $registry->addAction($this->delayAction);
    $registry->addTrigger($this->emptyTrigger);
  }
}
