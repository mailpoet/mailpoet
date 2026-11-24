<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use MailPoet\Mailer\MailerError;

class SendPreviewException extends \RuntimeException {
  /** @var MailerError|null */
  private $mailerError;

  public function __construct(
    string $message = '',
    ?MailerError $mailerError = null
  ) {
    parent::__construct($message);
    $this->mailerError = $mailerError;
  }

  public function getMailerError(): ?MailerError {
    return $this->mailerError;
  }
}
