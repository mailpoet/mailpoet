<?php

namespace MailPoet\Mailer;

use MailPoet\WP\Functions as WPFunctions;

class MailerError {
  const OPERATION_CONNECT = 'connect';
  const OPERATION_SEND = 'send';
  const OPERATION_AUTHORIZATION = 'authorization';
  const OPERATION_INSUFFICIENT_PRIVILEGES = 'insufficient_privileges';

  const LEVEL_HARD = 'hard';
  const LEVEL_SOFT = 'soft';

  const MESSAGE_EMAIL_FORBIDDEN_ACTION = 'Key is valid, but the action is forbidden';
  const MESSAGE_EMAIL_INSUFFICIENT_PRIVILEGES = 'Insufficient privileges';
  const MESSAGE_EMAIL_NOT_AUTHORIZED = 'The email address is not authorized';

  /** @var string */
  private $operation;

  /** @var string */
  private $level;

  /** @var string|null */
  private $message;

  /** @var int|null */
  private $retryInterval;

  /** @var array */
  private $subscribersErrors = [];

  /**
   * @param string $operation
   * @param string $level
   * @param null|string $message
   * @param int|null $retryInterval
   * @param array $subscribersErrors
   */
  public function __construct(
    $operation,
    $level,
    $message = null,
    $retryInterval = null,
    array $subscribersErrors = []
  ) {
    $this->operation = $operation;
    $this->level = $level;
    $this->message = $message;
    $this->retryInterval = $retryInterval;
    $this->subscribersErrors = $subscribersErrors;
  }

  /**
   * @return string
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * @return string
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * @return null|string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @return int|null
   */
  public function getRetryInterval() {
    return $this->retryInterval;
  }

  /**
   * @return SubscriberError[]
   */
  public function getSubscriberErrors() {
    return $this->subscribersErrors;
  }

  public function getMessageWithFailedSubscribers() {
    $message = $this->message ?: '';
    if (!$this->subscribersErrors) {
      return $message;
    }

    $message .= $this->message ? ' ' : '';

    if (count($this->subscribersErrors) === 1) {
      $message .= WPFunctions::get()->__('Unprocessed subscriber:', 'mailpoet') . ' ';
    } else {
      $message .= WPFunctions::get()->__('Unprocessed subscribers:', 'mailpoet') . ' ';
    }

    $message .= implode(
      ', ',
      array_map(function (SubscriberError $subscriberError) {
        return "($subscriberError)";
      }, $this->subscribersErrors)
    );
    return $message;
  }
}
