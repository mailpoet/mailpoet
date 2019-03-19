<?php
namespace MailPoet\Settings;

use MailPoet\Models\UserFlag;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsController {

  /**
   * @var array|null
   */
  private $data = null;
  
  /**
   * @var array
   */
  private $defaults;

  function __construct() {
    $this->defaults = [
      'last_announcement_seen' => false,
      'editor_tutorial_seen' => false,
    ];
  }

  function get($name) {
    $this->ensureLoaded();
    if (!isset($this->data[$name])) {
      return $this->defaults[$name];
    }
    return $this->data[$name];
  }

  function getAll() {
    $this->ensureLoaded();
    return array_merge($this->defaults, $this->data);
  }

  function set($name, $value) {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    UserFlag::createOrUpdate([
      'user_id' => $current_user_id,
      'name' => $name,
      'value' => $value
    ]);
    if ($this->isLoaded()) {
      $this->data[$name] = $value;
    }
  }

  function delete($name) {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    UserFlag::where('user_id', $current_user_id)
      ->where('name', $name)
      ->deleteMany();
    if ($this->isLoaded()) {
      unset($this->data[$name]);
    }
  }

  private function load() {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    $flags = UserFlag::where('user_id', $current_user_id)->findMany();
    $this->data = [];
    foreach ($flags as $flag) {
      $this->data[$flag->name] = $flag->value;
    }
  }

  private function isLoaded() {
    return $this->data !== null;
  }

  private function ensureLoaded() {
    if (!$this->isLoaded()) {
      $this->load();
    }
  }
}