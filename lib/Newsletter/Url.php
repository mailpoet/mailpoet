<?php
namespace MailPoet\Newsletter;

use MailPoet\API\API;
use MailPoet\API\Endpoints\ViewInBrowser;
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
      API::API_NAME,
      'endpoint' => ViewInBrowser::ENDPOINT,
      'action' => ViewInBrowser::ACTION_VIEW,
      'data' => base64_encode(serialize($data))
    );
    return add_query_arg($params, home_url());
  }
}