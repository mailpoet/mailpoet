<?php
namespace MailPoet\Settings;

use MailPoet\Entities\UserFlagEntity;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsController {

  /** @var array|null */
  private $data = null;
  
  /** @var array */
  private $defaults;

  /** @var UserFlagsRepository */
  private $user_flags_repository;

  function __construct(UserFlagsRepository $user_flags_repository) {
    $this->defaults = [
      'last_announcement_seen' => false,
      'editor_tutorial_seen' => false,
    ];
    $this->user_flags_repository = $user_flags_repository;
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
    $flag = $this->user_flags_repository->findOneBy([
      'user_id' => $current_user_id,
      'name' => $name,
    ]);

    if (!$flag) {
      $flag = new UserFlagEntity();
      $flag->setUserId($current_user_id);
      $flag->setName($name);
      $this->user_flags_repository->persist($flag);
    }
    $flag->setValue($value);
    $this->user_flags_repository->flush();

    if ($this->isLoaded()) {
      $this->data[$name] = $value;
    }
  }

  function delete($name) {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    $flag = $this->user_flags_repository->findOneBy([
      'user_id' => $current_user_id,
      'name' => $name,
    ]);

    if (!$flag) {
      return;
    }

    $this->user_flags_repository->remove($flag);
    $this->user_flags_repository->flush();

    if ($this->isLoaded()) {
      unset($this->data[$name]);
    }
  }

  private function load() {
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    $flags = $this->user_flags_repository->findBy(['user_id' => $current_user_id]);
    $this->data = [];
    foreach ($flags as $flag) {
      $this->data[$flag->getName()] = $flag->getValue();
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
