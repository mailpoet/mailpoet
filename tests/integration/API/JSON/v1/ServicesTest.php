<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Analytics\Analytics;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Services;
use MailPoet\Config\Installer;
use MailPoet\Services\Bridge;
use MailPoet\Services\SPFCheck;
use MailPoet\Settings\SettingsController;

class ServicesTest extends \MailPoetTest {
  public $data;
  public $services_endpoint;
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->services_endpoint = $this->di_container->get(Services::class);
    $this->data = ['key' => '1234567890abcdef'];
    $this->settings = SettingsController::getInstance();
  }

  public function testItRespondsWithErrorIfSPFCheckFails() {
    $email = 'spf_test@example.com';
    $this->settings->set('sender.address', $email);

    $spf_check = $this->make(
      SPFCheck::class,
      ['checkSPFRecord' => false]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedSPFCheck($spf_check);
    $response = $services_endpoint->checkSPFRecord([]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta['sender_address'])->equals($email);
  }

  public function testItRespondsWithSuccessIfSPFCheckPasses() {
    $spf_check = $this->make(
      SPFCheck::class,
      ['checkSPFRecord' => true]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedSPFCheck($spf_check);
    $response = $services_endpoint->checkSPFRecord([]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItRespondsWithErrorIfNoMSSKeyIsGiven() {
    $response = $this->di_container->get(Services::class)->checkMSSKey(['key' => '']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  public function testItRespondsWithSuccessIfMSSKeyIsValid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['state' => Bridge::KEY_VALID],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItRespondsWithErrorIfMSSKeyIsInvalid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['state' => Bridge::KEY_INVALID],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );
    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItRespondsWithErrorIfMSSKeyIsExpiring() {
    $date = new \DateTime;
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [
          'state' => Bridge::KEY_EXPIRING,
          'data' => ['expire_at' => $date->format('c')],
        ],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($services_endpoint->date_time->getDateFormat()));
  }

  public function testItRespondsWithErrorIfServiceIsUnavailableDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $services_endpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNAVAILABLE]
      )
    );
  }

  public function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => null,
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $services_endpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNKNOWN]
      )
    );
  }

  public function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['code' => 404],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  public function testItRespondsWithErrorIfMSSCheckThrowsAnException() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => function() {
          throw new \Exception('test');
        },
        'storeMSSKeyAndState' => Expected::never(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  public function testItRespondsWithErrorIfNoPremiumKeyIsGiven() {
    $response = $response = $this->di_container->get(Services::class)->checkPremiumKey(['key' => '']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  public function testItRespondsWithSuccessIfPremiumKeyIsValid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_VALID],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    foreach (array_keys(Installer::getPremiumStatus()) as $key) {
      expect(isset($response->meta[$key]))->true();
    }
  }

  public function testItRespondsWithErrorIfPremiumKeyIsInvalid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_INVALID],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItRespondsWithErrorIfPremiumKeyIsUsed() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_ALREADY_USED],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItRespondsWithErrorIfPremiumKeyIsExpiring() {
    $date = new \DateTime;
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => [
          'state' => Bridge::KEY_EXPIRING,
          'data' => ['expire_at' => $date->format('c')],
        ],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($services_endpoint->date_time->getDateFormat()));
  }

  public function testItRespondsWithErrorIfServiceIsUnavailableDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $services_endpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNAVAILABLE]
      )
    );
  }

  public function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => null,
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $services_endpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNKNOWN]
      )
    );
  }

  public function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['code' => 404],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  public function testItRespondsWithErrorIfPremiumCheckThrowsAnException() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => function() {
          throw new \Exception('test');
        },
        'storePremiumKeyAndState' => Expected::never(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  public function testItRespondsWithPublicIdForMSS() {
    $fake_public_id = 'a-fake-public_id';
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => [ 'public_id' => $fake_public_id ],
        ],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $services_endpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id'))->equals($fake_public_id);
    expect($this->settings->get('new_public_id'))->equals('true');
  }

  public function testItRespondsWithoutPublicIdForMSS() {
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [ 'state' => Bridge::KEY_VALID ],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id', null))->null();
    expect($this->settings->get('new_public_id', null))->null();
  }

  public function testItRespondsWithPublicIdForPremium() {
    $fake_public_id = 'another-fake-public_id';
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => [ 'public_id' => $fake_public_id ],
        ],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);

    expect($this->settings->get('public_id'))->equals($fake_public_id);
    expect($this->settings->get('new_public_id'))->equals('true');
  }

  public function testItRespondsWithoutPublicIdForPremium() {
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_VALID],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);

    expect($this->settings->get('public_id', null))->null();
    expect($this->settings->get('new_public_id', null))->null();
  }

  private function createServicesEndpointWithMockedSPFCheck($spf_check) {
    return new Services(
      $this->di_container->get(Bridge::class),
      $this->di_container->get(SettingsController::class),
      $this->di_container->get(Analytics::class),
      $spf_check
    );
  }

  private function createServicesEndpointWithMockedBridge($bridge) {
    return new Services(
      $bridge,
      $this->di_container->get(SettingsController::class),
      $this->di_container->get(Analytics::class),
      $this->di_container->get(SPFCheck::class)
    );
  }
}
