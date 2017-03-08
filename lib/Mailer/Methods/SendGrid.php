<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;

if(!defined('ABSPATH')) exit;

class SendGrid {
  public $url = 'https://api.sendgrid.com/api/mail.send.json';
  public $api_key;
  public $sender;
  public $reply_to;

  function __construct($api_key, $sender, $reply_to) {
    $this->api_key = $api_key;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
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
      $response = json_decode($result['body'], true);
      $response = (!empty($response['errors'][0])) ?
        $response['errors'][0] :
        sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SENDGRID);
      if(empty($extra_params['test_email'])) {
        $response .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
      }
      return Mailer::formatMailerSendErrorResult($response);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  function getBody($newsletter, $subscriber, $extra_params = array()) {
    $body = array(
      'to' => $subscriber,
      'from' => $this->sender['from_email'],
      'fromname' => $this->sender['from_name'],
      'replyto' => $this->reply_to['reply_to_email'],
      'subject' => $newsletter['subject']
    );
    $headers = array();
    if(!empty($extra_params['unsubscribe_url'])) {
      $headers['List-Unsubscribe'] = '<' . $extra_params['unsubscribe_url'] . '>';
    }
    if($headers) {
      $body['headers'] = json_encode($headers);
    }
    if(!empty($newsletter['body']['html'])) {
      $body['html'] = $newsletter['body']['html'];
    }
    if(!empty($newsletter['body']['text'])) {
      $body['text'] = $newsletter['body']['text'];
    }
    return $body;
  }

  function auth() {
    return 'Bearer ' . $this->api_key;
  }

  function request($newsletter, $subscriber, $extra_params = array()) {
    $body = $this->getBody($newsletter, $subscriber, $extra_params);
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => $this->auth()
      ),
      'body' => http_build_query($body, null, '&')
    );
  }
}