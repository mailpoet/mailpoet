<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Subscriber extends \Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;
  function save () {
    if ($this->created_at === NULL) {
      $this->created_at = date("Y-m-d H:i:s");
    }
    parent::save();
  }
}
