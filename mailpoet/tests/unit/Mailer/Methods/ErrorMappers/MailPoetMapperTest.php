<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use Codeception\Stub;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;

class MailPoetMapperTest extends \MailPoetUnitTest {
  /** @var MailPoetMapper */
  private $mapper;

  /** @var array */
  private $subscribers;

  public function _before() {
    parent::_before();
    $wpFunctions = Stub::make(new WPFunctions, [
      '_x' => function ($value) {
        return $value;
      },
      'escAttr' => function ($value) {
        return $value;
      },
    ]);
    $this->mapper = new MailPoetMapper(
      Stub::make(Bridge::class),
      Stub::make(ServicesChecker::class),
      Stub::make(SettingsController::class),
      Stub::make(Subscribers::class),
      $wpFunctions
    );
    $this->subscribers = ['a@example.com', 'c d <b@example.com>'];
  }

  public function testCreateBlacklistError() {
    $error = $this->mapper->getBlacklistError($this->subscribers[1]);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
    expect($error->getMessage())->stringContainsString('unknown error');
    expect($error->getMessage())->stringContainsString('MailPoet');
  }

  public function testCreateConnectionError() {
    $error = $this->mapper->getConnectionError('connection error');
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_CONNECT);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('connection error');
  }

  public function testGetErrorNotArray() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_NOT_ARRAY,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error not array',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('JSON input is not an array');
  }

  public function testGetErrorBannedAccount() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_BANNED,
      'error' => API::ERROR_MESSAGE_BANNED,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->stringContainsString('MailPoet Sending Service has been temporarily suspended for your site due to');
  }

  public function testGetErrorInsufficientPrivileges(): void {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES,
      'error' => API::ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_INSUFFICIENT_PRIVILEGES);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->stringContainsString('You have reached the subscriber limit of your plan.');
  }

  public function testGetErrorUnauthorizedEmail() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_INVALID_FROM,
      'error' => API::ERROR_MESSAGE_INVALID_FROM,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_AUTHORIZATION);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->stringContainsString('Sending all of your emails has been paused');
    expect($error->getMessage())->stringContainsString('because your email address');
  }

  public function testGetErrorPayloadTooBig() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_TOO_BIG,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error too big',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('error too big');
  }

  public function testGetPayloadError() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'Api Error',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Error while sending. Api Error');
  }

  public function testGetPayloadErrorWithErrorMessage() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"index":0,"errors":{"subject":"subject is missing"}},{"index":1,"errors":{"subject":"subject is missing"}}]',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
    $subscriberErrors = $error->getSubscriberErrors();
    expect(count($subscriberErrors))->equals(2);
    expect($subscriberErrors[0]->getEmail())->equals('a@example.com');
    expect($subscriberErrors[0]->getMessage())->equals('subject is missing');
    expect($subscriberErrors[1]->getEmail())->equals('c d <b@example.com>');
    expect($subscriberErrors[1]->getMessage())->equals('subject is missing');
  }

  public function testGetPayloadErrorForMalformedMSSResponse() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"errors":{"subject":"subject is missing"}},{"errors":{"subject":"subject is missing"}}]',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Error while sending. Invalid MSS response format.');
  }

  public function testGetPendingApprovalMessage() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_PENDING_APPROVAL,
      'error' => API::ERROR_MESSAGE_PENDING_APPROVAL,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_PENDING_APPROVAL);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->stringContainsString('pending approval');
    expect($error->getMessage())->stringContainsString("You’ll soon be able to send once our team reviews your account.");
  }

  public function testGetUnavailableServiceError() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_GATEWAY_TIMEOUT,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'Service is temporary unavailable',
    ];

    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getRetryInterval())->equals(MailPoetMapper::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Email service is temporarily not available, please try again in a few minutes.');
  }
}
