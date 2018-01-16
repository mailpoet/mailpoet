<?php

namespace MailPoet\Services\Bridge;

use MailPoet\WP\Hooks as WPHooks;
use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;

class API {
  const SENDING_STATUS_OK = 'ok';
  const SENDING_STATUS_CONNECTION_ERROR = 'connection_error';
  const SENDING_STATUS_SEND_ERROR = 'send_error';

  const REQUEST_TIMEOUT = 10; // seconds

  const RESPONSE_CODE_KEY_INVALID = 401;
  const RESPONSE_CODE_STATS_SAVED = 204;

  private $api_key;

  public $url_me = 'https://bridge.mailpoet.com/api/v0/me';
  public $url_premium = 'https://bridge.mailpoet.com/api/v0/premium';
  public $url_messages = 'https://bridge.mailpoet.com/api/v0/messages';
  public $url_bounces = 'https://bridge.mailpoet.com/api/v0/bounces/search';
  public $url_stats = 'https://bridge.mailpoet.com/api/v0/stats';

  function __construct($api_key) {
    $this->setKey($api_key);
  }

  function checkMSSKey() {
    $result = $this->request(
      $this->url_me,
      array('site' => home_url())
    );

    $code = WPFunctions::wpRemoteRetrieveResponseCode($result);
    switch($code) {
      case 200:
        $body = json_decode(WPFunctions::wpRemoteRetrieveBody($result), true);
        break;
      default:
        $body = null;
        break;
    }

    return array('code' => $code, 'data' => $body);
  }

  function checkPremiumKey() {
    $result = $this->request(
      $this->url_premium,
      array('site' => home_url())
    );

    $code = WPFunctions::wpRemoteRetrieveResponseCode($result);
    switch($code) {
      case 200:
        if($body = WPFunctions::wpRemoteRetrieveBody($result)) {
          $body = json_decode($body, true);
        }
        break;
      default:
        $body = null;
        break;
    }

    return array('code' => $code, 'data' => $body);
  }


  function sendMessages($message_body) {
    $result = $this->request(
      $this->url_messages,
      $message_body
    );
    if(is_wp_error($result)) {
      return array(
        'status' => self::SENDING_STATUS_CONNECTION_ERROR,
        'message' => $result->get_error_message()
      );
    }
    $response_code = WPFunctions::wpRemoteRetrieveResponseCode($result);
    if($response_code !== 201) {
      $response = (WPFunctions::wpRemoteRetrieveBody($result)) ?
        WPFunctions::wpRemoteRetrieveBody($result) :
        WPFunctions::wpRemoteRetrieveResponseMessage($result);
      return array(
        'status' => self::SENDING_STATUS_SEND_ERROR,
        'message' => $response,
        'code' => $response_code
      );
    }
    return array('status' => self::SENDING_STATUS_OK);
  }

  function checkBounces(array $emails) {
    $result = $this->request(
      $this->url_bounces,
      $emails
    );
    if(WPFunctions::wpRemoteRetrieveResponseCode($result) === 200) {
      return json_decode(WPFunctions::wpRemoteRetrieveBody($result), true);
    }
    return false;
  }

  function updateSubscriberCount($count) {
    $result = $this->request(
      $this->url_stats,
      array('subscriber_count' => (int)$count),
      'PUT'
    );
    return WPFunctions::wpRemoteRetrieveResponseCode($result) === self::RESPONSE_CODE_STATS_SAVED;
  }

  function setKey($api_key) {
    $this->api_key = $api_key;
  }

  function getKey() {
    return $this->api_key;
  }

  private function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  private function request($url, $body, $method = 'POST') {
    $params = array(
      'timeout' => WPHooks::applyFilters('mailpoet_bridge_api_request_timeout', self::REQUEST_TIMEOUT),
      'httpversion' => '1.0',
      'method' => $method,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth()
      ),
      'body' => json_encode($body)
    );
    return WPFunctions::wpRemotePost($url, $params);
  }
}