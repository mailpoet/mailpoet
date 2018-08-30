<?php
namespace MailPoet\Mailer;

class MailerError {
  const OPERATION_CONNECT = 'connect';
  const OPERATION_SEND = 'send';

  const LEVEL_HARD = 'hard';
  const LEVEL_SOFT = 'soft';

  /** @var string */
  private $operation;

  /** @var string */
  private $level;

  /** @var string|null */
  private $message;

  /** @var int|null */
  private $retry_interval;

  /**
   * @param string $operation
   * @param string $level
   * @param null|string $message
   * @param int|null $retry_interval
   */
  function __construct($operation, $level, $message = null, $retry_interval = null) {
    $this->operation = $operation;
    $this->level = $level;
    $this->message = $message;
    $this->retry_interval = $retry_interval;
  }

  /**
   * @return string
   */
  function getOperation() {
    return $this->operation;
  }

  /**
   * @return string
   */
  function getLevel() {
    return $this->level;
  }

  /**
   * @return null|string
   */
  function getMessage() {
    return $this->message;
  }

  /**
   * @return int|null
   */
  function getRetryInterval() {
    return $this->retry_interval;
  }
}
