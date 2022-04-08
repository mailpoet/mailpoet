<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\Core\Actions\Wait\Action as WaitAction;

class CoreIntegration implements Integration {
  /** @var WaitAction */
  private $waitAction;

  public function __construct(
    WaitAction $waitAction
  ) {
    $this->waitAction = $waitAction;
  }

  public function register(Registry $registry): void {
    $registry->addAction($this->waitAction);
  }
}
