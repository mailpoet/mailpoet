<?php
namespace MailPoet\Newsletter;

use MailPoet\Router\Front as FrontRouter;
use MailPoet\Router\ViewInBrowser as ViewInBrowserEndpoint;
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
    return FrontRouter::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      $data
    );
  }
}