<?php

namespace MailPoet\API\JSON;
use MailPoet\WP\Functions as WPFunctions;

class ErrorResponse extends Response {
  public $errors;

  function __construct($errors = [], $meta = [], $status = self::STATUS_NOT_FOUND) {
    parent::__construct($status, $meta);
    $this->errors = $this->formatErrors($errors);
  }

  function getData() {
    return (empty($this->errors)) ? null : ['errors' => $this->errors];
  }

  function formatErrors($errors = []) {
    return array_map(function($error, $message) {
      // sanitize SQL error
      if (preg_match('/^SQLSTATE/i', $message)) {
        $message = WPFunctions::get()->__('An unknown error occurred.', 'mailpoet');
      }
      return [
        'error' => $error,
        'message' => $message,
      ];
    }, array_keys($errors), array_values($errors));
  }
}
