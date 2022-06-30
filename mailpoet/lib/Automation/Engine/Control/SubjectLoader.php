<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Workflows\Subject;
use Throwable;

class SubjectLoader {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function loadSubject(string $key, array $args): Subject {
    $subject = $this->registry->getSubject($key);
    if (!$subject) {
      throw Exceptions::subjectNotFound($key);
    }

    try {
      $subject->load($args);
    } catch (Throwable $e) {
      throw Exceptions::subjectLoadFailed($key, $args);
    }
    return $subject;
  }
}
