<?php
namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\Helpers;

class MailPoetMapperTest extends \MailPoetTest {
  /** @var MailPoetMapper */
  private $mapper;

  /** @var array */
  private $subscribers;

  function _before() {
    parent::_before();
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

  function testGetErrorBannedAccount() {
    $api_result = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => 'this is a spam',
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals(Helpers::replaceLinkTags(
      __('You currently are not permitted to send any emails with MailPoet Sending Service, which may have happened due to poor deliverability. Please [link]contact our support team[/link] to resolve the issue.', 'mailpoet'),
      'https://www.mailpoet.com/support/',
      array(
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      )
    ));
  }

  function testGetErrorUnauthorizedEmail() {
    $api_result = [
      'code' => API::RESPONSE_CODE_CAN_NOT_SEND,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED,
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);

    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_AUTHORIZATION);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->contains('The MailPoet Sending Service did not send your latest email because the address');
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
    expect($error->getMessage())->equals('Error while sending. Api Error');
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
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
    $subscriber_errors = $error->getSubscriberErrors();
    expect(count($subscriber_errors))->equals(2);
    expect($subscriber_errors[0]->getEmail())->equals('a@example.com');
    expect($subscriber_errors[0]->getMessage())->equals('subject is missing');
    expect($subscriber_errors[1]->getEmail())->equals('c d <b@example.com>');
    expect($subscriber_errors[1]->getMessage())->equals('subject is missing');
  }

  function testGetPayloadErrorForMalformedMSSResponse() {
    $api_result = [
      'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
      'status' => API::SENDING_STATUS_SEND_ERROR,
      'message' => '[{"errors":{"subject":"subject is missing"}},{"errors":{"subject":"subject is missing"}}]'
    ];
    $error = $this->mapper->getErrorForResult($api_result, $this->subscribers);
    expect($error)->isInstanceOf(MailerError::class);
    expect($error->getOperation())->equals(MailerError::OPERATION_SEND);
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Error while sending. Invalid MSS response format.');
  }
}
