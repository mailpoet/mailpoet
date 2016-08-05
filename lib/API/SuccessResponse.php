<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

class SuccessResponse extends Response {
  public $data;

  function __construct($data = array(), $meta = array(), $status = self::STATUS_OK) {
    parent::__construct($status, $meta);
    $this->data = $data;
  }

  function getData() {
    return array(
      'data' => $this->data
    );
  }
}