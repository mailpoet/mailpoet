<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;

if(!defined('ABSPATH')) exit;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';

  static function view($data) {
    $view_in_browser = new NewsletterViewInBrowser();
    $view_in_browser->view(self::_processBrowserPreviewData($data));
  }

  static function _processBrowserPreviewData($data) {
    $data = (object)$data;
    if(empty($data->subscriber_id) ||
      empty($data->subscriber_token) ||
      empty($data->newsletter_id)
    ) {
      return false;
    }
    $data->newsletter = Newsletter::findOne($data->newsletter_id);
    $data->subscriber = Subscriber::findOne($data->subscriber_id);
    $data->queue = ($data->queue_id) ?
      SendingQueue::findOne($data->queue_id) :
      false;
    return self::_validateBrowserPreviewData($data);
  }

  static function _validateBrowserPreviewData($data) {
    if(!$data || !$data->subscriber || !$data->newsletter) return false;
    $subscriber_token_match =
      Subscriber::verifyToken($data->subscriber->email, $data->subscriber_token);
    if(!$subscriber_token_match) return false;
    // return if this is a WP user previewing the newsletter
    if($data->subscriber->isWPUser() && $data->preview) {
      return $data;
    }
    // if queue exists, check if the newsletter was sent to the subscriber
    if($data->queue && !$data->queue->isSubscriberProcessed($data->subscriber->id)) {
      $data = false;
    }
    return $data;
  }
}