<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Captcha\Validator\ValidationError;

/**
 * A ValidationError, so the visitor message reaches the form wherever it is thrown.
 */
class CaptchaSessionLimitException extends ValidationError {
  public function __construct() {
    parent::__construct(__('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.', 'mailpoet'));
  }
}
