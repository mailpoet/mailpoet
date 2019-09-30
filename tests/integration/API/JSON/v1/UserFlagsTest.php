<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\UserFlags;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Settings\UserFlagsRepository;

class UserFlagsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var UserFlagsController */
  private $user_flags;

  function _before() {
    $this->cleanup();
    $this->user_flags = Stub::make(UserFlagsController::class, [
      'user_flags_repository' => $this->di_container->get(UserFlagsRepository::class),
      'defaults' => [
        'flag_1' => 'default_value_1',
        'flag_2' => 'default_value_2',
      ],
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
    $this->cleanup();
  }

  private function cleanup() {
    $table_name = $this->entity_manager->getClassMetadata(UserFlagEntity::class)->getTableName();
    $this->entity_manager->getConnection()->executeUpdate("TRUNCATE $table_name");
  }
}
