<?php

namespace MailPoet\Test\Services;

use Codeception\Util\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Services\Bridge\BridgeTestMockAPI as MockAPI;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

require_once('BridgeTestMockAPI.php');

class BridgeTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->valid_key = 'abcdefghijklmnopqrstuvwxyz';
    $this->invalid_key = '401' . $this->valid_key;
    $this->expiring_key = 'expiring' . $this->valid_key;
    $this->used_key = '402' . $this->valid_key;
    $this->forbidden_endpoint_key = '403' . $this->valid_key;
    $this->uncheckable_key = '503' . $this->valid_key;

    $this->expiring_premium_key = 'expiring' . $this->valid_key;
    $this->used_premium_key = '402' . $this->valid_key;

    $this->bridge = new Bridge();

    $this->bridge->api = new MockAPI('key');
    $this->settings = new SettingsController();
  }

  function testItChecksIfCurrentSendingMethodIsMailpoet() {
    expect(Bridge::isMPSendingServiceEnabled())->false();
    $this->setMailPoetSendingMethod();
    expect(Bridge::isMPSendingServiceEnabled())->true();
  }

  function testMPCheckReturnsFalseWhenMailerThrowsException() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, '');
    expect(Bridge::isMPSendingServiceEnabled())->false();
  }

  function testItChecksIfPremiumKeyIsSpecified() {
    expect(Bridge::isPremiumKeySpecified())->false();
    $this->fillPremiumKey();
    expect(Bridge::isPremiumKeySpecified())->true();
  }

  function testItInstantiatesDefaultAPI() {
    $this->bridge->api = null;
    $this->bridge->initApi(null);
    expect($this->bridge->api instanceof API)->true();
  }

  function testItChecksValidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID);
  }

  function testItChecksInvalidMSSKey() {
    $result = $this->bridge->checkMSSKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  function testItChecksExpiringMSSKey() {
    $result = $this->bridge->checkMSSKey($this->expiring_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  function testItChecksAlreadyUsed() {
    $result = $this->bridge->checkMSSKey($this->used_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  function testItChecksForbiddenEndpointMSSKey() {
    $result = $this->bridge->checkMSSKey($this->forbidden_endpoint_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringMSSCheck() {
    $api = Stub::make(new API(null), array('checkMSSKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkMSSKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  function testItStoresExpectedMSSKeyStates() {
    $states = array(
      Bridge::KEY_VALID => $this->valid_key,
      Bridge::KEY_INVALID => $this->invalid_key,
      Bridge::KEY_EXPIRING => $this->expiring_key
    );
    foreach ($states as $state => $key) {
      $state = array('state' => $state);
      $this->bridge->storeMSSKeyAndState($key, $state);
      expect($this->getMSSKey())->equals($key);
      expect($this->getMSSKeyState())->equals($state);
    }
  }

  function testItDoesNotStoreErroneousOrUnexpectedMSSKeyStates() {
    $states = array(
      array('state' => Bridge::KEY_CHECK_ERROR),
      array()
    );
    foreach ($states as $state) {
      $this->bridge->storeMSSKeyAndState($this->valid_key, $state);
      expect($this->getMSSKey())->notEquals($this->valid_key);
      expect($this->getMSSKeyState())->notEquals($state);
    }
  }

  function testItChecksValidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_VALID);
  }

  function testItChecksInvalidPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->invalid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  function testItChecksAlreadyUsedPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->used_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_ALREADY_USED);
  }

  function testItChecksForbiddenEndpointPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->forbidden_endpoint_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_INVALID);
  }

  function testItChecksExpiringPremiumKey() {
    $result = $this->bridge->checkPremiumKey($this->expiring_premium_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_EXPIRING);
    expect($result['data']['expire_at'])->notEmpty();
  }

  function testItReturnsErrorStateOnEmptyAPIResponseCodeDuringPremiumCheck() {
    $api = Stub::make(new API(null), array('checkPremiumKey' => array()), $this);
    $this->bridge->api = $api;
    $result = $this->bridge->checkPremiumKey($this->valid_key);
    expect($result)->notEmpty();
    expect($result['state'])->equals(Bridge::KEY_CHECK_ERROR);
  }

  function testItStoresExpectedPremiumKeyStates() {
    $states = array(
      Bridge::KEY_VALID => $this->valid_key,
      Bridge::KEY_INVALID => $this->invalid_key,
      Bridge::KEY_ALREADY_USED => $this->used_premium_key,
      Bridge::KEY_EXPIRING => $this->expiring_key
    );
    foreach ($states as $state => $key) {
      $state = array('state' => $state);
      $this->bridge->storePremiumKeyAndState($key, $state);
      expect($this->getPremiumKey())->equals($key);
      expect($this->getPremiumKeyState())->equals($state);
    }
  }

  function testItDoesNotStoreErroneousOrUnexpectedPremiumKeyStates() {
    $states = array(
      array('state' => Bridge::KEY_CHECK_ERROR),
      array()
    );
    foreach ($states as $state) {
      $this->bridge->storePremiumKeyAndState($this->valid_key, $state);
      expect($this->getPremiumKey())->notEquals($this->valid_key);
      expect($this->getPremiumKeyState())->notEquals($state);
    }
  }

  function testItUpdatesSubscriberCount() {
    // it performs update if the key is valid or expiring
    $result = array();
    $result['state'] = Bridge::KEY_VALID;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->true();
    $result['state'] = Bridge::KEY_EXPIRING;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->true();
    // it does not perform update if the key is invalid
    $result['state'] = Bridge::KEY_INVALID;
    $updated = $this->bridge->updateSubscriberCount($result);
    expect($updated)->null();
  }

  function testItInvalidatesMSSKey() {
    $this->settings->set(
      Bridge::API_KEY_STATE_SETTING_NAME,
      array('state' => Bridge::KEY_VALID)
    );
    Bridge::invalidateKey();
    expect($this->getMSSKeyState())->equals(array('state' => Bridge::KEY_INVALID));
  }

  function testItChecksAndStoresKeysOnSettingsSave() {
    $response = array('abc' => 'def');
    $bridge = Stub::makeEmptyExcept(
      $this->bridge,
      'onSettingsSave',
      array(
        'checkMSSKey' => $response,
        'checkPremiumKey' => $response
      ),
      $this
    );
    $bridge->expects($this->once())
      ->method('checkMSSKey')
      ->with($this->equalTo($this->valid_key));
    $bridge->expects($this->once())
      ->method('storeMSSKeyAndState')
      ->with(
        $this->equalTo($this->valid_key),
        $this->equalTo($response)
      );
    $bridge->expects($this->once())
      ->method('updateSubscriberCount')
      ->with($this->equalTo($response));

    $bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($this->valid_key));
    $bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($this->valid_key),
        $this->equalTo($response)
      );

    $settings = array();
    $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] = $this->valid_key;
    $settings['premium']['premium_key'] = $this->valid_key;

    $this->setMailPoetSendingMethod();
    $bridge->onSettingsSave($settings);
  }

  function testItPingsBridge() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    expect(Bridge::pingBridge())->true();
  }

  function testItAllowsChangingRequestTimeout() {
    $wp_remote_post_args = array();
    $wp = Stub::make(new WPFunctions, [
      'wpRemotePost' => function() use (&$wp_remote_post_args) {
        $wp_remote_post_args = func_get_args();
      }
    ]);
    $api = new API('test_key', $wp);

    // test default request value
    $api->sendMessages('test');
    expect($wp_remote_post_args[1]['timeout'])->equals(API::REQUEST_TIMEOUT);

    // test custom request value
    $custom_request_value = 20;
    $filter = function() use ($custom_request_value) {
      return $custom_request_value;
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_bridge_api_request_timeout', $filter);
    $api->sendMessages('test');
    expect($wp_remote_post_args[1]['timeout'])->equals($custom_request_value);
    $wp->removeFilter('mailpoet_bridge_api_request_timeout', $filter);
  }

  private function setMailPoetSendingMethod() {
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      )
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

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
