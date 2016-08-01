<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

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