<?php
namespace MailPoet\Queue;

use MailPoet\Models\Setting;

class BootStrapMenu {
  function __construct() {
    $this->daemon = Setting::where('name', 'daemon')
      ->findOne()
      ->asArray();
  }

  function bootStrap() {
    return ($this->daemon) ?
      array_merge(
        array(
          'started_at' => $this->daemon['created_at'],
          'updated_at' => $this->daemon['updated_at']
        ),
        json_decode($this->daemon['value'], true)
      ) :
      "false";
  }
}