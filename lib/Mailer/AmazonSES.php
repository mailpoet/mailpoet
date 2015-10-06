<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class AmazonSES {
  function __construct($region, $access_key, $secret_key, $from, $to, $newsletter) {
    $this->region = $region;
    $this->host = sprintf('email.%s.amazonaws.com', $region);
    $this->url = 'https://' . $this->host;
    $this->access_key = $access_key;
    $this->secret_key = $secret_key;
    $this->newsletter = $newsletter;
    $this->from = $from;
    $this->to = $to;
    $this->date = gmdate('Ymd\THis\Z');
  }

  function send() {
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    return ($result['response']['code'] === 200);
  }

  function getBody() {
    $parameters = array(
      'Action' => 'SendEmail',
      'Version' => '2010-12-01',
      'Source' => $this->from,
      'Destination.ToAddresses.member.1' => $this->to,
      'Message.Subject.Data' => $this->newsletter['subject'],
      'Message.Body.Html.Data' => $this->newsletter['body'],
      'ReplyToAddresses.member.1' => $this->from,
      'ReturnPath' => $this->from
    );
    return urldecode(http_build_query($parameters));
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Host' => $this->host,
        'Authorization' => $this->signRequest($this->getBody()),
        'X-Amz-Date' => $this->date
      ),
      'body' => $this->getBody()
    );
  }

  function signRequest($bodyToSign) {
    $requestDate = $this->date;
    $requestDateShorted = substr($requestDate, 0, 8);
    $awsSigningAlgorithm = 'AWS4-HMAC-SHA256';
    $hashAlgorithm = 'sha256';
    $service = 'ses';
    $type = 'aws4_request';
    $scope = sprintf('%s/%s/%s/%s', $requestDateShorted, $this->region, $service, $type);

    $requestToSign = array(
      'POST',
      '/',
      '',
      'host:' . $this->host,
      'x-amz-date:' . $requestDate,
      '',
      'host;x-amz-date',
      hash($hashAlgorithm, $bodyToSign)
    );
    $requestToSign = implode("\n", $requestToSign);

    $stringToSign = array(
      $awsSigningAlgorithm,
      $requestDate,
      $scope,
      hash($hashAlgorithm, $requestToSign)
    );
    $stringToSign = implode("\n", $stringToSign);

    $dateKey = hash_hmac($hashAlgorithm, $requestDateShorted, 'AWS4' . $this->secret_key, true);
    $regionKey = hash_hmac($hashAlgorithm, $this->region, $dateKey, true);
    $serviceKey = hash_hmac($hashAlgorithm, $service, $regionKey, true);
    $signingKey = hash_hmac($hashAlgorithm, $type, $serviceKey, true);
    $signature = hash_hmac($hashAlgorithm, $stringToSign, $signingKey);

    return sprintf(
      '%s Credential=%s/%s, SignedHeaders=host;x-amz-date, Signature=%s',
      $awsSigningAlgorithm,
      $this->access_key,
      $scope,
      $signature);
  }
}