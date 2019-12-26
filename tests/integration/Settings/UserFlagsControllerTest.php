<?php

namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Settings\UserFlagsRepository;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsControllerTest extends \MailPoetTest {

  /** @var UserFlagsController */
  private $user_flags;

  /** @var UserFlagsRepository */
  private $user_flags_repository;

  /** @var int */
  private $current_user_id;

  public function _before() {
    parent::_before();
    $this->cleanup();

    $current_user_id = 1;
    $other_user_id = 2;

    WPFunctions::set(Stub::make(new WPFunctions, [
      'getCurrentUserId' => $current_user_id,
    ]));
    $this->current_user_id = $current_user_id;
    $this->user_flags_repository = $this->di_container->get(UserFlagsRepository::class);
    $this->user_flags = Stub::make(UserFlagsController::class, [
      'user_flags_repository' => $this->user_flags_repository,
      'defaults' => [
        'flag_1' => 'default_value_1',
        'flag_2' => 'default_value_2',
        'flag_3' => 'default_value_3',
      ],
    ]);

    $this->createUserFlag($this->current_user_id, 'flag_1', 'value_1');
    $this->createUserFlag($this->current_user_id, 'flag_3', 'value_3');
    $this->createUserFlag($other_user_id, 'flag_1', 'other_value_1');
    $this->createUserFlag($other_user_id, 'flag_2', 'other_value_2');
  }

  public function testItGetsFlagsOfCurrentUser() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    expect($this->user_flags->get('flag_2'))->equals('default_value_2');
    expect($this->user_flags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'value_3',
    ]);
  }

  public function testItLoadsDataOnlyOnceWhenNeeded() {
    $this->updateUserFlag($this->current_user_id, 'flag_1', 'new_value_1');
    expect($this->user_flags->get('flag_1'))->equals('new_value_1');
    $this->updateUserFlag($this->current_user_id, 'flag_1', 'newer_value_1');
    expect($this->user_flags->get('flag_1'))->equals('new_value_1');
  }

  public function testItSetsNewFlagValue() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    $this->user_flags->set('flag_1', 'updated_value');
    expect($this->user_flags->get('flag_1'))->equals('updated_value');
    $flag = $this->user_flags_repository->findOneBy([
      'user_id' => $this->current_user_id,
      'name' => 'flag_1',
    ]);
    expect($flag->getValue())->equals('updated_value');
  }

  public function testItDeletesAFlag() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    $this->user_flags->delete('flag_1');
    expect($this->user_flags->get('flag_1'))->equals('default_value_1');
    $flag = $this->user_flags_repository->findOneBy([
      'user_id' => $this->current_user_id,
      'name' => 'flag_1',
    ]);
    expect($flag)->null();
  }

  public function _after() {
    $this->cleanup();
    WPFunctions::set(new WPFunctions);
  }

  private function createUserFlag($user_id, $name, $value) {
    $flag = new UserFlagEntity();
    $flag->setUserId($user_id);
    $flag->setName($name);
    $flag->setValue($value);
    $this->user_flags_repository->persist($flag);
    $this->user_flags_repository->flush();
    return $flag;
  }

  private function updateUserFlag($user_id, $name, $value) {
    $flag = $this->user_flags_repository->findOneBy([
      'user_id' => $user_id,
      'name' => $name,
    ]);
    if (!$flag) {
      throw new \Exception();
    }

    $flag->setValue($value);
    $this->user_flags_repository->flush();
    return $flag;
  }

  private function cleanup() {
    $table_name = $this->entity_manager->getClassMetadata(UserFlagEntity::class)->getTableName();
    $this->connection->executeUpdate("TRUNCATE $table_name");
  }
}
