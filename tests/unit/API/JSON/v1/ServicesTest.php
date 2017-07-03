<?php

use Codeception\Util\Stub;
use MailPoet\API\JSON\v1\Services;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Config\Installer;
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
      array(
        'checkMSSKey' => array('state' => Bridge::MAILPOET_KEY_VALID),
        'storeMSSKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfMSSKeyIsInvalid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array('state' => Bridge::MAILPOET_KEY_INVALID),
        'storeMSSKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfMSSKeyIsExpiring() {
    $date = new DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array(
          'state' => Bridge::MAILPOET_KEY_EXPIRING,
          'data' => array('expire_at' => $date->format('c'))
        ),
        'storeMSSKeyAndState' => Stub::once()
      ),
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
      array(
        'checkMSSKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE),
        'storeMSSKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains((string)Bridge::CHECK_ERROR_UNAVAILABLE);
  }

  function testItRespondsWithErrorIfMSSCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => function() { throw new \Exception('test'); },
        'storeMSSKeyAndState' => Stub::never()
      ),
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
      array(
        'checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_VALID),
        'storePremiumKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    foreach(array_keys(Installer::getPremiumStatus()) as $key) {
      expect(isset($response->meta[$key]))->true();
    }
  }

  function testItRespondsWithErrorIfPremiumKeyIsInvalid() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_INVALID),
        'storePremiumKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsUsed() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array('state' => Bridge::PREMIUM_KEY_ALREADY_USED),
        'storePremiumKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsExpiring() {
    $date = new DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array(
          'state' => Bridge::PREMIUM_KEY_EXPIRING,
          'data' => array('expire_at' => $date->format('c'))
        ),
        'storePremiumKeyAndState' => Stub::once()
      ),
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
      array(
        'checkPremiumKey' => array('code' => Bridge::CHECK_ERROR_UNAVAILABLE),
        'storePremiumKeyAndState' => Stub::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains((string)Bridge::CHECK_ERROR_UNAVAILABLE);
  }

  function testItRespondsWithErrorIfPremiumCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => function() { throw new \Exception('test'); },
        'storePremiumKeyAndState' => Stub::never()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }
}
