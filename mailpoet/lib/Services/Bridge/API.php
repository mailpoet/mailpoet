<?php

namespace MailPoet\Services\Bridge;

use MailPoet\Logging\LoggerFactory;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;

class API {
  const SENDING_STATUS_OK = 'ok';
  const SENDING_STATUS_CONNECTION_ERROR = 'connection_error';
  const SENDING_STATUS_SEND_ERROR = 'send_error';

  const REQUEST_TIMEOUT = 10; // seconds

  const RESPONSE_CODE_KEY_INVALID = 401;
  const RESPONSE_CODE_STATS_SAVED = 204;
  const RESPONSE_CODE_CREATED = 201;
  const RESPONSE_CODE_INTERNAL_SERVER_ERROR = 500;
  const RESPONSE_CODE_BAD_GATEWAY = 502;
  const RESPONSE_CODE_TEMPORARY_UNAVAILABLE = 503;
  const RESPONSE_CODE_GATEWAY_TIMEOUT = 504;
  const RESPONSE_CODE_NOT_ARRAY = 422;
  const RESPONSE_CODE_PAYLOAD_TOO_BIG = 413;
  const RESPONSE_CODE_PAYLOAD_ERROR = 400;
  const RESPONSE_CODE_CAN_NOT_SEND = 403;

  private $apiKey;
  private $wp;
  /** @var LoggerFactory */
  private $loggerFactory;
  /** @var mixed|null It is an instance of \CurlHandle in PHP8 and aboove but a resource in PHP7 */
  private $curlHandle = null;

  public $urlMe = 'https://bridge.mailpoet.com/api/v0/me';
  public $urlPremium = 'https://bridge.mailpoet.com/api/v0/premium';
  public $urlMessages = 'https://bridge.mailpoet.com/api/v0/messages';
  public $urlBounces = 'https://bridge.mailpoet.com/api/v0/bounces/search';
  public $urlStats = 'https://bridge.mailpoet.com/api/v0/stats';
  public $urlAuthorizedEmailAddresses = 'https://bridge.mailpoet.com/api/v1/authorized_email_address';
  public $urlAuthorizedSenderDomains = 'https://bridge.mailpoet.com/api/v1/sender_domain';
  public $urlAuthorizedSenderDomainVerification = 'https://bridge.mailpoet.com/api/v1/sender_domain_verify';

  public function __construct(
    $apiKey,
    $wp = null
  ) {
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
        $this->logKeyCheckError((int)$code, 'mss');
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
        $this->logKeyCheckError((int)$code, 'premium');
        $body = null;
        break;
    }

    return ['code' => $code, 'data' => $body];
  }

  public function logCurlInformation($headers, $info) {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_MSS)->info(
      'requests-curl.after_request',
      ['headers' => $headers, 'curl_info' => $info]
    );
  }

  public function setCurlHandle($handle) {
    $this->curlHandle = $handle;
  }

  public function sendMessages($messageBody) {
    $this->curlHandle = null;
    add_action('requests-curl.before_request', [$this, 'setCurlHandle'], 10, 2);
    add_action('requests-curl.after_request', [$this, 'logCurlInformation'], 10, 2);
    $result = $this->request(
      $this->urlMessages,
      $messageBody
    );
    remove_action('requests-curl.after_request', [$this, 'logCurlInformation']);
    remove_action('requests-curl.before_request', [$this, 'setCurlHandle']);
    if (is_wp_error($result)) {
      $this->logCurlError($result);
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

  public function updateSubscriberCount($count): bool {
    $result = $this->request(
      $this->urlStats,
      ['subscriber_count' => (int)$count],
      'PUT'
    );
    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    $isSuccess = $code === self::RESPONSE_CODE_STATS_SAVED;
    if (!$isSuccess) {
      $logData = [
        'code' => $code,
        'error' => is_wp_error($result) ? $result->get_error_message() : null,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('Stats API call failed.', $logData);
    }
    return $isSuccess;
  }

  public function getAuthorizedEmailAddresses(): ?array {
    $result = $this->request(
      $this->urlAuthorizedEmailAddresses,
      null,
      'GET'
    );
    if ($this->wp->wpRemoteRetrieveResponseCode($result) !== 200) {
      return null;
    }
    $data = json_decode($this->wp->wpRemoteRetrieveBody($result), true);
    return is_array($data) ? $data : null;
  }

  /**
   * Create Authorized Email Address
   *
   * returns ['status' => true] if done or an array of error messages ['error' => $errorBody, 'status' => false]
   * @param string $emailAddress
   * @return array
   */
  public function createAuthorizedEmailAddress(string $emailAddress): array {
    $body = ['email' => $emailAddress];
    $result = $this->request(
      $this->urlAuthorizedEmailAddresses,
      $body
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    $isSuccess = $code === self::RESPONSE_CODE_CREATED;

    if (!$isSuccess) {
      $errorBody = $this->wp->wpRemoteRetrieveBody($result);
      $logData = [
        'code' => $code,
        'error' => is_wp_error($result) ? $result->get_error_message() : $errorBody,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('CreateAuthorizedEmailAddress API call failed.', $logData);

      $errorResponseData = json_decode($errorBody, true);
      $fallbackError = sprintf($this->wp->__('An error has happened while performing a request, the server has responded with response code %d'), $code);

      $errorData = is_array($errorResponseData) && isset($errorResponseData['error']) ? $errorResponseData['error'] : $fallbackError;
      return ['error' => $errorData, 'status' => false];
    }

    return ['status' => $isSuccess];
  }

  /**
   * Get a list of sender domains
   * Fetched from API
   */
  public function getAuthorizedSenderDomains(): ?array {
    $result = $this->request(
      $this->urlAuthorizedSenderDomains,
      null,
      'GET'
    );
    if ($this->wp->wpRemoteRetrieveResponseCode($result) !== 200) {
      return null;
    }
    $data = json_decode($this->wp->wpRemoteRetrieveBody($result), true);
    return is_array($data) ? $data : null;
  }

  /**
   * Create Sender domain record
   * Done via API
   */
  public function createAuthorizedSenderDomain(string $domain): array {
    $body = ['domain' => strtolower($domain)];
    $result = $this->request(
      $this->urlAuthorizedSenderDomains,
      $body
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    $rawResponseBody = $this->wp->wpRemoteRetrieveBody($result);

    $responseBody = json_decode($rawResponseBody, true);
    $isSuccess = $code === self::RESPONSE_CODE_CREATED;

    if (!$isSuccess) {
      $logData = [
        'code' => $code,
        'error' => is_wp_error($result) ? $result->get_error_message() : $rawResponseBody,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('createAuthorizedSenderDomain API call failed.', $logData);

      $fallbackError = sprintf($this->wp->__('An error has happened while performing a request, the server has responded with response code %d'), $code);

      $errorData = is_array($responseBody) && isset($responseBody['error']) ? $responseBody['error'] : $fallbackError;
      return ['error' => $errorData, 'status' => false];
    }

    return is_array($responseBody) ? $responseBody : [];
  }

  /**
   * Verify Sender Domain records
   * returns an Array of DNS response or an array of error
   */
  public function verifyAuthorizedSenderDomain(string $domain): array {
    $url = $this->urlAuthorizedSenderDomainVerification . '/' . urlencode(strtolower($domain));
    $result = $this->request(
      $url,
      null
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    $rawResponseBody = $this->wp->wpRemoteRetrieveBody($result);

    $responseBody = json_decode($rawResponseBody, true);
    $isSuccess = $code === 200;

    if (!$isSuccess) {
      if ($code === 400) {
        // we need to return the body as it is
        return is_array($responseBody) ? $responseBody : [];
      }
      $logData = [
        'code' => $code,
        'error' => is_wp_error($result) ? $result->get_error_message() : $rawResponseBody,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('verifyAuthorizedSenderDomain API call failed.', $logData);

      $fallbackError = sprintf($this->wp->__('An error has happened while performing a request, the server has responded with response code %d'), $code);

      $errorData = is_array($responseBody) && isset($responseBody['error']) ? $responseBody['error'] : $fallbackError;
      return ['error' => $errorData, 'status' => false];
    }

    return is_array($responseBody) ? $responseBody : [];
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

  private function logCurlError(WP_Error $error) {
    $logData = [
      'curl_errno' => $this->curlHandle ? curl_errno($this->curlHandle) : 'n/a',
      'curl_error' => $this->curlHandle ? curl_error($this->curlHandle) : $error->get_error_message(),
      'curl_info' => $this->curlHandle ? curl_getinfo($this->curlHandle) : 'n/a',
    ];
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_MSS)->error('requests-curl.failed', $logData);
  }

  private function logKeyCheckError(int $code, string $keyType): void {
    $logData = [
      'http_code' => $code,
      'home_url' => $this->wp->homeUrl(),
      'key_type' => $keyType,
    ];
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_MSS)->error('key-validation.failed', $logData);
  }
}
