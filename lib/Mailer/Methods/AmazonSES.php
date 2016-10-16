<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class AmazonSES {
  public $aws_access_key;
  public $aws_secret_key;
  public $aws_region;
  public $aws_endpoint;
  public $aws_signing_algorithm;
  public $aws_service;
  public $aws_termination_string;
  public $hash_algorithm;
  public $url;
  public $sender;
  public $reply_to;
  public $date;
  public $date_without_time;
  const SES_REGIONS = array(
    'US East (N. Virginia)' => 'us-east-1',
    'US West (Oregon)' => 'us-west-2',
    'EU (Ireland)' => 'eu-west-1'
  );

  function __construct($region, $access_key, $secret_key, $sender, $reply_to) {
    $this->aws_access_key = $access_key;
    $this->aws_secret_key = $secret_key;
    $this->aws_region = (in_array($region, self::SES_REGIONS)) ? $region : false;
    if(!$this->aws_region) {
      throw new \Exception(__('Unsupported Amazon SES region.', 'mailpoet'));
    }
    $this->aws_endpoint = sprintf('email.%s.amazonaws.com', $this->aws_region);
    $this->aws_signing_algorithm = 'AWS4-HMAC-SHA256';
    $this->aws_service = 'ses';
    $this->aws_termination_string = 'aws4_request';
    $this->hash_algorithm = 'sha256';
    $this->url = 'https://' . $this->aws_endpoint;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->date = gmdate('Ymd\THis\Z');
    $this->date_without_time = gmdate('Ymd');
  }

  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber)
    );
    return (
      !is_wp_error($result) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'Action' => 'SendEmail',
      'Version' => '2010-12-01',
      'Destination.ToAddresses.member.1' => $subscriber,
      'Source' => $this->sender['from_name_email'],
      'ReplyToAddresses.member.1' => $this->reply_to['reply_to_name_email'],
      'Message.Subject.Data' => $newsletter['subject'],
      'ReturnPath' => $this->sender['from_name_email'],
    );
    if(!empty($newsletter['body']['html'])) {
      $body['Message.Body.Html.Data'] = $newsletter['body']['html'];
    }
    if(!empty($newsletter['body']['text'])) {
      $body['Message.Body.Text.Data'] = $newsletter['body']['text'];
    }
    return $body;
  }

  function request($newsletter, $subscriber) {
    $body = $this->getBody($newsletter, $subscriber);
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Host' => $this->aws_endpoint,
        'Authorization' => $this->signRequest($body),
        'X-Amz-Date' => $this->date
      ),
      'body' => urldecode(http_build_query($body))
    );
  }

  function signRequest($body) {
    $string_to_sign = $this->createStringToSign(
      $this->getCredentialScope(),
      $this->getCanonicalRequest($body)
    );
    $signature = hash_hmac(
      $this->hash_algorithm,
      $string_to_sign,
      $this->getSigningKey()
    );

    return sprintf(
      '%s Credential=%s/%s, SignedHeaders=host;x-amz-date, Signature=%s',
      $this->aws_signing_algorithm,
      $this->aws_access_key,
      $this->getCredentialScope(),
      $signature);
  }

  function getCredentialScope() {
    return sprintf(
      '%s/%s/%s/%s',
      $this->date_without_time,
      $this->aws_region,
      $this->aws_service,
      $this->aws_termination_string);
  }

  function getCanonicalRequest($body) {
    return implode("\n", array(
      'POST',
      '/',
      '',
      'host:' . $this->aws_endpoint,
      'x-amz-date:' . $this->date,
      '',
      'host;x-amz-date',
      hash($this->hash_algorithm, urldecode(http_build_query($body)))
    ));
  }

  function createStringToSign($credential_scope, $canonical_request) {
    return implode("\n", array(
      $this->aws_signing_algorithm,
      $this->date,
      $credential_scope,
      hash($this->hash_algorithm, $canonical_request)
    ));
  }

  function getSigningKey() {
    $date_key = hash_hmac(
      $this->hash_algorithm,
      $this->date_without_time,
      'AWS4' . $this->aws_secret_key,
      true
    );
    $region_key = hash_hmac(
      $this->hash_algorithm,
      $this->aws_region,
      $date_key,
      true
    );
    $service_key = hash_hmac(
      $this->hash_algorithm,
      $this->aws_service,
      $region_key,
      true
    );
    return hash_hmac(
      $this->hash_algorithm,
      $this->aws_termination_string,
      $service_key,
      true
    );
  }
}