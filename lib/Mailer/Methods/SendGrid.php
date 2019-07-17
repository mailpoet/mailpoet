<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class SendGrid {
  public $url = 'https://api.sendgrid.com/api/mail.send.json';
  public $api_key;
  public $sender;
  public $reply_to;

  /** @var SendGridMapper */
  private $error_mapper;

  /** @var BlacklistCheck */
  private $blacklist;

  private $wp;

  function __construct($api_key, $sender, $reply_to, SendGridMapper $error_mapper) {
    $this->api_key = $api_key;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->error_mapper = $error_mapper;
    $this->wp = new WPFunctions();
    $this->blacklist = new BlacklistCheck();
  }

  function send($newsletter, $subscriber, $extra_params = []) {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->error_mapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    $result = $this->wp->wpRemotePost(
      $this->url,
      $this->request($newsletter, $subscriber, $extra_params)
    );
    if (is_wp_error($result)) {
      $error = $this->error_mapper->getConnectionError($result->get_error_message());
      return Mailer::formatMailerErrorResult($error);
    }
    if ($this->wp->wpRemoteRetrieveResponseCode($result) !== 200) {
      $response = json_decode($result['body'], true);
      $error = $this->error_mapper->getErrorFromResponse($response, $subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  function getBody($newsletter, $subscriber, $extra_params = []) {
    $body = [
      'to' => $subscriber,
      'from' => $this->sender['from_email'],
      'fromname' => $this->sender['from_name'],
      'replyto' => $this->reply_to['reply_to_email'],
      'subject' => $newsletter['subject'],
    ];
    $headers = [];
    if (!empty($extra_params['unsubscribe_url'])) {
      $headers['List-Unsubscribe'] = '<' . $extra_params['unsubscribe_url'] . '>';
    }
    if ($headers) {
      $body['headers'] = json_encode($headers);
    }
    if (!empty($newsletter['body']['html'])) {
      $body['html'] = $newsletter['body']['html'];
    }
    if (!empty($newsletter['body']['text'])) {
      $body['text'] = $newsletter['body']['text'];
    }
    return $body;
  }

  function auth() {
    return 'Bearer ' . $this->api_key;
  }

  function request($newsletter, $subscriber, $extra_params = []) {
    $body = $this->getBody($newsletter, $subscriber, $extra_params);
    return [
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => [
        'Authorization' => $this->auth(),
      ],
      'body' => http_build_query($body, null, '&'),
    ];
  }
}
