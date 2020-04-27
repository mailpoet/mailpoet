<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\Analytics\Analytics;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Services;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Cron\Workers\KeyCheck\SendingServiceKeyCheck;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Services\CongratulatoryMssEmailController;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WP\Functions as WPFunctions;

class ServicesTest extends \MailPoetTest {
  public $data;
  public $servicesEndpoint;
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->servicesEndpoint = $this->diContainer->get(Services::class);
    $this->data = ['key' => '1234567890abcdef'];
    $this->settings = SettingsController::getInstance();
  }

  public function testItRespondsWithErrorIfNoMSSKeyIsGiven() {
    $response = $this->diContainer->get(Services::class)->checkMSSKey(['key' => '']);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
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
    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($servicesEndpoint->dateTime->getDateFormat()));
  }

  public function testItRespondsWithErrorIfServiceIsUnavailableDuringMSSCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $servicesEndpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNAVAILABLE]
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $servicesEndpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNKNOWN]
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  public function testItDoesNotPauseSendingWhenMSSKeyValidAndApproved() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    expect(MailerLog::isSendingPaused())->false();

    $bridge = $this->make(
      Bridge::class,
      [
        'settings' => SettingsController::getInstance(),
        'checkMSSKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => ['is_approved' => true],
        ],
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItPausesSendingWhenMSSKeyValidButNotApproved() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    expect(MailerLog::isSendingPaused())->false();

    $bridge = $this->make(
      Bridge::class,
      [
        'settings' => SettingsController::getInstance(),
        'checkMSSKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => ['is_approved' => false],
        ],
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect(MailerLog::isSendingPaused())->true();
  }

  public function testItResumesSendingWhenMSSKeyBecomesApproved() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->settings->set(Bridge::API_KEY_SETTING_NAME, 'key');
    $this->settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_VALID,
      'data' => ['is_approved' => false],
    ]);
    MailerLog::pauseSending(MailerLog::getMailerLog());
    expect(MailerLog::isSendingPaused())->true();

    $bridge = $this->make(
      Bridge::class,
      [
        'settings' => SettingsController::getInstance(),
        'checkMSSKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => ['is_approved' => true],
        ],
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItRespondsWithErrorIfNoPremiumKeyIsGiven() {
    $response = $response = $this->diContainer->get(Services::class)->checkPremiumKey(['key' => '']);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['message'])
      ->contains($date->format($servicesEndpoint->dateTime->getDateFormat()));
  }

  public function testItRespondsWithErrorIfServiceIsUnavailableDuringPremiumCheck() {
    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => ['code' => Bridge::CHECK_ERROR_UNAVAILABLE],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $servicesEndpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNAVAILABLE]
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains(
      $this->invokeMethod(
        $servicesEndpoint, 'getErrorDescriptionByCode', [Bridge::CHECK_ERROR_UNKNOWN]
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('test');
  }

  public function testItRespondsWithPublicIdForMSS() {
    $fakePublicId = 'a-fake-public_id';
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkMSSKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => [ 'public_id' => $fakePublicId ],
        ],
        'storeMSSKeyAndState' => Expected::once(),
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $servicesEndpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id'))->equals($fakePublicId);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkMSSKey($this->data);

    expect($this->settings->get('public_id', null))->null();
    expect($this->settings->get('new_public_id', null))->null();
  }

  public function testItRespondsWithPublicIdForPremium() {
    $fakePublicId = 'another-fake-public_id';
    $this->settings->delete('public_id');
    $this->settings->delete('new_public_id');

    $bridge = $this->make(
      new Bridge(),
      [
        'checkPremiumKey' => [
          'state' => Bridge::KEY_VALID,
          'data' => [ 'public_id' => $fakePublicId ],
        ],
        'storePremiumKeyAndState' => Expected::once(),
      ]
    );

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);

    expect($this->settings->get('public_id'))->equals($fakePublicId);
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

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->checkPremiumKey($this->data);

    expect($this->settings->get('public_id', null))->null();
    expect($this->settings->get('new_public_id', null))->null();
  }

  public function testCongratulatoryEmailIsSent() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->settings->set('sender.address', 'authorized@email.com');
    $bridge = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => ['authorized@email.com'],
    ]);

    $congratulatoryEmailController = $this->make(CongratulatoryMssEmailController::class, [
      'sendCongratulatoryEmail' => Expected::once('authorized@email.com'),
    ]);

    $servicesEndpoint = $this->createServicesEndpointWithMocks([
      'bridge' => $bridge,
      'congratulatoryEmailController' => $congratulatoryEmailController,
    ]);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(['email_address' => 'authorized@email.com']);
  }

  public function testCongratulatoryEmailRespondsWithErrorWhenMssNotActive() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);
    $servicesEndpoint = $this->diContainer->get(Services::class);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('MailPoet Sending Service is not active.');
  }

  public function testCongratulatoryEmailRespondsWithErrorWhenNoEmailAuthorized() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $bridge = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => [],
    ]);

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('No FROM email addresses are authorized.');
  }

  public function testCongratulatoryEmailRespondsWithErrorWhenNoSenderSet() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->settings->set('sender.address', null);
    $bridge = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => ['authorized@email.com'],
    ]);

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Sender email address is not set.');
  }

  public function testCongratulatoryEmailRespondsWithErrorWhenSenderNotAuthorized() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->settings->set('sender.address', 'unauthorized@email.com');
    $bridge = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => ['authorized@email.com'],
    ]);

    $servicesEndpoint = $this->createServicesEndpointWithMocks(['bridge' => $bridge]);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals("Sender email address 'unauthorized@email.com' is not authorized.");
  }

  public function testCongratulatoryEmailRespondsWithErrorWhenSendingFails() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->settings->set('sender.address', 'authorized@email.com');
    $bridge = $this->make(Bridge::class, [
      'getAuthorizedEmailAddresses' => ['authorized@email.com'],
    ]);

    $congratulatoryEmailController = $this->make(CongratulatoryMssEmailController::class, [
      'sendCongratulatoryEmail' => function () {
        throw new \Exception('test');
      },
    ]);

    $servicesEndpoint = $this->createServicesEndpointWithMocks([
      'bridge' => $bridge,
      'congratulatoryEmailController' => $congratulatoryEmailController,
    ]);
    $response = $servicesEndpoint->sendCongratulatoryMssEmail();
    expect($response->status)->equals(APIResponse::STATUS_UNKNOWN);
    expect($response->errors[0]['message'])->equals('Sending of congratulatory email failed.');
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }

  private function createServicesEndpointWithMocks(array $mocks) {
    return new Services(
      $mocks['bridge'] ?? $this->diContainer->get(Bridge::class),
      $this->diContainer->get(SettingsController::class),
      $this->diContainer->get(Analytics::class),
      $this->diContainer->get(SendingServiceKeyCheck::class),
      $this->diContainer->get(PremiumKeyCheck::class),
      $this->diContainer->get(ServicesChecker::class),
      $mocks['congratulatoryEmailController'] ?? $this->diContainer->get(CongratulatoryMssEmailController::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }
}
