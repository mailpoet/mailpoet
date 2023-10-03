<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress;

use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;

class WordPressIntegration {
  /** @var UserSubject */
  private $userSubject;

  /** @var CommentSubject */
  private $commentSubject;

  public function __construct(
    UserSubject $userSubject,
    CommentSubject $commentSubject
  ) {
    $this->userSubject = $userSubject;
    $this->commentSubject = $commentSubject;
  }

  public function register(Registry $registry): void {
    $registry->addSubject($this->userSubject);
    $registry->addSubject($this->commentSubject);
  }
}
