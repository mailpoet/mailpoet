<?php
namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\v1\Services;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Config\Installer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class ServicesTest extends \MailPoetTest {
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
        'checkMSSKey' => array('state' => Bridge::KEY_VALID),
        'storeMSSKeyAndState' => Expected::once()
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
        'checkMSSKey' => array('state' => Bridge::KEY_INVALID),
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfMSSKeyIsExpiring() {
    $date = new \DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array(
          'state' => Bridge::KEY_EXPIRING,
          'data' => array('expire_at' => $date->format('c'))
        ),
        'storeMSSKeyAndState' => Expected::once()
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
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $this->services_endpoint, 'getErrorDescriptionByCode', array(Bridge::CHECK_ERROR_UNAVAILABLE)
      )
    );
  }

  function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringMSSCheck() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => null,
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $this->services_endpoint, 'getErrorDescriptionByCode', array(Bridge::CHECK_ERROR_UNKNOWN)
      )
    );
  }

  function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringMSSCheck() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array('code' => 404),
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  function testItRespondsWithErrorIfMSSCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => function() {
          throw new \Exception('test');
        },
        'storeMSSKeyAndState' => Expected::never()
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
        'checkPremiumKey' => array('state' => Bridge::KEY_VALID),
        'storePremiumKeyAndState' => Expected::once()
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
        'checkPremiumKey' => array('state' => Bridge::KEY_INVALID),
        'storePremiumKeyAndState' => Expected::once()
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
        'checkPremiumKey' => array('state' => Bridge::KEY_ALREADY_USED),
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsExpiring() {
    $date = new \DateTime;
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array(
          'state' => Bridge::KEY_EXPIRING,
          'data' => array('expire_at' => $date->format('c'))
        ),
        'storePremiumKeyAndState' => Expected::once()
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
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $this->services_endpoint, 'getErrorDescriptionByCode', array(Bridge::CHECK_ERROR_UNAVAILABLE)
      )
    );
  }

  function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringPremiumCheck() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => null,
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $this->services_endpoint, 'getErrorDescriptionByCode', array(Bridge::CHECK_ERROR_UNKNOWN)
      )
    );
  }

  function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringPremiumCheck() {
    $this->services_endpoint->bridge = \Codeception\Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array('code' => 404),
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  function testItRespondsWithErrorIfPremiumCheckThrowsAnException() {
    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => function() {
          throw new \Exception('test');
        },
        'storePremiumKeyAndState' => Expected::never()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  function testItRespondsWithPublicIdForMSS() {
    $fake_public_id = 'a-fake-public_id';
    Setting::deleteValue('public_id');
    Setting::deleteValue('new_public_id');

    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array(
          'state' => Bridge::KEY_VALID,
          'data' => array( 'public_id' => $fake_public_id )
        ),
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);

    expect(Setting::getValue('public_id'))->equals($fake_public_id);
    expect(Setting::getValue('new_public_id'))->equals('true');
  }

  function testItRespondsWithoutPublicIdForMSS() {
    Setting::deleteValue('public_id');
    Setting::deleteValue('new_public_id');

    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkMSSKey' => array( 'state' => Bridge::KEY_VALID ),
        'storeMSSKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkMSSKey($this->data);

    expect(Setting::getValue('public_id', null))->null();
    expect(Setting::getValue('new_public_id', null))->null();
  }

  function testItRespondsWithPublicIdForPremium() {
    $fake_public_id = 'another-fake-public_id';
    Setting::deleteValue('public_id');
    Setting::deleteValue('new_public_id');

    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array(
          'state' => Bridge::KEY_VALID,
          'data' => array( 'public_id' => $fake_public_id )
        ),
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);

    expect(Setting::getValue('public_id'))->equals($fake_public_id);
    expect(Setting::getValue('new_public_id'))->equals('true');
  }

  function testItRespondsWithoutPublicIdForPremium() {
    Setting::deleteValue('public_id');
    Setting::deleteValue('new_public_id');

    $this->services_endpoint->bridge = Stub::make(
      new Bridge(),
      array(
        'checkPremiumKey' => array('state' => Bridge::KEY_VALID),
        'storePremiumKeyAndState' => Expected::once()
      ),
      $this
    );
    $response = $this->services_endpoint->checkPremiumKey($this->data);

    expect(Setting::getValue('public_id', null))->null();
    expect(Setting::getValue('new_public_id', null))->null();
  }
}
