<?php
namespace MailPoet\API\JSON;

if (!defined('ABSPATH')) exit;

class SuccessResponse extends Response {
  public $data;

  function __construct($data = [], $meta = [], $status = self::STATUS_OK) {
    parent::__construct($status, $meta);
    $this->data = $data;
  }

  function getData() {
    if ($this->data === null) return null;

    return [
      'data' => $this->data,
    ];
  }
}
