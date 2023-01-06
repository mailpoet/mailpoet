<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Services\Bridge;

use MailPoet\Logging\LoggerFactory;
use MailPoet\WP\Functions as WPFunctions;
use WP_Error;

class API {
  const AUTHORIZED_EMAIL_STATUS_OK = 'ok';
  const AUTHORIZED_EMAIL_STATUS_ERROR = 'authorized_email_error';
  const AUTHORIZED_DOMAIN_STATUS_OK = 'ok';
  const AUTHORIZED_DOMAIN_STATUS_ERROR = 'authorized_domain_error';
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

  // Bridge messages from https://github.com/mailpoet/services-bridge/blob/master/api/messages.rb
  public const ERROR_MESSAGE_BANNED = 'Key is valid, but the action is forbidden';
  public const ERROR_MESSAGE_INVALID_FROM = 'The email address is not authorized';
  public const ERROR_MESSAGE_PENDING_APPROVAL = 'Key is valid, but not approved yet; you can send only to authorized email addresses at the moment';
  public const ERROR_MESSAGE_DMRAC = "Email violates Sender Domain's DMARC policy. Please set up sender authentication.";
  // Bridge message from https://github.com/mailpoet/services-bridge/blob/master/extensions/authentication/basic_strategy.rb
  public const ERROR_MESSAGE_UNAUTHORIZED = 'No valid API key provided';
  public const ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES = 'Insufficient privileges';
  public const ERROR_MESSAGE_EMAIL_VOLUME_LIMIT_REACHED = 'Email volume limit reached';
  // Proxy request `authorized_email_address` from shop https://github.com/mailpoet/shop/blob/master/routes/hooks/sending/v1/index.js#L65
  public const ERROR_MESSAGE_AUTHORIZED_EMAIL_NO_FREE = 'You cannot use a free email address. Please use an address from your website’s domain, for example.';
  public const ERROR_MESSAGE_AUTHORIZED_EMAIL_INVALID = 'Invalid email.';
  public const ERROR_MESSAGE_AUTHORIZED_EMAIL_ALREADY_ADDED = 'This email was already added to the list.';
  // Proxy request `sender_domain_verify` from shop https://github.com/mailpoet/shop/blob/master/routes/hooks/sending/v1/index.js#L137
  public const ERROR_MESSAGE_AUTHORIZED_DOMAIN_VERIFY_NOT_FOUND = 'Domain not found';
  public const ERROR_MESSAGE_AUTHORIZED_DOMAIN_VERIFY_FAILED = 'Some DNS records were not set up correctly. Please check the records again. You may need to wait up to 24 hours for DNS changes to propagate.';

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
      ['site' => strtolower(WPFunctions::get()->homeUrl())]
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
      ['site' => strtolower(WPFunctions::get()->homeUrl())]
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
        'error' => $response,
        'message' => $this->getTranslatedErrorMessage($response),
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
   * @param string $emailAddress
   * @return array{status: string, code?: int, error?: string, message?: string}
   */
  public function createAuthorizedEmailAddress(string $emailAddress): array {
    $body = ['email' => $emailAddress];
    $result = $this->request(
      $this->urlAuthorizedEmailAddresses,
      $body
    );

    $responseCode = $this->wp->wpRemoteRetrieveResponseCode($result);

    if ($responseCode !== self::RESPONSE_CODE_CREATED) {
      $errorBody = $this->wp->wpRemoteRetrieveBody($result);
      $logData = [
        'code' => $responseCode,
        'error' => is_wp_error($result) ? $result->get_error_message() : $errorBody,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('CreateAuthorizedEmailAddress API call failed.', $logData);

      $errorResponseData = json_decode($errorBody, true);
      // translators: %d is the error code.
      $fallbackError = sprintf(__('An error has happened while performing a request, the server has responded with response code %d', 'mailpoet'), $responseCode);

      return [
        'status' => self::AUTHORIZED_EMAIL_STATUS_ERROR,
        'code' => $responseCode,
        'error' => is_array($errorResponseData) && isset($errorResponseData['error']) ? $errorResponseData['error'] : $fallbackError,
        'message' => is_array($errorResponseData) && isset($errorResponseData['error']) ? $this->getTranslatedErrorMessage($errorResponseData['error']) : $fallbackError,
      ];
    }

    return ['status' => self::AUTHORIZED_EMAIL_STATUS_OK];
  }

  /**
   * Get a list of sender domains
   * Fetched from API
   * @see https://github.com/mailpoet/services-bridge#sender-domains
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
    $rawData = $this->wp->wpRemoteRetrieveBody($result);
    $data = json_decode($rawData, true);
    if (!is_array($data)) {
      $this->logInvalidDataFormat('getAuthorizedSenderDomains', $rawData);
      return null;
    }
    return $data;
  }

  /**
   * Create Sender domain record
   * Done via API
   * Returns same response se sender_domain_verify @see https://github.com/mailpoet/services-bridge#verify-a-sender-domain
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

      // translators: %d will be replaced by an error code
      $fallbackError = sprintf(__('An error has happened while performing a request, the server has responded with response code %d', 'mailpoet'), $code);

      $errorData = is_array($responseBody) && isset($responseBody['error']) ? $responseBody['error'] : $fallbackError;
      return ['error' => $errorData, 'status' => false];
    }

    if (!is_array($responseBody)) {
      $this->logInvalidDataFormat('createAuthorizedSenderDomain', $rawResponseBody);
      return [];
    }

    return $responseBody;
  }

  /**
   * Verify Sender Domain records
   * returns an Array of DNS response or an array of error
   * @see https://github.com/mailpoet/services-bridge#verify-a-sender-domain
   */
  public function verifyAuthorizedSenderDomain(string $domain): array {
    $url = $this->urlAuthorizedSenderDomainVerification . '/' . urlencode(strtolower($domain));
    $result = $this->request(
      $url,
      null
    );

    $responseCode = $this->wp->wpRemoteRetrieveResponseCode($result);
    $rawResponseBody = $this->wp->wpRemoteRetrieveBody($result);

    $responseBody = json_decode($rawResponseBody, true);
    if ($responseCode !== 200) {
      if ($responseCode === 400) {
        // we need to return the body as it is, but for consistency we add status and translated error message
        $response = is_array($responseBody) ? $responseBody : [];
        $response['status'] = self::AUTHORIZED_DOMAIN_STATUS_ERROR;
        $response['message'] = $this->getTranslatedErrorMessage($response['error']);
        return $response;
      }
      $logData = [
        'code' => $responseCode,
        'error' => is_wp_error($result) ? $result->get_error_message() : $rawResponseBody,
      ];
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error('verifyAuthorizedSenderDomain API call failed.', $logData);

      // translators: %d will be replaced by an error code
      $fallbackError = sprintf(__('An error has happened while performing a request, the server has responded with response code %d', 'mailpoet'), $responseCode);

      return [
        'status' => self::AUTHORIZED_DOMAIN_STATUS_ERROR,
        'code' => $responseCode,
        'error' => is_array($responseBody) && isset($responseBody['error']) ? $responseBody['error'] : $fallbackError,
        'message' => is_array($responseBody) && isset($responseBody['error']) ? $this->getTranslatedErrorMessage($responseBody['error']) : $fallbackError,
      ];
    }

    if (!is_array($responseBody)) {
      $this->logInvalidDataFormat('verifyAuthorizedSenderDomain', $rawResponseBody);
      return [];
    }

    $responseBody['status'] = self::AUTHORIZED_DOMAIN_STATUS_OK;
    return $responseBody;
  }

  public function setKey($apiKey) {
    $this->apiKey = $apiKey;
  }

  public function getKey() {
    return $this->apiKey;
  }

  public function getTranslatedErrorMessage(string $errorMessage): string {
    switch ($errorMessage) {
      case self::ERROR_MESSAGE_BANNED:
        return __('Key is valid, but the action is forbidden.', 'mailpoet');
      case self::ERROR_MESSAGE_INVALID_FROM:
        return __('The email address is not authorized.', 'mailpoet');
      case self::ERROR_MESSAGE_PENDING_APPROVAL:
        return __('Key is valid, but not approved yet; you can send only to authorized email addresses at the moment.', 'mailpoet');
      case self::ERROR_MESSAGE_DMRAC:
        return __("Email violates Sender Domain's DMARC policy. Please set up sender authentication.", 'mailpoet');
      case self::ERROR_MESSAGE_UNAUTHORIZED:
        return __('No valid API key provided.', 'mailpoet');
      case self::ERROR_MESSAGE_INSUFFICIENT_PRIVILEGES:
        return __('Insufficient privileges.', 'mailpoet');
      case self::ERROR_MESSAGE_EMAIL_VOLUME_LIMIT_REACHED:
        return __('Email volume limit reached.', 'mailpoet');
      case self::ERROR_MESSAGE_AUTHORIZED_EMAIL_NO_FREE:
        return __('You cannot use a free email address. Please use an address from your website’s domain, for example.', 'mailpoet');
      case self::ERROR_MESSAGE_AUTHORIZED_EMAIL_INVALID:
        return __('Invalid email.', 'mailpoet');
      case self::ERROR_MESSAGE_AUTHORIZED_EMAIL_ALREADY_ADDED:
        return __('This email was already added to the list.', 'mailpoet');
      case self::ERROR_MESSAGE_AUTHORIZED_DOMAIN_VERIFY_NOT_FOUND:
        return __('Domain not found.', 'mailpoet');
      case self::ERROR_MESSAGE_AUTHORIZED_DOMAIN_VERIFY_FAILED:
        return __('Some DNS records were not set up correctly. Please check the records again. You may need to wait up to 24 hours for DNS changes to propagate.', 'mailpoet');
      // when we don't match translation we return the origin
      default:
        return $errorMessage;
    }
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

  private function logInvalidDataFormat(string $method, ?string $response = null): void {
    $logData = [
      'code' => json_last_error(),
      'response' => $response,
    ];
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error($method . ' API response was not in expected format.', $logData);
  }
}
