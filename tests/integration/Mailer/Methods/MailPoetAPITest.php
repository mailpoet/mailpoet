<?php
namespace MailPoet\Test\Mailer\Methods;

use Codeception\Util\Stub;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Services\Bridge\API;

class MailPoetAPITest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->settings = array(
      'method' => 'MailPoet',
      'api_key' => getenv('WP_TEST_MAILER_MAILPOET_API') ?
        getenv('WP_TEST_MAILER_MAILPOET_API') :
        '1234567890'
    );
    $this->sender = array(
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>'
    );
    $this->reply_to = array(
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>'
    );
    $this->mailer = new MailPoet(
      $this->settings['api_key'],
      $this->sender,
      $this->reply_to,
      new MailPoetMapper()
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing MailPoet',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function testItCanGenerateBodyForSingleMessage() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    expect($body[0]['to']['address'])->equals($subscriber['email']);
    expect($body[0]['to']['name'])->equals($subscriber['name']);
    expect($body[0]['from']['address'])->equals($this->sender['from_email']);
    expect($body[0]['from']['name'])->equals($this->sender['from_name']);
    expect($body[0]['reply_to']['address'])->equals($this->reply_to['reply_to_email']);
    expect($body[0]['reply_to']['name'])->equals($this->reply_to['reply_to_name']);
    expect($body[0]['subject'])->equals($this->newsletter['subject']);
    expect($body[0]['html'])->equals($this->newsletter['body']['html']);
    expect($body[0]['text'])->equals($this->newsletter['body']['text']);
  }

  function testItCanGenerateBodyForMultipleMessages() {
    $newsletters = array_fill(0, 10, $this->newsletter);
    $subscribers = array_fill(0, 10, $this->subscriber);
    $body = $this->mailer->getBody($newsletters, $subscribers);
    expect(count($body))->equals(10);
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    expect($body[0]['to']['address'])->equals($subscriber['email']);
    expect($body[0]['to']['name'])->equals($subscriber['name']);
    expect($body[0]['from']['address'])->equals($this->sender['from_email']);
    expect($body[0]['from']['name'])->equals($this->sender['from_name']);
    expect($body[0]['reply_to']['address'])->equals($this->reply_to['reply_to_email']);
    expect($body[0]['reply_to']['name'])->equals($this->reply_to['reply_to_name']);
    expect($body[0]['subject'])->equals($this->newsletter['subject']);
    expect($body[0]['html'])->equals($this->newsletter['body']['html']);
    expect($body[0]['text'])->equals($this->newsletter['body']['text']);
  }

  function testItCanAddExtraParametersToSingleMessage() {
    $extra_params = array('unsubscribe_url' => 'http://example.com');
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber, $extra_params);
    expect($body[0]['list_unsubscribe'])->equals($extra_params['unsubscribe_url']);
  }

  function testItCanAddExtraParametersToMultipleMessages() {
    $extra_params = array('unsubscribe_url' => 'http://example.com');
    $newsletters = array_fill(0, 10, $this->newsletter);
    $subscribers = array_fill(0, 10, $this->subscriber);
    $body = $this->mailer->getBody($newsletters, $subscribers, $extra_params);
    expect($body[0]['list_unsubscribe'])->equals($extra_params['unsubscribe_url'][0]);
    expect($body[9]['list_unsubscribe'])->equals($extra_params['unsubscribe_url'][9]);
  }

  function testItCanAddUnsubscribeUrlToMultipleMessages() {
    $newsletters = array_fill(0, 10, $this->newsletter);
    $subscribers = array_fill(0, 10, $this->subscriber);
    $extra_params = array('unsubscribe_url' => array_fill(0, 10, 'http://example.com'));

    $body = $this->mailer->getBody($newsletters, $subscribers, $extra_params);
    expect(count($body))->equals(10);
    expect($body[0]['list_unsubscribe'])->equals($extra_params['unsubscribe_url'][0]);
    expect($body[9]['list_unsubscribe'])->equals($extra_params['unsubscribe_url'][9]);
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => ''
        ));
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => 'First'
        ));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => 'First Last'
        ));
  }

  function testItWillNotSendIfApiKeyIsMarkedInvalid() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->api_key = 'someapi';
    $this->mailer->services_checker = Stub::make(
      new ServicesChecker(),
      array('isMailPoetAPIKeyValid' => false),
      $this
    );
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItCannotSendWithoutProperApiKey() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->api->setKey('someapi');
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }

  function testFormatConnectionError() {
    $this->mailer->api = Stub::makeEmpty(
      'MailPoet\Services\Bridge\API',
      array('sendMessages' => [
        'status' => API::SENDING_STATUS_CONNECTION_ERROR,
        'message' => 'connection error',
      ]),
      $this
    );
    $result = $this->mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getOperation())->equals(MailerError::OPERATION_CONNECT);
  }

  function testFormatErrorNotArray() {
    $this->mailer->api = Stub::makeEmpty(
      'MailPoet\Services\Bridge\API',
      array('sendMessages' => [
        'code' => API::RESPONSE_CODE_NOT_ARRAY,
        'status' => API::SENDING_STATUS_SEND_ERROR,
        'message' => 'error not array',
      ]),
      $this
    );
    $result = $this->mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getOperation())->equals(MailerError::OPERATION_SEND);
  }

  function testFormatErrorTooBig() {
    $this->mailer->api = Stub::makeEmpty(
      'MailPoet\Services\Bridge\API',
      array('sendMessages' => [
        'code' => API::RESPONSE_CODE_PAYLOAD_TOO_BIG,
        'status' => API::SENDING_STATUS_SEND_ERROR,
        'message' => 'error too big',
      ]),
      $this
    );
    $result = $this->mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
  }

  function testFormatPayloadError() {
    $this->mailer->api = Stub::makeEmpty(
      'MailPoet\Services\Bridge\API',
      array('sendMessages' => [
        'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
        'status' => API::SENDING_STATUS_SEND_ERROR,
        'message' => 'Api Error',
      ]),
      $this
    );
    $result = $this->mailer->send([$this->newsletter, $this->newsletter], ['a@example.com', 'c d <b@example.com>']);
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getOperation())->equals(MailerError::OPERATION_SEND);
  }

  function testFormatPayloadErrorWithErrorMessage() {
    $this->mailer->api = Stub::makeEmpty(
      'MailPoet\Services\Bridge\API',
      array('sendMessages' => [
        'code' => API::RESPONSE_CODE_PAYLOAD_ERROR,
        'status' => API::SENDING_STATUS_SEND_ERROR,
        'message' => '[{"index":0,"errors":{"subject":"subject is missing"}},{"index":1,"errors":{"subject":"subject is missing"}}]'
      ]),
      $this
    );
    $result = $this->mailer->send([$this->newsletter, $this->newsletter], ['a@example.com', 'c d <b@example.com>']);
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getOperation())->equals(MailerError::OPERATION_SEND);
  }
}
