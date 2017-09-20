<?php

namespace MailPoet\API\JSON;

if(!defined('ABSPATH')) exit;

class ErrorResponse extends Response {
  public $errors;

  function __construct($errors = array(), $meta = array(), $status = self::STATUS_NOT_FOUND) {
    parent::__construct($status, $meta);
    $this->errors = $this->formatErrors($errors);
  }

  function getData() {
    return (empty($this->errors)) ? null : array('errors' => $this->errors);
  }

  function formatErrors($errors = array()) {
    return array_map(function($error, $message) {
      // sanitize SQL error
      if(preg_match('/^SQLSTATE/i', $message)) {
        $message = __('An unknown error occurred.', 'mailpoet');
      }
      return array(
        'error' => $error,
        'message' => $message
      );
    }, array_keys($errors), array_values($errors));
  }
}