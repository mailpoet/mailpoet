<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Exceptions\UnexpectedSubjectType;

interface SubjectFactory {
  public function canHandle(string $key): bool;

  /**
   * @param string $key
   * @return Subject
   * @throws UnexpectedSubjectType
   */
  public function forKey(string $key): Subject;
}
