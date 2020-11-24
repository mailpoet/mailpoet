<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Swift_Message;

class AmazonSES {
  public $awsAccessKey;
  public $awsSecretKey;
  public $awsRegion;
  public $awsEndpoint;
  public $awsSigningAlgorithm;
  public $awsService;
  public $awsTerminationString;
  public $hashAlgorithm;
  public $url;
  public $sender;
  public $replyTo;
  public $returnPath;
  public $message;
  public $date;
  public $dateWithoutTime;
  private $availableRegions = [
    'US East (N. Virginia)' => 'us-east-1',
    'US East (Ohio) => 'us-east-2',
    'US West (Oregon)' => 'us-west-2',
    'EU (Ireland)' => 'eu-west-1',
    'EU (London)' => 'eu-west-2',
    'EU (Frankfurt)' => 'eu-central-1',
    'Canada (Central)' => 'ca-central-1',
    'Asia Pacific (Mumbai)' => 'ap-south-1',
    'Asia Pacific (Sydney)' => 'ap-southeast-2',
    'South America (Sao Paulo)' => 'sa-east-1',
  ];

  /** @var AmazonSESMapper */
  private $errorMapper;

  /** @var BlacklistCheck */
  private $blacklist;

  private $wp;

  public function __construct(
    $region,
    $accessKey,
    $secretKey,
    $sender,
    $replyTo,
    $returnPath,
    AmazonSESMapper $errorMapper
  ) {
    $this->awsAccessKey = $accessKey;
    $this->awsSecretKey = $secretKey;
    $this->awsRegion = (in_array($region, $this->availableRegions)) ? $region : false;
    if (!$this->awsRegion) {
      throw new \Exception(__('Unsupported Amazon SES region', 'mailpoet'));
    }
    $this->awsEndpoint = sprintf('email.%s.amazonaws.com', $this->awsRegion);
    $this->awsSigningAlgorithm = 'AWS4-HMAC-SHA256';
    $this->awsService = 'ses';
    $this->awsTerminationString = 'aws4_request';
    $this->hashAlgorithm = 'sha256';
    $this->url = 'https://' . $this->awsEndpoint;
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->returnPath = ($returnPath) ?
      $returnPath :
      $this->sender['from_email'];
    $this->date = gmdate('Ymd\THis\Z');
    $this->dateWithoutTime = gmdate('Ymd');
    $this->errorMapper = $errorMapper;
    $this->wp = new WPFunctions();
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
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

  public function getBody($newsletter, $subscriber, $extraParams = []) {
    $this->message = $this->createMessage($newsletter, $subscriber, $extraParams);
    $body = [
      'Action' => 'SendRawEmail',
      'Version' => '2010-12-01',
      'Source' => $this->sender['from_name_email'],
      'RawMessage.Data' => $this->encodeMessage($this->message),
    ];
    return $body;
  }

  public function createMessage($newsletter, $subscriber, $extraParams = []) {
    $message = (new Swift_Message())
      ->setTo($this->processSubscriber($subscriber))
      ->setFrom([
          $this->sender['from_email'] => $this->sender['from_name'],
        ])
      ->setSender($this->sender['from_email'])
      ->setReplyTo([
          $this->replyTo['reply_to_email'] => $this->replyTo['reply_to_name'],
        ])
      ->setReturnPath($this->returnPath)
      ->setSubject($newsletter['subject']);
    if (!empty($extraParams['unsubscribe_url'])) {
      $headers = $message->getHeaders();
      $headers->addTextHeader('List-Unsubscribe', '<' . $extraParams['unsubscribe_url'] . '>');
    }
    if (!empty($newsletter['body']['html'])) {
      $message = $message->setBody($newsletter['body']['html'], 'text/html');
    }
    if (!empty($newsletter['body']['text'])) {
      $message = $message->addPart($newsletter['body']['text'], 'text/plain');
    }
    return $message;
  }

  public function encodeMessage(Swift_Message $message) {
    return base64_encode($message->toString());
  }

  public function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if (!isset($subscriberData['email'])) {
      $subscriberData = [
        'email' => $subscriber,
      ];
    }
    return [
      $subscriberData['email'] =>
        (isset($subscriberData['name'])) ? $subscriberData['name'] : '',
    ];
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
      'body' => urldecode(http_build_query($body, null, '&')),
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
      $signature);
  }

  public function getCredentialScope() {
    return sprintf(
      '%s/%s/%s/%s',
      $this->dateWithoutTime,
      $this->awsRegion,
      $this->awsService,
      $this->awsTerminationString);
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
      hash($this->hashAlgorithm, urldecode(http_build_query($body, null, '&'))),
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
