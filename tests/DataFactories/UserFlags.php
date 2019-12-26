<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsRepository;

class UserFlags {
  /** @var int */
  private $user_id;

  /** @var UserFlagsRepository */
  private $user_flags_repository;

  public function __construct($user_id) {
    $this->user_id = $user_id;
    $this->user_flags_repository = ContainerWrapper::getInstance()->get(UserFlagsRepository::class);
  }

  public function withDefaultFlags() {
    $this->withEditorTutorialSeen();
  }

  public function withEditorTutorialSeen() {
    $this->withFlag('editor_tutorial_seen', 1);
    return $this;
  }

  public function withFlag($name, $value) {
    $user_flag = $this->user_flags_repository->findOneBy([
      'user_id' => $this->user_id,
      'name' => $name,
    ]);

    if (!$user_flag) {
      $user_flag = new UserFlagEntity();
      $user_flag->setUserId($this->user_id);
      $user_flag->setName($name);
      $this->user_flags_repository->persist($user_flag);
    }

    $user_flag->setValue($value);
    $this->user_flags_repository->persist($user_flag);
    $this->user_flags_repository->flush();
    return $this;
  }
}
