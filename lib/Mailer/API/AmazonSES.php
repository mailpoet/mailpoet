<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class AmazonSES {
  function __construct($region, $accessKey, $secretKey, $from) {
    $this->awsAccessKey = $accessKey;
    $this->awsSecret_key = $secretKey;
    $this->awsRegion = $region;
    $this->awsEndpoint = sprintf('email.%s.amazonaws.com', $region);
    $this->awsSigningAlgorithm = 'AWS4-HMAC-SHA256';
    $this->awsService = 'ses';
    $this->awsTerminationString = 'aws4_request';
    $this->hashAlgorithm = 'sha256';
    $this->url = 'https://' . $this->awsEndpoint;
    $this->from = $from;
    $this->date = gmdate('Ymd\THis\Z');
    $this->dateWithoutTime = gmdate('Ymd');
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    return (
      !is_wp_error($result) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody() {
    return array(
      'Action' => 'SendEmail',
      'Version' => '2010-12-01',
      'Source' => $this->from,
      'Destination.ToAddresses.member.1' => $this->subscriber,
      'Message.Subject.Data' => $this->newsletter['subject'],
      'Message.Body.Html.Data' => $this->newsletter['body']['html'],
      'Message.Body.Text.Data' => $this->newsletter['body']['text'],
      'ReturnPath' => $this->from
    );
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Host' => $this->awsEndpoint,
        'Authorization' => $this->signRequest($this->getBody()),
        'X-Amz-Date' => $this->date
      ),
      'body' => urldecode(http_build_query($this->getBody()))
    );
  }

  function signRequest() {
    $stringToSign = $this->createStringToSign(
      $this->getCredentialScope(),
      $this->getCanonicalRequest()
    );
    $signature = hash_hmac($this->hashAlgorithm, $stringToSign, $this->getSigningKey());

    return sprintf(
      '%s Credential=%s/%s, SignedHeaders=host;x-amz-date, Signature=%s',
      $this->awsSigningAlgorithm,
      $this->awsAccessKey,
      $this->getCredentialScope(),
      $signature);
  }

  function getCredentialScope() {
    return sprintf('%s/%s/%s/%s', $this->dateWithoutTime, $this->awsRegion, $this->awsService, $this->awsTerminationString);
  }

  function getCanonicalRequest() {
    return implode("\n", array(
      'POST',
      '/',
      '',
      'host:' . $this->awsEndpoint,
      'x-amz-date:' . $this->date,
      '',
      'host;x-amz-date',
      hash($this->hashAlgorithm, urldecode(http_build_query($this->getBody())))
    ));
  }

  function createStringToSign($credentialScope, $canonicalRequest) {
    return implode("\n", array(
      $this->awsSigningAlgorithm,
      $this->date,
      $credentialScope,
      hash($this->hashAlgorithm, $canonicalRequest)
    ));
  }

  function getSigningKey() {
    $dateKey = hash_hmac($this->hashAlgorithm, $this->dateWithoutTime, 'AWS4' . $this->awsSecret_key, true);
    $regionKey = hash_hmac($this->hashAlgorithm, $this->awsRegion, $dateKey, true);
    $serviceKey = hash_hmac($this->hashAlgorithm, $this->awsService, $regionKey, true);
    return hash_hmac($this->hashAlgorithm, $this->awsTerminationString, $serviceKey, true);
  }
}