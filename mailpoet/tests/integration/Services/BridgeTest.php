<?php declare(strict_types = 1);

namespace MailPoet\Test\Services;

use Codeception\Util\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Services\Bridge\BridgeTestMockAPI as MockAPI;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once('BridgeTestMockAPI.php');

class BridgeTest extends \MailPoetTest {
  public $usedPremiumKey;
  public $expiringPremiumKey;
  public $uncheckableKey;
  public $underPrivilegedKey;
  public $usedKey;
  public $expiringKey;
  public $invalidKey;
  public $validKey;

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  public function _before() {
    parent::_before();
    $this->validKey = 'abcdefghijklmnopqrstuvwxyz';
    $this->invalidKey = '401' . $this->validKey;
    $this->expiringKey = 'expiring' . $this->validKey;
    $this->usedKey = '402' . $this->validKey;
    $this->underPrivilegedKey = '403' . $this->validKey;
    $this->uncheckableKey = '503' . $this->validKey;

    $this->expiringPremiumKey = 'expiring' . $this->validKey;
    $this->usedPremiumKey = '402' . $this->validKey;

    $this->bridge = new Bridge();

    $this->bridge->api = new MockAPI('key');
    $this->settings = SettingsController::getInstance();
  }

  public function testItChecksIfCurrentSendingMethodIsMailpoet() {
    $this->setMailPoetSendingMethod();
    expect(Bridge::isMPSendingServiceEnabled())->true();
  }

  public function testMPCheckReturnsFalseWhenMailerThrowsException() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, '');
    expect(Bridge::isMPSendingServiceEnabled())->false();
  }

  public function testItChecksIfPremiumKeyIsSpecified() {
    expect(Bridge::isPremiumKeySpecified())->false();
    $this->fillPremiumKey();
    expect(Bridge::isPremiumKeySpecified())->true();
  }

  public function testItInstantiatesDefaultAPI() {
    $this->bridge->api = null;
    expect($this->bridge->getApi('key') instanceof API)->true();
  }

  public function testItChecksValidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->validKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID);
  }

  public function testItChecksInvalidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->invalidKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  public function testItChecksExpiringMSSKey() {
    $result = $this->bridge->checkMSSKey($this->expiringKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  public function testItChecksAlreadyUsed() {
    $result = $this->bridge->checkMSSKey($this->usedKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  public function testItChecksForbiddenEndpointMSSKey() {
    $result = $this->bridge->checkMSSKey($this->underPrivilegedKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
  }

  public function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringMSSCheck() {
    $api = Stub::make(new API(null), ['checkMSSKey' => []], $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkMSSKey($this->validKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  public function testItStoresExpectedMSSKeyStates() {
    $states = [
      Bridge::KEY_VALID => $this->validKey,
      Bridge::KEY_INVALID => $this->invalidKey,
      Bridge::KEY_EXPIRING => $this->expiringKey,
      Bridge::KEY_ALREADY_USED => $this->usedKey,
      Bridge::CHECK_ERROR_UNAVAILABLE => $this->uncheckableKey,
      Bridge::KEY_VALID_UNDERPRIVILEGED => $this->underPrivilegedKey,
    ];
    foreach ($states as $state => $key) {
      $state = ['state' => $state];
      $this->bridge->storeMSSKeyAndState($key, $state);
      expect($this->getMSSKey())->equals($key);
      expect($this->getMSSKeyState())->equals($state);
    }
  }

  public function testItDoesNotStoreErroneousOrUnexpectedMSSKeyStates() {
    $states = [
      ['state' => Bridge::KEY_CHECK_ERROR],
      [],
    ];
    foreach ($states as $state) {
      $this->bridge->storeMSSKeyAndState($this->validKey, $state);
      expect($this->getMSSKey())->notEquals($this->validKey);
      expect($this->getMSSKeyState())->notEquals($state);
    }
  }

  public function testItChecksValidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->validKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID);
  }

  public function testItChecksInvalidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->invalidKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  public function testItChecksAlreadyUsedPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->usedPremiumKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  public function testItChecksForbiddenEndpointPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->underPrivilegedKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID_UNDERPRIVILEGED);
  }

  public function testItChecksExpiringPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->expiringPremiumKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  public function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringPremiumCheck() {
    $api = Stub::make(new API(null), ['checkPremiumKey' => []], $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkPremiumKey($this->validKey);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  public function testItStoresExpectedPremiumKeyStates() {
    $states = [
      Bridge::KEY_VALID => $this->validKey,
      Bridge::KEY_INVALID => $this->invalidKey,
      Bridge::KEY_ALREADY_USED => $this->usedPremiumKey,
      Bridge::KEY_EXPIRING => $this->expiringKey,
    ];
    foreach ($states as $state => $key) {
      $state = ['state' => $state];
      $this->bridge->storePremiumKeyAndState($key, $state);
      expect($this->getPremiumKey())->equals($key);
      expect($this->getPremiumKeyState())->equals($state);
    }
  }

  public function testItDoesNotStoreErroneousOrUnexpectedPremiumKeyStates() {
    $states = [
      ['state' => Bridge::KEY_CHECK_ERROR],
      [],
    ];
    foreach ($states as $state) {
      $this->bridge->storePremiumKeyAndState($this->validKey, $state);
      expect($this->getPremiumKey())->notEquals($this->validKey);
      expect($this->getPremiumKeyState())->notEquals($state);
    }
  }

  public function testItInvalidatesMSSKey() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      ['state' => Bridge::KEY_VALID]
    );
    Bridge::invalidateKey();
    expect($this->getMSSKeyState())->equals(['state' => Bridge::KEY_INVALID]);
  }

  public function testItChecksAndStoresKeysOnSettingsSave() {
    $response = ['state' => Bridge::KEY_VALID];
    /** @var Bridge&MockObject $bridge */
    $bridge = Stub::makeEmptyExcept(
      Bridge::class,
      'onSettingsSave',
      [
        'checkMSSKey' => $response,
        'checkPremiumKey' => $response,
      ],
      $this
    );
    $bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($this->validKey));
    $bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($this->validKey),
        $this->equalTo($response)
      );

    $bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($this->validKey));
    $bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($this->validKey),
        $this->equalTo($response)
      );
    $bridge->expects($this->once())
      ->method('updateSubscriberCount')
      ->with($this->equalTo($this->validKey));

    $settings = [];
    $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] = $this->validKey;
    $settings['premium']['premium_key'] = $this->validKey;

    $this->setMailPoetSendingMethod();
    $bridge->onSettingsSave($settings);
  }

  public function testItPingsBridge() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    expect(Bridge::pingBridge())->true();
  }

  public function testItAllowsChangingRequestTimeout() {
    $wpRemotePostArgs = [];
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => function() use (&$wpRemotePostArgs) {
        $wpRemotePostArgs = func_get_args();
      },
    ]);
    $api = new API('test_key', $wp);

    // test default request value
    $api->sendMessages('test');
    expect($wpRemotePostArgs[1]['timeout'])->equals(API::REQUEST_TIMEOUT);

    // test custom request value
    $customRequestValue = 20;
    $filter = function() use ($customRequestValue) {
      return $customRequestValue;
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_bridge_api_request_timeout', $filter);
    $api->sendMessages('test');
    expect($wpRemotePostArgs[1]['timeout'])->equals($customRequestValue);
    $wp->removeFilter('mailpoet_bridge_api_request_timeout', $filter);
  }

  public function testItReturnsOnlyAuthorizedEmails() {
    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
      'main' => 'main@email.com',
    ];
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => $array], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    expect($result)->same(['authorized@email.com']);
  }

  public function testItReturnsAllUserEmails() {
    $array = [
      'pending' => ['pending@email.com'],
      'authorized' => ['authorized@email.com'],
      'main' => 'main@email.com',
    ];
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => $array], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses('all');
    expect($result)->same($array);
  }

  public function testItReturnsAnEmptyArrayIfNoEmailForAllParam() {
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses('all');
    expect($result)->same([]);
  }

  public function testItReturnsAnEmptyArrayIfNoEmailForAuthorizedParam() {
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    expect($result)->same([]);
  }

  public function testItReturnsAnEmptyArrayIfNoNullForAuthorizedParam() {
    $api = Stub::make(new API(null), ['getAuthorizedEmailAddresses' => null], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedEmailAddresses();
    expect($result)->same([]);
  }

  public function testItReturnsTheRightDataForSenderDomains() {
    // when API returns null
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => null], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    expect($result)->same([]);

    // when API returns an empty array []
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    expect($result)->same([]);

    // when arg param is 'all'
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => []], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('all');
    expect($result)->same([]);
  }

  public function testItReturnsSenderDomainsDnsRecords() {
    $domainData = MockAPI::VERIFIED_DOMAIN_RESPONSE;
    $domainData['domain'] = 'example.com';
    $data = [$domainData];

    // with a custom sender domain param
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('example.com');
    expect($result)->same($data[0]['dns']);

    // with a custom sender domain param that does not exist
    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('mailpoet.com');
    expect($result)->same([]);

    // when param is all
    $returnDataForAllParam = [
      'example.com' => $data[0]['dns'],
    ];

    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains('all');
    expect($result)->same($returnDataForAllParam);

    // when param is not provided
    $returnDataForNoArgs = [
      'example.com' => $data[0]['dns'],
    ];

    $api = Stub::make(new API(null), ['getAuthorizedSenderDomains' => $data], $this);
    $this->bridge->api = $api;

    $result = $this->bridge->getAuthorizedSenderDomains();
    expect($result)->same($returnDataForNoArgs);
  }

  public function testItCanCreateSenderDomain() {
    $result = $this->bridge->createAuthorizedSenderDomain('mailpoet.com');
    expect($result)->notEmpty();
    expect(isset($result['error']))->false();
    expect($result[0]['host'])->equals('mailpoet1._domainkey.example.com');
  }

  public function testItDoesntCreateSenderDomainThatExists() {
    $result = $this->bridge->createAuthorizedSenderDomain('existing.com');
    expect($result)->notEmpty();
    expect($result['error'])->equals('This domain was already added to the list.');
    expect($result['status'])->equals(false);
  }

  public function testTheSenderDomainApiReturnsValidDataType() {
    $result = $this->bridge->getAuthorizedSenderDomains('mailpoet.com');
    expect($result)->notEmpty();
    expect($result[0]['host'])->equals('mailpoet1._domainkey.example.com');
    expect($result[0]['value'])->equals('dkim1.sendingservice.net');
    expect($result[0]['type'])->equals('CNAME');
    expect($result[0]['status'])->equals('valid');
    expect($result[0]['message'])->equals('');
  }

  public function testItCanVerifySenderDomain() {
    $result = $this->bridge->verifyAuthorizedSenderDomain('mailpoet.com');
    expect($result)->notEmpty();
    expect($result['ok'])->equals(true); // verified
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  private function getMSSKey() {
    return $this->settings->get(Bridge::API_KEY_SETTING_NAME);
  }

  private function getMSSKeyState() {
    return $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME);
  }

  private function fillPremiumKey() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  private function getPremiumKey() {
    return $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
  }

  private function getPremiumKeyState() {
    return $this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);
  }

  public function _after() {
    parent::_after();
  }
}
