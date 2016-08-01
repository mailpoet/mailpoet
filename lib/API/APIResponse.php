<?php
namespace MailPoet\API;
abstract class APIResponse {
  const STATUS_OK = 200;
  const STATUS_BAD_REQUEST = 400;
  const STATUS_UNAUTHORIZED = 401;
  const STATUS_FORBIDDEN = 403;
  const STATUS_NOT_FOUND = 404;

  public $status;
  public $meta;

  function __construct($status, $meta = array()) {
    $this->status = $status;
    $this->meta = $meta;
  }

  function send() {
    status_header($this->status);

    $data = $this->getData();
    $response = array();

    if(!empty($this->meta)) {
      $response['meta'] = $this->meta;
    }
    if(!empty($data)) {
      $response = array_merge($response, $data);
    }

    if(empty($response)) {
      die();
    } else {
      wp_send_json($response);
    }
  }

  abstract function getData();
}

class SuccessResponse extends APIResponse {
  public $data;

  function __construct($data = array(), $meta = array(), $status = self::STATUS_OK) {
    parent::__construct($status, $meta);
    $this->data = $data;
  }

  function getData() {
    if(empty($this->data)) {
      return false;
    } else {
      return array(
        'data' => $this->data
      );
    }
  }
}

class ErrorResponse extends APIResponse {
  public $errors;

  function __construct($errors = array(), $meta = array(), $status = self::STATUS_NOT_FOUND) {
    parent::__construct($status, $meta);
    $this->errors = $this->formatErrors($errors);
  }

  function getData() {
    if(empty($this->errors)) {
      return false;
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