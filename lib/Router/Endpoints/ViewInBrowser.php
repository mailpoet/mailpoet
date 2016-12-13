<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;

if(!defined('ABSPATH')) exit;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';
  public $allowed_actions = array(self::ACTION_VIEW);
  public $data;

  function __construct($data) {
    $this->data = $this->_processBrowserPreviewData($data);
  }

  function view() {
    $view_in_browser = new NewsletterViewInBrowser();
    return $this->_displayNewsletter($view_in_browser->view($this->data));
  }

  function _processBrowserPreviewData($data) {
    $data = (object)NewsletterUrl::transformUrlDataObject($data);
    return ($this->_validateBrowserPreviewData($data)) ?
      $data :
      $this->_abort();
  }

  function _validateBrowserPreviewData($data) {
    // either newsletter ID or hash must be defined, and newsletter must exist
    if(empty($data->newsletter_id) && empty($data->newsletter_hash)) return false;
    $data->newsletter = (!empty($data->newsletter_id)) ?
      Newsletter::findOne($data->newsletter_id) :
      Newsletter::getByHash($data->newsletter_hash);
    if(!$data->newsletter) return false;

    // queue is optional; if defined, get it
    $data->queue = (!empty($data->queue_id)) ?
      SendingQueue::findOne($data->queue_id) :
      SendingQueue::where('newsletter_id', $data->newsletter->id)->findOne();

    // subscriber is optional; if exists, token must validate
    $data->subscriber = (!empty($data->subscriber_id)) ?
      Subscriber::findOne($data->subscriber_id) :
      false;
    if($data->subscriber) {
      if(empty($data->subscriber_token) ||
         !Subscriber::verifyToken($data->subscriber->email, $data->subscriber_token)
      ) return false;
    }
    // if queue and subscriber exist and newsletter is not being previewed,
    // subscriber must have received the newsletter
    if(empty($data->preview) &&
       $data->queue &&
       $data->subscriber &&
       !$data->queue->isSubscriberProcessed($data->subscriber->id)
    ) return false;

    return $data;
  }

  function _displayNewsletter($result) {
    header('Content-Type: text/html; charset=utf-8');
    echo $result;
    exit;
  }

  function _abort() {
    status_header(404);
    exit;
  }
}