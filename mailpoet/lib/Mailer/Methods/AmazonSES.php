<?php declare(strict_types = 1);

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use MailPoet\Settings\Hosts;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;
use PHPMailer\PHPMailer\PHPMailer;

class AmazonSES extends PHPMailerMethod {
  /** @var string */
  public $awsAccessKey;
  /** @var string */
  public $awsSecretKey;
  /** @var string */
  public $awsRegion;
  /** @var string */
  public $awsEndpoint;
  /** @var string */
  public $awsSigningAlgorithm;
  /** @var string */
  public $awsService;
  /** @var string */
  public $awsTerminationString;
  /** @var string */
  public $hashAlgorithm;
  /** @var string */
  public $url;
  /** @var string */
  public $rawMessage;
  /** @var string */
  public $date;
  /** @var string */
  public $dateWithoutTime;
  /** @var AmazonSESMapper */
  protected $errorMapper;
  /** @var WPFunctions */
  protected $wp;

  public function __construct(
    $region,
    $accessKey,
    $secretKey,
    $sender,
    $replyTo,
    $returnPath,
    AmazonSESMapper $errorMapper,
    WPFunctions $wp,
    Url $urlUtils
  ) {
    $this->awsAccessKey = $accessKey;
    $this->awsSecretKey = $secretKey;
    $this->awsRegion = in_array($region, self::getAvailableRegions(), true) ? $region : false;
    if (!$this->awsRegion) {
      throw new \Exception(__('Unsupported Amazon SES region', 'mailpoet'));
    }
    $this->awsEndpoint = sprintf('email.%s.%s', $this->awsRegion, self::getEndpointSuffix($this->awsRegion));
    $this->awsSigningAlgorithm = 'AWS4-HMAC-SHA256';
    $this->awsService = 'ses';
    $this->awsTerminationString = 'aws4_request';
    $this->hashAlgorithm = 'sha256';
    $this->url = 'https://' . $this->awsEndpoint;
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->returnPath = $returnPath;
    $this->date = gmdate('Ymd\THis\Z');
    $this->dateWithoutTime = gmdate('Ymd');
    $this->errorMapper = $errorMapper;
    $this->wp = $wp;
    $this->urlUtils = $urlUtils;
    $this->blacklist = new BlacklistCheck();
    $this->mailer = $this->buildMailer();
  }

  /**
   * @return string[]
   */
  public static function getAvailableRegions(): array {
    return Hosts::getSMTPHosts()['AmazonSES']['regions'];
  }

  private static function getEndpointSuffix(string $region): string {
    // AWS China is a separate partition and does not answer on amazonaws.com.
    return strpos($region, 'cn-') === 0 ? 'amazonaws.com.cn' : 'amazonaws.com';
  }

  public function send($newsletter, $subscriber, $extraParams = []): array {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->errorMapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    try {
      $result = $this->wp->wpRemotePost(
        $this->url,
        $this->request($newsletter, $subscriber, $extraParams)
      );
    } catch (\Exception $e) {
      $error = $this->errorMapper->getErrorFromException($e, $subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    if (is_wp_error($result)) {
      $error = $this->errorMapper->getConnectionError($result->get_error_message());
      return Mailer::formatMailerErrorResult($error);
    }
    if ($this->wp->wpRemoteRetrieveResponseCode($result) !== 200) {
      $response = simplexml_load_string($this->wp->wpRemoteRetrieveBody($result));
      $error = $this->errorMapper->getErrorFromResponse($response, $subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  public function buildMailer(): PHPMailer {
    return new PHPMailer(true);
  }

  public function getBody($newsletter, $subscriber, $extraParams = []) {
    /* Configure mailer and call preSend() method to prepare message */
    $mailer = $this->configureMailerWithMessage($newsletter, $subscriber, $extraParams);
    $mailer->preSend();
    /* When message is prepared, we can get the raw message */
    $this->rawMessage = $mailer->getSentMIMEMessage();
    return [
      'Action' => 'SendRawEmail',
      'Version' => '2010-12-01',
      'Source' => $this->sender['from_name_email'],
      'RawMessage.Data' => $this->encodeMessage($this->rawMessage),
    ];
  }

  public function encodeMessage(string $message) {
    return base64_encode($message);
  }

  public function request($newsletter, $subscriber, $extraParams = []) {
    $body = array_map('urlencode', $this->getBody($newsletter, $subscriber, $extraParams));
    return [
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => [
        'Host' => $this->awsEndpoint,
        'Authorization' => $this->signRequest($body),
        'X-Amz-Date' => $this->date,
      ],
      'body' => urldecode(http_build_query($body, '', '&')),
    ];
  }

  public function signRequest($body) {
    $stringToSign = $this->createStringToSign(
      $this->getCredentialScope(),
      $this->getCanonicalRequest($body)
    );
    $signature = hash_hmac(
      $this->hashAlgorithm,
      $stringToSign,
      $this->getSigningKey()
    );

    return sprintf(
      '%s Credential=%s/%s, SignedHeaders=host;x-amz-date, Signature=%s',
      $this->awsSigningAlgorithm,
      $this->awsAccessKey,
      $this->getCredentialScope(),
      $signature
    );
  }

  public function getCredentialScope() {
    return sprintf(
      '%s/%s/%s/%s',
      $this->dateWithoutTime,
      $this->awsRegion,
      $this->awsService,
      $this->awsTerminationString
    );
  }

  public function getCanonicalRequest($body) {
    return implode("\n", [
      'POST',
      '/',
      '',
      'host:' . $this->awsEndpoint,
      'x-amz-date:' . $this->date,
      '',
      'host;x-amz-date',
      hash($this->hashAlgorithm, urldecode(http_build_query($body, '', '&'))),
    ]);
  }

  public function createStringToSign($credentialScope, $canonicalRequest) {
    return implode("\n", [
      $this->awsSigningAlgorithm,
      $this->date,
      $credentialScope,
      hash($this->hashAlgorithm, $canonicalRequest),
    ]);
  }

  public function getSigningKey() {
    $dateKey = hash_hmac(
      $this->hashAlgorithm,
      $this->dateWithoutTime,
      'AWS4' . $this->awsSecretKey,
      true
    );
    $regionKey = hash_hmac(
      $this->hashAlgorithm,
      $this->awsRegion,
      $dateKey,
      true
    );
    $serviceKey = hash_hmac(
      $this->hashAlgorithm,
      $this->awsService,
      $regionKey,
      true
    );
    return hash_hmac(
      $this->hashAlgorithm,
      $this->awsTerminationString,
      $serviceKey,
      true
    );
  }
}
