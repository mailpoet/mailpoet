<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\UserFlag;

class UserFlagTest extends \MailPoetTest {
  
  function testItCanCreateOrUpdate() {
    expect(UserFlag::count())->equals(0);

    $created_flag = UserFlag::createOrUpdate([
      'user_id' => 3,
      'name' => 'first_flag',
      'value' => 'foo'
    ]);
    expect($created_flag->id > 0)->true();
    expect($created_flag->getErrors())->false();
    expect(UserFlag::count())->equals(1);

    $updated_flag = UserFlag::createOrUpdate([
      'user_id' => 3,
      'name' => 'first_flag',
      'value' => 'bar'
    ]);
    expect($updated_flag->id)->equals($created_flag->id);
    expect($updated_flag->value)->equals('bar');
    expect(UserFlag::count())->equals(1);

    $other_flag = UserFlag::createOrUpdate([
      'user_id' => 3,
      'name' => 'second_flag',
      'value' => 'bar'
    ]);
    expect($other_flag->id != $created_flag)->true();
    expect(UserFlag::count())->equals(2);
  }

  function _after() {
    UserFlag::deleteMany();
  }
}
