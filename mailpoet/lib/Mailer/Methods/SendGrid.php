<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class SendGrid implements MailerMethod {
  public $url = 'https://api.sendgrid.com/api/mail.send.json';
  public $apiKey;
  public $sender;
  public $replyTo;

  /** @var SendGridMapper */
  private $errorMapper;

  /** @var Url */
  private $urlUtils;

  /** @var BlacklistCheck */
  private $blacklist;

  private $wp;

  public function __construct(
    $apiKey,
    $sender,
    $replyTo,
    SendGridMapper $errorMapper,
    Url $urlUtils
  ) {
    $this->apiKey = $apiKey;
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->errorMapper = $errorMapper;
    $this->urlUtils = $urlUtils;
    $this->wp = new WPFunctions();
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []): array {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->errorMapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    $result = $this->wp->wpRemotePost(
      $this->url,
      $this->request($newsletter, $subscriber, $extraParams)
    );
    if (is_wp_error($result)) {
      $error = $this->errorMapper->getConnectionError($result->get_error_message());
      return Mailer::formatMailerErrorResult($error);
    }
    if ($this->wp->wpRemoteRetrieveResponseCode($result) !== 200) {
      $response = json_decode($result['body'], true);
      $error = $this->errorMapper->getErrorFromResponse($response, $subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  public function getBody($newsletter, $subscriber, $extraParams = []) {
    $body = [
      'to' => $subscriber,
      'from' => $this->sender['from_email'],
      'fromname' => $this->sender['from_name'],
      'replyto' => $this->replyTo['reply_to_email'],
      'subject' => $newsletter['subject'],
    ];
    $headers = [];

    // unsubscribe header
    $unsubscribeUrl = $extraParams['unsubscribe_url'] ?? null;
    $oneClickUnsubscribeUrl = $extraParams['one_click_unsubscribe'] ?? null;
    if ($unsubscribeUrl) {
      $isHttps = $this->urlUtils->isUsingHttps($unsubscribeUrl);
      $url = $isHttps && $oneClickUnsubscribeUrl ? $oneClickUnsubscribeUrl : $unsubscribeUrl;
      if ($isHttps) {
        $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
      }
      $headers['List-Unsubscribe'] = '<' . $url . '>';
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

  public function auth() {
    return 'Bearer ' . $this->apiKey;
  }

  public function request($newsletter, $subscriber, $extraParams = []) {
    $body = $this->getBody($newsletter, $subscriber, $extraParams);
    return [
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => [
        'Authorization' => $this->auth(),
      ],
      'body' => http_build_query($body, '', '&'),
    ];
  }
}
