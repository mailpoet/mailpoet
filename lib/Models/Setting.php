<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Setting extends \Model {
  public static $_table = MP_SETTINGS_TABLE;

  function save() {
    if ($this->created_at === NULL) {
      $this->created_at = date("Y-m-d H:i:s");
    }
    parent::save();
  }
}
