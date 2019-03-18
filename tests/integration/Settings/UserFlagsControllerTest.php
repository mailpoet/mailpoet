<?php
namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Models\UserFlag;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsControllerTest extends \MailPoetTest {

  /** @var UserFlagsController */
  private $user_flags;

  /** @var int */
  private $current_user_id;

  /** @var int */
  private $other_user_id;

  function _before() {
    parent::_before();
    
    $current_user_id = 1;
    $other_user_id = 2;
    WPFunctions::set(Stub::make(new WPFunctions, [
      'getCurrentUserId' => $current_user_id,
    ]));
    $this->current_user_id = $current_user_id;
    $this->user_flags = Stub::make(new UserFlagsController(), [
      'defaults' => [
        'flag_1' => 'default_value_1',
        'flag_2' => 'default_value_2',
        'flag_3' => 'default_value_3',
      ],
    ]);
    UserFlag::createOrUpdate([
      'user_id' => $this->current_user_id,
      'name' => 'flag_1',
      'value' => 'value_1',
    ]);
    UserFlag::createOrUpdate([
      'user_id' => $this->current_user_id,
      'name' => 'flag_3',
      'value' => 'value_3',
    ]);
    UserFlag::createOrUpdate([
      'user_id' => $other_user_id,
      'name' => 'flag_1',
      'value' => 'other_value_1',
    ]);
    UserFlag::createOrUpdate([
      'user_id' => $other_user_id,
      'name' => 'flag_2',
      'value' => 'other_value_2',
    ]);
  }

  function testItGetsFlagsOfCurrentUser() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    expect($this->user_flags->get('flag_2'))->equals('default_value_2');
    expect($this->user_flags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'value_3',
    ]);
  }

  function testItLoadsDataOnlyOnceWhenNeeded() {
    UserFlag::createOrUpdate([
      'user_id' => $this->current_user_id,
      'name' => 'flag_1',
      'value' => 'new_value_1',
    ]);
    expect($this->user_flags->get('flag_1'))->equals('new_value_1');
    UserFlag::createOrUpdate([
      'user_id' => $this->current_user_id,
      'name' => 'flag_1',
      'value' => 'newer_value_1',
    ]);
    expect($this->user_flags->get('flag_1'))->equals('new_value_1');
  }

  function testItSetsNewFlagValue() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    $this->user_flags->set('flag_1', 'updated_value');
    expect($this->user_flags->get('flag_1'))->equals('updated_value');
    $flag = UserFlag::where('user_id', $this->current_user_id)
      ->where('name', 'flag_1')
      ->findOne();
    expect($flag->value)->equals('updated_value');
  }

  function testItDeletesAFlag() {
    expect($this->user_flags->get('flag_1'))->equals('value_1');
    $this->user_flags->delete('flag_1');
    expect($this->user_flags->get('flag_1'))->equals('default_value_1');
    $flag = UserFlag::where('user_id', $this->current_user_id)
      ->where('name', 'flag_1')
      ->findOne();
    expect($flag)->equals(false);
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
    \ORM::raw_execute('TRUNCATE ' . UserFlag::$_table);
  }
}
