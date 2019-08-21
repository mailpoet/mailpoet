<?php

namespace MailPoet\Test\DataFactories;

use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\UserFlagEntity;

class UserFlags {
  /** @var int */
  private $user_id;

  /** @var EntityManager */
  private $entity_manager;

  function __construct($user_id) {
    $this->user_id = $user_id;
    $this->entity_manager = ContainerWrapper::getInstance()->get(EntityManager::class);
  }

  function withDefaultFlags() {
    $this->withEditorTutorialSeen();
  }

  function withEditorTutorialSeen() {
    $this->withFlag('editor_tutorial_seen', 1);
    return $this;
  }

  function withFlag($name, $value) {
    $user_flag = new UserFlagEntity();
    $user_flag->setUserId($this->user_id);
    $user_flag->setName($name);
    $user_flag->setValue($value);
    $this->entity_manager->persist($user_flag);
    $this->entity_manager->flush();
    return $this;
  }
}
