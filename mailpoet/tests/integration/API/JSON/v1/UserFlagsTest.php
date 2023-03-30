<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\UserFlags;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Settings\UserFlagsRepository;

class UserFlagsTest extends \MailPoetTest {

  /** @var UserFlags */
  private $endpoint;

  /** @var UserFlagsController */
  private $userFlags;

  public function _before() {
    $this->cleanup();
    $this->userFlags = Stub::make(UserFlagsController::class, [
      'userFlagsRepository' => $this->diContainer->get(UserFlagsRepository::class),
      'defaults' => [
        'flag_1' => 'default_value_1',
        'flag_2' => 'default_value_2',
      ],
    ]);
    $this->userFlags->set('flag_1', 'value_1');
    $this->endpoint = new UserFlags($this->userFlags);
  }

  public function testItCanSetUserFlags() {
    $newFlags = [
      'flag_1' => 'new_value_1',
      'flag_3' => 'new_value_3',
    ];

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    expect($this->userFlags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
    ]);

    $response = $this->endpoint->set($newFlags);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($this->userFlags->getAll())->equals([
      'flag_1' => 'new_value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'new_value_3',
    ]);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    $tableName = $this->entityManager->getClassMetadata(UserFlagEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("TRUNCATE $tableName");
  }
}
