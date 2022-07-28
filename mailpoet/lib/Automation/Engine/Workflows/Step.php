<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

interface Step {
  public function getKey(): string;

  public function getName(): string;
}
