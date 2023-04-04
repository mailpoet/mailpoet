<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Settings\UserFlagsRepository;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsControllerTest extends \MailPoetTest {

  /** @var UserFlagsController */
  private $userFlags;

  /** @var UserFlagsRepository */
  private $userFlagsRepository;

  /** @var int */
  private $currentUserId;

  public function _before() {
    parent::_before();

    $currentUserId = 1;
    $otherUserId = 2;

    WPFunctions::set(Stub::make(new WPFunctions, [
      'getCurrentUserId' => $currentUserId,
    ]));
    $this->currentUserId = $currentUserId;
    $this->userFlagsRepository = $this->diContainer->get(UserFlagsRepository::class);
    $this->userFlags = Stub::make(UserFlagsController::class, [
      'userFlagsRepository' => $this->userFlagsRepository,
      'defaults' => [
        'flag_1' => 'default_value_1',
        'flag_2' => 'default_value_2',
        'flag_3' => 'default_value_3',
      ],
    ]);

    $this->createUserFlag($this->currentUserId, 'flag_1', 'value_1');
    $this->createUserFlag($this->currentUserId, 'flag_3', 'value_3');
    $this->createUserFlag($otherUserId, 'flag_1', 'other_value_1');
    $this->createUserFlag($otherUserId, 'flag_2', 'other_value_2');
  }

  public function testItGetsFlagsOfCurrentUser() {
    expect($this->userFlags->get('flag_1'))->equals('value_1');
    expect($this->userFlags->get('flag_2'))->equals('default_value_2');
    expect($this->userFlags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'value_3',
    ]);
  }

  public function testItLoadsDataOnlyOnceWhenNeeded() {
    $this->updateUserFlag($this->currentUserId, 'flag_1', 'new_value_1');
    expect($this->userFlags->get('flag_1'))->equals('new_value_1');
    $this->updateUserFlag($this->currentUserId, 'flag_1', 'newer_value_1');
    expect($this->userFlags->get('flag_1'))->equals('new_value_1');
  }

  public function testItSetsNewFlagValue() {
    expect($this->userFlags->get('flag_1'))->equals('value_1');
    $this->userFlags->set('flag_1', 'updated_value');
    expect($this->userFlags->get('flag_1'))->equals('updated_value');
    $flag = $this->userFlagsRepository->findOneBy([
      'userId' => $this->currentUserId,
      'name' => 'flag_1',
    ]);
    $this->assertInstanceOf(UserFlagEntity::class, $flag);
    expect($flag->getValue())->equals('updated_value');
  }

  public function testItDeletesAFlag() {
    expect($this->userFlags->get('flag_1'))->equals('value_1');
    $this->userFlags->delete('flag_1');
    expect($this->userFlags->get('flag_1'))->equals('default_value_1');
    $flag = $this->userFlagsRepository->findOneBy([
      'userId' => $this->currentUserId,
      'name' => 'flag_1',
    ]);
    expect($flag)->null();
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }

  private function createUserFlag($userId, $name, $value) {
    $flag = new UserFlagEntity();
    $flag->setUserId($userId);
    $flag->setName($name);
    $flag->setValue($value);
    $this->userFlagsRepository->persist($flag);
    $this->userFlagsRepository->flush();
    return $flag;
  }

  private function updateUserFlag($userId, $name, $value) {
    $flag = $this->userFlagsRepository->findOneBy([
      'userId' => $userId,
      'name' => $name,
    ]);
    if (!$flag) {
      throw new \Exception();
    }

    $flag->setValue($value);
    $this->userFlagsRepository->flush();
    return $flag;
  }
}
