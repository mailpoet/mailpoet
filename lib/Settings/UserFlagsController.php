<?php
namespace MailPoet\Settings;

use MailPoet\Models\UserFlag;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsController {

  /**
   * @var array|null
   */
  private static $data = null;

  public function getDefaults() {
    return [
      'last_announcement_seen' => false,
      'editor_tutorial_seen' => false,
    ];
  }

  public function load() {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    $flags = UserFlag::where('user_id', $current_user_id)->findMany();
    self::$data = [];
    foreach ($flags as $flag) {
      self::$data[$flag->name] = $flag->value;
    }
  }

  public function isLoaded() {
    return self::$data !== null;
  }

  public function ensureLoaded() {
    if (!$this->isLoaded()) {
      $this->load();
    }
  }

  public function get($name) {
    $this->ensureLoaded();
    if (empty(self::$data[$name])) {
      $defaults = $this->getDefaults();
      return $defaults[$name];
    }
    return self::$data[$name];
  }

  public function getAll() {
    $this->ensureLoaded();
    return array_merge($this->getDefaults(), self::$data);
  }

  public function set($name, $value) {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    UserFlag::createOrUpdate([
      'user_id' => $current_user_id,
      'name' => $name,
      'value' => $value
    ]);
    if ($this->isLoaded()) {
      self::$data[$name] = $value;
    }
  }

  public function delete($name) {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    UserFlag::where('user_id', $current_user_id)
      ->where('name', $name)
      ->deleteMany();
    if ($this->isLoaded()) {
      unset(self::$data[$name]);
    }
  }

  public static function clear() {
    self::$data = null;
  }
}