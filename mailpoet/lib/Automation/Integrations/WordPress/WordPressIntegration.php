<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress;

use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;

class WordPressIntegration {
  /** @var UserSubject */
  private $userSubject;

  public function __construct(
    UserSubject $userSubject
  ) {
    $this->userSubject = $userSubject;
  }

  public function register(Registry $registry): void {
    $registry->addSubject($this->userSubject);
  }
}
