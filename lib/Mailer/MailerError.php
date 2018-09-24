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

  /** @var array */
  private $subscribers_errors = [];

  /**
   * @param string $operation
   * @param string $level
   * @param null|string $message
   * @param int|null $retry_interval
   * @param array $subscribers_errors
   */
  function __construct($operation, $level, $message = null, $retry_interval = null, array $subscribers_errors = []) {
    $this->operation = $operation;
    $this->level = $level;
    $this->message = $message;
    $this->retry_interval = $retry_interval;
    $this->subscribers_errors = $subscribers_errors;
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

  /**
   * @return SubscriberError[]
   */
  function getSubscriberErrors() {
    return $this->subscribers_errors;
  }

  /**
   * Temporary method until we implement UI for subscriber errors
   */
  function switchLevelToHard() {
    $this->level = self::LEVEL_HARD;
  }

  function getMessageWithFailedSubscribers() {
    $message = $this->message ?: '';
    if(!$this->subscribers_errors) {
      return $message;
    }

    $message .= $this->message ? ' ' : '';

    if(count($this->subscribers_errors) === 1) {
      $message .=  __('Unprocessed subscriber:', 'mailpoet') . ' ';
    } else {
      $message .=  __('Unprocessed subscribers:', 'mailpoet') . ' ';
    }

    $message .= implode(
      ', ',
      array_map(function (SubscriberError $subscriber_error) {
        return "($subscriber_error)";
      }, $this->subscribers_errors)
    );
    return $message;
  }
}
