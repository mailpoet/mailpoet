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
  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->services_endpoint = $this->di_container->get(Services::class);
    $this->data = ['key' => '1234567890abcdef'];
    $this->settings = SettingsController::getInstance();
  }

  function testItRespondsWithErrorIfSPFCheckFails() {
    $email = 'spf_test@example.com';
    $this->settings->set('sender.address', $email);

    $spf_check = $this->make(
      SPFCheck::class,
      ['checkSPFRecord' => false],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedSPFCheck($spf_check);
    $response = $services_endpoint->checkSPFRecord([]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->meta['sender_address'])->equals($email);
    expect($response->meta['domain_name'])->equals('example.com');
  }

  function testItRespondsWithSuccessIfSPFCheckPasses() {
    $spf_check = $this->make(
      SPFCheck::class,
      ['checkSPFRecord' => true],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedSPFCheck($spf_check);
    $response = $services_endpoint->checkSPFRecord([]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfNoMSSKeyIsGiven() {
    $response = $this->di_container->get(Services::class)->checkMSSKey(['key' => '']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  function testItRespondsWithSuccessIfMSSKeyIsValid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['state' => Bridge::KEY_VALID],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItRespondsWithErrorIfMSSKeyIsInvalid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['state' => Bridge::KEY_INVALID],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
    );
    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfMSSKeyIsExpiring() {
    $date = new \DateTime;
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [
          'state' => Bridge::KEY_EXPIRING,
          'data' => ['expire_at' => $date->format('c')],
        ],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($services_endpoint->date_time->getDateFormat()));
  }

  function testItRespondsWithErrorIfServiceIsUnavailableDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
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

  function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => null,
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
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

  function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['code' => 404],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  function testItRespondsWithErrorIfMSSCheckThrowsAnException() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => function() {
          throw new \Exception('test');
        },
        'storeMSSKeyAndState' => Expected::never(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  function testItRespondsWithErrorIfNoPremiumKeyIsGiven() {
    $response = $response = $this->di_container->get(Services::class)->checkPremiumKey(['key' => '']);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a key.');
  }

  function testItRespondsWithSuccessIfPremiumKeyIsValid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_VALID],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    foreach (array_keys(Installer::getPremiumStatus()) as $key) {
      expect(isset($response->meta[$key]))->true();
    }
  }

  function testItRespondsWithErrorIfPremiumKeyIsInvalid() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_INVALID],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsUsed() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_ALREADY_USED],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  function testItRespondsWithErrorIfPremiumKeyIsExpiring() {
    $date = new \DateTime;
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => [
          'state' => Bridge::KEY_EXPIRING,
          'data' => ['expire_at' => $date->format('c')],
        ],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($services_endpoint->date_time->getDateFormat()));
  }

  function testItRespondsWithErrorIfServiceIsUnavailableDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
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

  function testItRespondsWithErrorIfServiceDidNotReturnAResponseCodeDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => null,
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
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

  function testItPrintsErrorCodeIfServiceReturnedAnUnexpectedResponseCodeDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['code' => 404],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('404');
  }

  function testItRespondsWithErrorIfPremiumCheckThrowsAnException() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => function() {
          throw new \Exception('test');
        },
        'storePremiumKeyAndState' => Expected::never(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  function testItRespondsWithPublicIdForMSS() {
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
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $services_endpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id'))->equals($fake_public_id);
    expect($this->settings->get('new_public_id'))->equals('true');
  }

  function testItRespondsWithoutPublicIdForMSS() {
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [ 'state' => Bridge::KEY_VALID ],
        'storeMSSKeyAndState' => Expected::once(),
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id', null))->null();
    expect($this->settings->get('new_public_id', null))->null();
  }

  function testItRespondsWithPublicIdForPremium() {
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
      ],
      $this
    );

    $services_endpoint = $this->createServicesEndpointWithMockedBridge($bridge);
    $response = $services_endpoint->checkPremiumKey($this->data);

    expect($this->settings->get('public_id'))->equals($fake_public_id);
    expect($this->settings->get('new_public_id'))->equals('true');
  }

  function testItRespondsWithoutPublicIdForPremium() {
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['state' => Bridge::KEY_VALID],
        'storePremiumKeyAndState' => Expected::once(),
      ],
      $this
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
