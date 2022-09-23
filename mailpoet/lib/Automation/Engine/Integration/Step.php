<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Validator\Schema\ObjectSchema;

interface Step {
  public function getKey(): string;

  public function getName(): string;

  public function getArgsSchema(): ObjectSchema;

  /** @return string[] */
  public function getSubjectKeys(): array;
}
