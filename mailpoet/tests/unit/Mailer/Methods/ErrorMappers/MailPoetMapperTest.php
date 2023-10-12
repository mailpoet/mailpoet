<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use Codeception\Stub;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\Util\Notices\PendingApprovalNotice;
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
      Stub::make(ServicesChecker::class),
      Stub::make(Subscribers::class),
      $wpFunctions,
      Stub::make(PendingApprovalNotice::class)
    );
    $this->subscribers = ['a@example.com', 'c d <b@example.com>'];
  }

  public function testCreateBlacklistError() {
    $error = $this->mapper->getBlacklistError($this->subscribers[1]);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_SOFT);
    verify($error->getMessage())->stringContainsString('unknown error');
    verify($error->getMessage())->stringContainsString('MailPoet');
  }

  public function testCreateConnectionError() {
    $error = $this->mapper->getConnectionError('connection error');
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_CONNECT);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('connection error');
  }

  public function testGetErrorNotArray() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_NOT_ARRAY,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error not array',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('JSON input is not an array');
  }

  public function testGetErrorBannedAccount() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_BANNED,
      'error' => API::ERROR_MESSAGE_BANNED,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('MailPoet Sending Service has been temporarily suspended for your site due to');
  }

  public function testGetErrorInsufficientPrivileges(): void {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES,
      'error' => API::ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_INSUFFICIENT_PRIVILEGES);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('You have reached the subscriber limit of your plan.');
  }

  public function testGetErrorSubscribersLimits(): void {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_SUBSCRIBERS_LIMIT_REACHED,
      'error' => API::ERROR_MESSAGE_SUBSCRIBERS_LIMIT_REACHED,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SUBSCRIBER_LIMIT_REACHED);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('You have reached the subscriber limit of your plan.');
  }

  public function testGetErrorUnauthorizedEmail() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_INVALID_FROM,
      'error' => API::ERROR_MESSAGE_INVALID_FROM,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);

    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_AUTHORIZATION);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('Sending all of your emails has been paused');
    verify($error->getMessage())->stringContainsString('because your email address');
  }

  public function testGetErrorPayloadTooBig() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_TOO_BIG,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'error too big',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('error too big');
  }

  public function testGetPayloadError() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'Api Error',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('Error while sending. Api Error');
  }

  public function testGetPayloadErrorWithErrorMessage() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"index":0,"errors":{"subject":"subject is missing"}},{"index":1,"errors":{"subject":"subject is missing"}}]',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_SOFT);
    $subscriberErrors = $error->getSubscriberErrors();
    verify(count($subscriberErrors))->equals(2);
    verify($subscriberErrors[0]->getEmail())->equals('a@example.com');
    verify($subscriberErrors[0]->getMessage())->equals('subject is missing');
    verify($subscriberErrors[1]->getEmail())->equals('c d <b@example.com>');
    verify($subscriberErrors[1]->getMessage())->equals('subject is missing');
  }

  public function testGetPayloadErrorForMalformedMSSResponse() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"errors":{"subject":"subject is missing"}},{"errors":{"subject":"subject is missing"}}]',
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('Error while sending. Invalid MSS response format.');
  }

  public function testGetPendingApprovalMessage() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_PENDING_APPROVAL,
      'error' => API::ERROR_MESSAGE_PENDING_APPROVAL,
    ];
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_PENDING_APPROVAL);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('reviewing your subscription');
    verify($error->getMessage())->stringContainsString("If you don't hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email for follow-up emails with the subject");
  }

  public function testGetUnavailableServiceError() {
    $apiResult = [
      'code' => API::RESPONSE_CODE_GATEWAY_TIMEOUT,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'Service is temporary unavailable',
    ];

    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getRetryInterval())->equals(MailPoetMapper::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL);
    verify($error->getOperation())->equals(MailerError::OPERATION_SEND);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('Email service is temporarily not available, please try again in a few minutes.');
  }

  public function testGetErrorEmailVolumeLimitWithAndWithoutKnownLimit(): void {
    $apiResult = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => API::ERROR_MESSAGE_EMAIL_VOLUME_LIMIT_REACHED,
      'error' => API::ERROR_MESSAGE_EMAIL_VOLUME_LIMIT_REACHED,
    ];

    $wpFunctions = Stub::make(new WPFunctions, [
      '_x' => function ($value) {
        return $value;
      },
      'getOption' => '',
      'dateI18n' => '2023-01-31',
    ]);

    $subscribersWithLimit = Stub::make(Subscribers::class, [
      'getEmailVolumeLimit' => 1000,
    ]);

    $subscribersWithoutKnownLimit = Stub::make(Subscribers::class, [
      'getEmailVolumeLimit' => 0,
    ]);

    $serviceChecker = Stub::make(ServicesChecker::class, [
      'generatePartialApiKey' => 'abc',
    ]);

    $pendingApprovalNotice = Stub::make(PendingApprovalNotice::class);

    // Check email volume error when the limit is known
    $this->mapper = new MailPoetMapper(
      $serviceChecker,
      $subscribersWithLimit,
      $wpFunctions,
      $pendingApprovalNotice
    );
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error)->instanceOf(MailerError::class);
    verify($error->getOperation())->equals(MailerError::OPERATION_EMAIL_LIMIT_REACHED);
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->stringContainsString('You have sent more emails this month than your MailPoet plan includes (1000),');
    verify($error->getMessage())->stringContainsString('wait until sending is automatically resumed on 2023-01-31');

    // Check email volume error when the limit is unknown
    $this->mapper = new MailPoetMapper(
      $serviceChecker,
      $subscribersWithoutKnownLimit,
      $wpFunctions,
      $pendingApprovalNotice
    );
    $error = $this->mapper->getErrorForResult($apiResult, $this->subscribers);
    verify($error->getMessage())->stringContainsString('You have sent more emails this month than your MailPoet plan includes,');
    verify($error->getMessage())->stringContainsString('wait until sending is automatically resumed on 2023-01-31');
  }
}
