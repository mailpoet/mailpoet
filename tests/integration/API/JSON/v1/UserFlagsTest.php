<?php
namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\v1\UserFlags;
use MailPoet\Models\UserFlag;
use MailPoet\Settings\UserFlags as UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class UserFlagsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var UserFlagsController */
  private $user_flags;

  function _before() {
    parent::_before();
    $this->user_flags = Stub::make(new UserFlagsController, [
      'getDefaults' => function() {
        return [
          'flag_1' => 'default_value_1',
          'flag_2' => 'default_value_2',
        ];
      },
    ]);    
    $this->user_flags->set('flag_1', 'value_1');
    $this->endpoint = new UserFlags($this->user_flags);
  }

  function testItCanSetUserFlags() {
    $new_flags = [
      'flag_1' => 'new_value_1',
      'flag_3' => 'new_value_3',
    ];

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    expect($this->user_flags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
    ]);

    $response = $this->endpoint->set($new_flags);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($this->user_flags->getAll())->equals([
      'flag_1' => 'new_value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'new_value_3',
    ]);
  }

  function _after() {
    \ORM::forTable(UserFlag::$_table)->deleteMany();
  }
}
