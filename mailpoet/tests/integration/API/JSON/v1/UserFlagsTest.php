<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\UserFlags;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Settings\UserFlagsRepository;

class UserFlagsTest extends \MailPoetTest {

  /** @var UserFlags */
  private $endpoint;

  /** @var UserFlagsController */
  private $userFlags;

  public function _before() {
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
    verify($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    verify($this->userFlags->getAll())->equals([
      'flag_1' => 'value_1',
      'flag_2' => 'default_value_2',
    ]);

    $response = $this->endpoint->set($newFlags);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    verify($this->userFlags->getAll())->equals([
      'flag_1' => 'new_value_1',
      'flag_2' => 'default_value_2',
      'flag_3' => 'new_value_3',
    ]);
  }
}
