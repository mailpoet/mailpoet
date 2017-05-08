<?php

use Codeception\Util\Stub;
use MailPoet\API\Endpoints\v1\Services;
use MailPoet\API\Response as APIResponse;
use MailPoet\Services\Bridge;

class ServicesTest extends MailPoetTest {
  function _before() {
    $this->services_endpoint = new Services();
    $this->data = array('key' => '1234567890abcdef');
  }

  function testItRespondsWithErrorIfNoMSSKeyIsGiven() {
    $response = $this->services_endpoint->checkMSSKey(array('key' => ''));
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  function testItRespondsWithSuccessIfMSSKeyIsValid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkMSSKey' => array('state' => Bridge::MAILPOET_KEY_VALID)),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfMSSKeyIsInvalid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkMSSKey' => array('state' => Bridge::MAILPOET_KEY_INVALID)),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfMSSKeyIsExpiring() {
    $date = new DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkMSSKey' => array(
        'state' => Bridge::MAILPOET_KEY_EXPIRING,
        'data' => array('expire_at' => $date->format('c'))
      )),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($this->services_endpoint->date_time->getDateFormat()));
  }

  function testItRespondsWithErrorIfServiceIsUnavailableDuringMSSCheck() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkMSSKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE)),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains((string)Bridge::CHECK_ERROR_UNAVAILABLE);
  }

  function testItRespondsWithErrorIfMSSCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkMSSKey' => function() { throw new \Exception('test'); }),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  function testItRespondsWithErrorIfNoPremiumKeyIsGiven() {
    $response = $this->services_endpoint->checkPremiumKey(array('key' => ''));
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  function testItRespondsWithSuccessIfPremiumKeyIsValid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_VALID)),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfPremiumKeyIsInvalid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_INVALID)),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsUsed() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_ALREADY_USED)),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsExpiring() {
    $date = new DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => array(
        'state' => Bridge::PREMIUM_KEY_EXPIRING,
        'data' => array('expire_at' => $date->format('c'))
      )),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($this->services_endpoint->date_time->getDateFormat()));
  }

  function testItRespondsWithErrorIfServiceIsUnavailableDuringPremiumCheck() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE)),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains((string)Bridge::CHECK_ERROR_UNAVAILABLE);
  }

  function testItRespondsWithErrorIfPremiumCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array('checkPremiumKey' => function() { throw new \Exception('test'); }),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }
}