<?php

namespace MailPoet\Services\Bridge;

use MailPoet\Logging\LoggerFactory;
use MailPoet\WP\Functions as WPFunctions;

class API {
  const SENDING_STATUS_OK = 'ok';
  const SENDING_STATUS_CONNECTION_ERROR = 'connection_error';
  const SENDING_STATUS_SEND_ERROR = 'send_error';

  const REQUEST_TIMEOUT = 10; // seconds

  const RESPONSE_CODE_KEY_INVALID = 401;
  const RESPONSE_CODE_STATS_SAVED = 204;
  const RESPONSE_CODE_TEMPORARY_UNAVAILABLE = 503;
  const RESPONSE_CODE_NOT_ARRAY = 422;
  const RESPONSE_CODE_PAYLOAD_TOO_BIG = 413;
  const RESPONSE_CODE_PAYLOAD_ERROR = 400;
  const RESPONSE_CODE_CAN_NOT_SEND = 403;

  private $api_key;
  private $wp;
  /** @var LoggerFactory */
  private $logger_factory;

  public $url_me = 'https://bridge.mailpoet.com/api/v0/me';
  public $url_premium = 'https://bridge.mailpoet.com/api/v0/premium';
  public $url_messages = 'https://bridge.mailpoet.com/api/v0/messages';
  public $url_bounces = 'https://bridge.mailpoet.com/api/v0/bounces/search';
  public $url_stats = 'https://bridge.mailpoet.com/api/v0/stats';
  public $url_authorized_email_addresses = 'https://bridge.mailpoet.com/api/v0/authorized_email_addresses';

  public function __construct($apiKey, $wp = null) {
    $this->setKey($apiKey);
    if (is_null($wp)) {
      $this->wp = new WPFunctions();
    } else {
      $this->wp = $wp;
    }
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function checkMSSKey() {
    $result = $this->request(
      $this->urlMe,
      ['site' => WPFunctions::get()->homeUrl()]
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    switch ($code) {
      case 200:
        $body = json_decode($this->wp->wpRemoteRetrieveBody($result), true);
        break;
      default:
        $body = null;
        break;
    }

    return ['code' => $code, 'data' => $body];
  }

  public function checkPremiumKey() {
    $result = $this->request(
      $this->urlPremium,
      ['site' => WPFunctions::get()->homeUrl()]
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    switch ($code) {
      case 200:
        $body = $this->wp->wpRemoteRetrieveBody($result);
        if ($body) {
          $body = json_decode($body, true);
        }
        break;
      default:
        $body = null;
        break;
    }

    return ['code' => $code, 'data' => $body];
  }

  public function logCurlInformation($headers, $info) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_MSS)->addInfo(
      'requests-curl.after_request',
      ['headers' => $headers, 'curl_info' => $info]
    );
  }

  public function sendMessages($messageBody) {
    add_action('requests-curl.after_request', [$this, 'logCurlInformation'], 10, 2);
    $result = $this->request(
      $this->urlMessages,
      $messageBody
    );
    remove_action('requests-curl.after_request', [$this, 'logCurlInformation']);
    if (is_wp_error($result)) {
      return [
        'status' => self::SENDING_STATUS_CONNECTION_ERROR,
        'message' => $result->get_error_message(),
      ];
    }

    $responseCode = $this->wp->wpRemoteRetrieveResponseCode($result);
    if ($responseCode !== 201) {
      $response = ($this->wp->wpRemoteRetrieveBody($result)) ?
        $this->wp->wpRemoteRetrieveBody($result) :
        $this->wp->wpRemoteRetrieveResponseMessage($result);
      return [
        'status' => self::SENDING_STATUS_SEND_ERROR,
        'message' => $response,
        'code' => $responseCode,
      ];
    }
    return ['status' => self::SENDING_STATUS_OK];
  }

  public function checkBounces(array $emails) {
    $result = $this->request(
      $this->urlBounces,
      $emails
    );
    if ($this->wp->wpRemoteRetrieveResponseCode($result) === 200) {
      return json_decode($this->wp->wpRemoteRetrieveBody($result), true);
    }
    return false;
  }

  public function updateSubscriberCount($count) {
    $result = $this->request(
      $this->urlStats,
      ['subscriber_count' => (int)$count],
      'PUT'
    );
    return $this->wp->wpRemoteRetrieveResponseCode($result) === self::RESPONSE_CODE_STATS_SAVED;
  }

  public function getAuthorizedEmailAddresses() {
    $result = $this->request(
      $this->urlAuthorizedEmailAddresses,
      null,
      'GET'
    );
    if ($this->wp->wpRemoteRetrieveResponseCode($result) === 200) {
      return json_decode($this->wp->wpRemoteRetrieveBody($result), true);
    }
    return false;
  }

  public function setKey($apiKey) {
    $this->apiKey = $apiKey;
  }

  public function getKey() {
    return $this->apiKey;
  }

  private function auth() {
    return 'Basic ' . base64_encode('api:' . $this->apiKey);
  }

  private function request($url, $body, $method = 'POST') {
    $params = [
      'timeout' => $this->wp->applyFilters('mailpoet_bridge_api_request_timeout', self::REQUEST_TIMEOUT),
      'httpversion' => '1.0',
      'method' => $method,
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth(),
      ],
      'body' => $body !== null ? json_encode($body) : null,
    ];
    return $this->wp->wpRemotePost($url, $params);
  }
}
