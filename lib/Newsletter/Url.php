<?php
namespace MailPoet\Newsletter;

use MailPoet\Models\Subscriber;

class Url {
  static function getViewInBrowserUrl(
    $newsletter,
    $subscriber = false,
    $queue = false
  ) {
    if(is_object($newsletter)) {
      $newsletter = $newsletter->asArray();
    }
    if(is_object($subscriber)) {
      $subscriber = $subscriber->asArray();
    }
    if(is_object($queue)) {
      $queue = $queue->asArray();
    }
    $data = array(
      'newsletter' => (!empty($newsletter['id'])) ?
        $newsletter['id'] :
        $newsletter,
      'subscriber' => (!empty($subscriber['id'])) ?
        $subscriber['id'] :
        $subscriber,
      'subscriber_token' => (!empty($subscriber['id'])) ?
        Subscriber::generateToken($subscriber['email']) :
        false,
      'queue' => (!empty($queue['id'])) ?
        $queue['id'] :
        $queue
    );
    $params = array(
      'mailpoet',
      'endpoint=view_in_browser',
      'data=' . rtrim(base64_encode(serialize($data)), '=')
    );
    $url = home_url();
    return $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);
  }
}