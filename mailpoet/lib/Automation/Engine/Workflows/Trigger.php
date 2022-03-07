<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface Trigger {
  public function getKey(): string;

  public function getName(): string;

  public function registerHooks(): void;
}
