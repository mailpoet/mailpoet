<?php

use Codeception\Util\Stub;
use MailPoet\API\Endpoints\Services;
use MailPoet\API\Response as APIResponse;
use MailPoet\Services\Bridge;

class ServicesTest extends MailPoetTest {
  function _before() {
    $this->services_endpoint = new Services();
    $this->data = array('key' => '1234567890abcdef');
  }

  function testItRespondsWithErrorIfNoKeyIsGiven() {
    $response = $this->services_endpoint->verifyMailPoetKey(array('key' => ''));
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  function testItRespondsWithSuccessIfKeyIsValid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkKey' => array('state' => Bridge::MAILPOET_KEY_VALID)),
      $this
    );
    $response = $this->services_endpoint->verifyMailPoetKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfKeyIsInvalid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkKey' => array('state' => Bridge::MAILPOET_KEY_INVALID)),
      $this
    );
    $response = $this->services_endpoint->verifyMailPoetKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfKeyIsExpiring() {
    $date = new DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkKey' => array(
        'state' => Bridge::MAILPOET_KEY_EXPIRING,
        'data' => array('expire_at' => $date->format('c'))
      )),
      $this
    );
    $response = $this->services_endpoint->verifyMailPoetKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains($date->format('Y-m-d'));
  }

  function testItRespondsWithErrorIfServiceIsUnavailable() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE)),
      $this
    );
    $response = $this->services_endpoint->verifyMailPoetKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains((string)Bridge::CHECK_ERROR_UNAVAILABLE);
  }
}