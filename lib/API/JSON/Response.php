<?php
namespace MailPoet\API\JSON;

if(!defined('ABSPATH')) exit;

abstract class Response {
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
    if($data !== null) {
      $response = array_merge($response, $data);
    }

    if(!empty($response)) {
      @header('Content-Type: application/json; charset='.get_option('blog_charset'));
      echo wp_json_encode($response);
    }
    die();
  }

  abstract function getData();
}