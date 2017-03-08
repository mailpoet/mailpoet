<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;

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
  public $return_path;
  public $message;
  public $date;
  public $date_without_time;
  private $available_regions = array(
    'US East (N. Virginia)' => 'us-east-1',
    'US West (Oregon)' => 'us-west-2',
    'EU (Ireland)' => 'eu-west-1'
  );

  function __construct($region, $access_key, $secret_key, $sender, $reply_to, $return_path) {
    $this->aws_access_key = $access_key;
    $this->aws_secret_key = $secret_key;
    $this->aws_region = (in_array($region, $this->available_regions)) ? $region : false;
    if(!$this->aws_region) {
      throw new \Exception(__('Unsupported Amazon SES region', 'mailpoet'));
    }
    $this->aws_endpoint = sprintf('email.%s.amazonaws.com', $this->aws_region);
    $this->aws_signing_algorithm = 'AWS4-HMAC-SHA256';
    $this->aws_service = 'ses';
    $this->aws_termination_string = 'aws4_request';
    $this->hash_algorithm = 'sha256';
    $this->url = 'https://' . $this->aws_endpoint;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->return_path = ($return_path) ?
      $return_path :
      $this->sender['from_email'];
    $this->date = gmdate('Ymd\THis\Z');
    $this->date_without_time = gmdate('Ymd');
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber, $extra_params)
    );
    if(is_wp_error($result)) {
      return Mailer::formatMailerConnectionErrorResult($result->get_error_message());
    }
    if(wp_remote_retrieve_response_code($result) !== 200) {
      $response = simplexml_load_string(wp_remote_retrieve_body($result));
      $response = ($response) ?
        $response->Error->Message->__toString() :
        sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_AMAZONSES);
      if(empty($extra_params['test_email'])) {
        $response .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
      }
      return Mailer::formatMailerSendErrorResult($response);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  function getBody($newsletter, $subscriber, $extra_params = array()) {
    $this->message = $this->createMessage($newsletter, $subscriber, $extra_params);
    $body = array(
      'Action' => 'SendRawEmail',
      'Version' => '2010-12-01',
      'Source' => $this->sender['from_name_email'],
      'RawMessage.Data' => $this->encodeMessage($this->message)
    );
    return $body;
  }

  function createMessage($newsletter, $subscriber, $extra_params = array()) {
    $message = \Swift_Message::newInstance()
      ->setTo($this->processSubscriber($subscriber))
      ->setFrom(array(
          $this->sender['from_email'] => $this->sender['from_name']
        ))
      ->setSender($this->sender['from_email'])
      ->setReplyTo(array(
          $this->reply_to['reply_to_email'] =>  $this->reply_to['reply_to_name']
        ))
      ->setReturnPath($this->return_path)
      ->setSubject($newsletter['subject']);
    if(!empty($extra_params['unsubscribe_url'])) {
      $headers = $message->getHeaders();
      $headers->addTextHeader('List-Unsubscribe', '<' . $extra_params['unsubscribe_url'] . '>');
    }
    if(!empty($newsletter['body']['html'])) {
      $message = $message->setBody($newsletter['body']['html'], 'text/html');
    }
    if(!empty($newsletter['body']['text'])) {
      $message = $message->addPart($newsletter['body']['text'], 'text/plain');
    }
    return $message;
  }

  function encodeMessage(\Swift_Message $message) {
    return base64_encode($message->toString());
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if(!isset($subscriber_data['email'])) {
      $subscriber_data = array(
        'email' => $subscriber,
      );
    }
    return array(
      $subscriber_data['email'] =>
        (isset($subscriber_data['name'])) ? $subscriber_data['name'] : ''
    );
  }

  function request($newsletter, $subscriber, $extra_params = array()) {
    $body = array_map('urlencode', $this->getBody($newsletter, $subscriber, $extra_params));
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Host' => $this->aws_endpoint,
        'Authorization' => $this->signRequest($body),
        'X-Amz-Date' => $this->date
      ),
      'body' => urldecode(http_build_query($body, null, '&'))
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
      hash($this->hash_algorithm, urldecode(http_build_query($body, null, '&')))
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
