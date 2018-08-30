<?php
namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\Bridge\API;

class MailPoetMapperTest extends \MailPoetTest {
  /** @var MailPoetMapper */
  private $mapper;

  /** @var array */
  private $subscribers;

  function _before() {
    $this->mapper = new MailPoetMapper();
    $this->subscribers = ['a@example.com', 'c d <b@example.com>'];
  }

  function testCreateConnectionError() {
    $error = $this->mapper->getConnectionError('connection error');
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_CONNECT);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('connection error');
  }

  function testGetErrorNotArray() {
    $api_result = [
      'code' => API::RESPONSE_CODE_NOT_ARRAY,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error not array',
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('JSON input is not an array');
  }

  function testGetErrorPayloadTooBig() {
    $api_result = [
      'code' => API::RESPONSE_CODE_PAYLOAD_TOO_BIG,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error too big',
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('error too big');
  }

  function testGetPayloadError() {
    $api_result = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'Api Error',
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Error while sending newsletters. Api Error');
  }

  function testGetPayloadErrorWithErrorMessage() {
    $api_result = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"index":0,"errors":{"subject":"subject is missing"}},{"index":1,"errors":{"subject":"subject is missing"}}]'
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Error while sending: (a@example.com: subject is missing), (c d <b@example.com>: subject is missing)');
  }
}
