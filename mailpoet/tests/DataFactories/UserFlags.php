<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsRepository;

class UserFlags {
  /** @var int */
  private $userId;

  /** @var UserFlagsRepository */
  private $userFlagsRepository;

  public function __construct(
    $userId
  ) {
    $this->userId = $userId;
    $this->userFlagsRepository = ContainerWrapper::getInstance()->get(UserFlagsRepository::class);
  }

  public function withDefaultFlags() {
    $this->withEditorTutorialSeen();
    $this->withFormEditorTutorialSeen();
  }

  public function withEditorTutorialSeen() {
    $this->withFlag('editor_tutorial_seen', 1);
    return $this;
  }

  public function withFormEditorTutorialSeen($value = 1) {
    $this->withFlag('form_editor_tutorial_seen', $value);
    return $this;
  }

  public function withFlag($name, $value) {
    $userFlag = $this->userFlagsRepository->findOneBy([
      'userId' => $this->userId,
      'name' => $name,
    ]);

    if (!$userFlag) {
      $userFlag = new UserFlagEntity();
      $userFlag->setUserId($this->userId);
      $userFlag->setName($name);
      $this->userFlagsRepository->persist($userFlag);
    }

    $userFlag->setValue($value);
    $this->userFlagsRepository->persist($userFlag);
    $this->userFlagsRepository->flush();
    return $this;
  }
}
