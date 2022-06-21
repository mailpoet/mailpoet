<?php

namespace MailPoet\Subscription\Captcha\Validator;

class ValidationError extends \RuntimeException {


  private $meta = [];

  public function __construct(
    $message = "",
    array $meta = [],
    $code = 0,
    \Throwable $previous = null
  ) {
    $this->meta = $meta;
    $this->meta['error'] = $message;
    parent::__construct($message, $code, $previous);
  }

  public function getMeta(): array {
    return $this->meta;
  }
}
