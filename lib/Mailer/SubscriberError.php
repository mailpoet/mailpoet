<?php
namespace MailPoet\Mailer;

class SubscriberError {

  /** @var string */
  private $email;

  /** @var string|null */
  private $message;

  /**
   * @param string $email
   * @param string $message|null
   */
  function __construct($email, $message = null) {
    $this->email = $email;
    $this->message = $message;
  }

  /**
   * @return string
   */
  function getEmail() {
    return $this->email;
  }

  /**
   * @return null|string
   */
  function getMessage() {
    return $this->message;
  }

  function __toString() {
    return $this->message ? $this->email . ': ' . $this->message : $this->email;
  }
}
