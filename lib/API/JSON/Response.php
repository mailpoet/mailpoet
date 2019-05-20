<?php
namespace MailPoet\API\JSON;
use MailPoet\WP\Functions as WPFunctions;

abstract class Response {
  const STATUS_OK = 200;
  const STATUS_BAD_REQUEST = 400;
  const STATUS_UNAUTHORIZED = 401;
  const STATUS_FORBIDDEN = 403;
  const STATUS_NOT_FOUND = 404;
  const STATUS_CONFLICT = 409;
  const STATUS_UNKNOWN = 500;

  public $status;
  public $meta;

  function __construct($status, $meta = []) {
    $this->status = $status;
    $this->meta = $meta;
  }

  function send() {
    WPFunctions::get()->statusHeader($this->status);

    $data = $this->getData();
    $response = [];

    if (!empty($this->meta)) {
      $response['meta'] = $this->meta;
    }
    if ($data !== null) {
      $response = array_merge($response, $data);
    }

    if (!empty($response)) {
      @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
      echo WPFunctions::get()->wpJsonEncode($response);
    }
    die();
  }

  abstract function getData();
}
