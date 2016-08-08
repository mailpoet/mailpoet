<?php
namespace MailPoet\API;

if(!defined('ABSPATH')) exit;

class SuccessResponse extends Response {
  public $data;

  function __construct($data = null, $meta = null, $status = self::STATUS_OK) {
    parent::__construct($status, $meta);
    $this->data = $data;
  }

  function getData() {
    if($this->data === null) return null;

    return array(
      'data' => $this->data
    );
  }
}