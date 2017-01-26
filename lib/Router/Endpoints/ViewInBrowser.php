<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Config\Env;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-includes/pluggable.php');

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
    $data->newsletter = (!empty($data->newsletter_hash)) ?
      Newsletter::getByHash($data->newsletter_hash) :
      Newsletter::findOne($data->newsletter_id);
    if(!$data->newsletter) return false;

    // subscriber is optional; if exists, token must validate
    $data->subscriber = (!empty($data->subscriber_id)) ?
      Subscriber::findOne($data->subscriber_id) :
      false;
    if($data->subscriber) {
      if(empty($data->subscriber_token) ||
         !Subscriber::verifyToken($data->subscriber->email, $data->subscriber_token)
      ) return false;
    } else if(!$data->subscriber && !empty($data->preview)) {
      // if this is a preview and subscriber does not exist,
      // attempt to set subscriber to the current logged-in WP user
      $data->subscriber = Subscriber::getCurrentWPUser();
    }

    // if newsletter hash is not provided but newsletter ID is defined then subscriber must exist
    if(empty($data->newsletter_hash) && $data->newsletter_id && !$data->subscriber) return false;

    // queue is optional; try to find it if it's not defined and this is not a welcome email
    if($data->newsletter->type !== Newsletter::TYPE_WELCOME) {
      $data->queue = (!empty($data->queue_id)) ?
        SendingQueue::findOne($data->queue_id) :
        SendingQueue::where('newsletter_id', $data->newsletter->id)
          ->findOne();
    } else {
      $data->queue = false;
    }

    // allow users with 'manage_options' permission to preview any newsletter
    if(!empty($data->preview) && current_user_can(Env::$required_permission)
    ) return $data;

    // allow others to preview newsletters only when newsletter hash is defined
    if(!empty($data->preview) && empty($data->newsletter_hash)
    ) return false;

    // if queue and subscriber exist, subscriber must have received the newsletter
    if($data->queue &&
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