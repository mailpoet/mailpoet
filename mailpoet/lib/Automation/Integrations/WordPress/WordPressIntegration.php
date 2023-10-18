<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress;

use MailPoet\Automation\Engine\Integration;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;
use MailPoet\Automation\Integrations\WordPress\Subjects\UserSubject;

class WordPressIntegration implements Integration {
  /** @var UserSubject */
  private $userSubject;

  /** @var CommentSubject */
  private $commentSubject;

  /** @var ContextFactory */
  private $contextFactory;

  public function __construct(
    UserSubject $userSubject,
    CommentSubject $commentSubject,
    ContextFactory $contextFactory
  ) {
    $this->userSubject = $userSubject;
    $this->commentSubject = $commentSubject;
    $this->contextFactory = $contextFactory;
  }

  public function register(Registry $registry): void {
    $registry->addSubject($this->userSubject);
    $registry->addSubject($this->commentSubject);
    $registry->addContextFactory('wordpress', [$this->contextFactory, 'getContextData']);
  }
}
