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
    if(empty($this->errors)) {
      return null;
    } else {
      return array(
        'errors' => $this->errors
      );
    }
  }

  function formatErrors($errors = array()) {
    $formatted_errors = array();
    foreach($errors as $error => $message) {
      $formatted_errors[] = array(
        'error' => $error,
        'message' => $message
      );
    }
    return $formatted_errors;
  }
}