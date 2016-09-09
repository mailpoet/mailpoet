<?php
namespace MailPoet\Newsletter;

use MailPoet\Models\SendingQueue;
use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\ViewInBrowser as ViewInBrowserEndpoint;
use MailPoet\Models\Subscriber;

class Url {
  static function getViewInBrowserUrl(
    $newsletter,
    $subscriber = false,
    $queue = false,
    $preview = false
  ) {
    if(is_object($newsletter)) {
      $newsletter = $newsletter->asArray();
    }
    if(is_object($subscriber)) {
      $subscriber = $subscriber->asArray();
    } else if(!$subscriber) {
      $subscriber = Subscriber::getCurrentWPUser();
      $subscriber = ($subscriber) ? $subscriber->asArray() : false;
    }
    if(is_object($queue)) {
      $queue = $queue->asArray();
    } else if(!$preview && !empty($newsletter['id'])) {
      $queue = SendingQueue::where('newsletter_id', $newsletter['id'])->findOne();
      $queue = ($queue) ? $queue->asArray() : false;
    }
    $data = array(
      'newsletter_id' => (!empty($newsletter['id'])) ?
        $newsletter['id'] :
        $newsletter,
      'subscriber_id' => (!empty($subscriber['id'])) ?
        $subscriber['id'] :
        $subscriber,
      'subscriber_token' => (!empty($subscriber['id'])) ?
        Subscriber::generateToken($subscriber['email']) :
        false,
      'queue_id' => (!empty($queue['id'])) ?
        $queue['id'] :
        $queue,
      'preview' => $preview
    );
    return Router::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      $data
    );
  }
}