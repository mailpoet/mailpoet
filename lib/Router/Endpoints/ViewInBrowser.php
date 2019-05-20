<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Newsletter\ViewInBrowser as NewsletterViewInBrowser;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';
  public $allowed_actions = [self::ACTION_VIEW];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];
  /** @var AccessControl */
  private $access_control;

  /** @var SettingsController */
  private $settings;

  function __construct(AccessControl $access_control, SettingsController $settings) {
    $this->access_control = $access_control;
    $this->settings = $settings;
  }

  function view($data) {
    $data = $this->_processBrowserPreviewData($data);
    $view_in_browser = new NewsletterViewInBrowser((bool)$this->settings->get('tracking.enabled'));
    return $this->_displayNewsletter($view_in_browser->view($data));
  }

  function _processBrowserPreviewData($data) {
    $data = (object)NewsletterUrl::transformUrlDataObject($data);
    return ($this->_validateBrowserPreviewData($data)) ?
      $data :
      $this->_abort();
  }

  /**
   * @param \stdClass $data
   * @return bool|\stdClass
   */
  function _validateBrowserPreviewData($data) {
    // either newsletter ID or hash must be defined, and newsletter must exist
    if (empty($data->newsletter_id) && empty($data->newsletter_hash)) return false;
    $data->newsletter = (!empty($data->newsletter_hash)) ?
      Newsletter::getByHash($data->newsletter_hash) :
      Newsletter::findOne($data->newsletter_id);
    if (!$data->newsletter) return false;

    // subscriber is optional; if exists, token must validate
    $data->subscriber = (!empty($data->subscriber_id)) ?
      Subscriber::findOne($data->subscriber_id) :
      false;
    if ($data->subscriber) {
      if (empty($data->subscriber_token) ||
         !Subscriber::verifyToken($data->subscriber->email, $data->subscriber_token)
      ) return false;
    } else if (!$data->subscriber && !empty($data->preview)) {
      // if this is a preview and subscriber does not exist,
      // attempt to set subscriber to the current logged-in WP user
      $data->subscriber = Subscriber::getCurrentWPUser();
    }

    // if newsletter hash is not provided but newsletter ID is defined then subscriber must exist
    if (empty($data->newsletter_hash) && $data->newsletter_id && !$data->subscriber) return false;

    // queue is optional; try to find it if it's not defined and this is not a welcome email
    if ($data->newsletter->type !== Newsletter::TYPE_WELCOME) {
      $data->queue = (!empty($data->queue_id)) ?
        SendingQueue::findOne($data->queue_id) :
        SendingQueue::where('newsletter_id', $data->newsletter->id)
          ->findOne();
    } else {
      $data->queue = false;
    }

    // reset queue when automatic email is being previewed
    if ($data->newsletter->type === Newsletter::TYPE_AUTOMATIC && !empty($data->preview)) {
      $data->queue = false;
    }

    // allow users with permission to manage emails to preview any newsletter
    if (!empty($data->preview) && $this->access_control->validatePermission(AccessControl::PERMISSION_MANAGE_EMAILS)
    ) return $data;

    // allow others to preview newsletters only when newsletter hash is defined
    if (!empty($data->preview) && empty($data->newsletter_hash)
    ) return false;

    // if queue and subscriber exist, subscriber must have received the newsletter
    if ($data->queue &&
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
    WPFunctions::get()->statusHeader(404);
    exit;
  }
}
