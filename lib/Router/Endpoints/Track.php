<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Track {
  const ENDPOINT = 'track';
  const ACTION_CLICK = 'click';
  const ACTION_OPEN = 'open';
  public $allowed_actions = [
    self::ACTION_CLICK,
    self::ACTION_OPEN,
  ];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var Clicks */
  private $clicks;

  /** @var Opens */
  private $opens;

  public function __construct(Clicks $clicks, Opens $opens) {
    $this->clicks = $clicks;
    $this->opens = $opens;
  }

  function click($data) {
    return $this->clicks->track($this->_processTrackData($data));
  }

  function open($data) {
    return $this->opens->track($this->_processTrackData($data));
  }

  function _processTrackData($data) {
    $data = (object)Links::transformUrlDataObject($data);
    if (empty($data->queue_id) ||
      empty($data->subscriber_id) ||
      empty($data->subscriber_token)
    ) {
      return false;
    }
    $data->queue = SendingQueue::findOne($data->queue_id);
    if ($data->queue) {
      $data->queue = SendingTask::createFromQueue($data->queue);
    }
    $data->subscriber = Subscriber::findOne($data->subscriber_id) ?: null;
    $data->newsletter = (!empty($data->queue->newsletter_id)) ?
      Newsletter::findOne($data->queue->newsletter_id) :
      false;
    if (!empty($data->link_hash)) {
      $data->link = NewsletterLink::where('hash', $data->link_hash)
        ->where('queue_id', $data->queue_id)
        ->findOne();
    }
    return $this->_validateTrackData($data);
  }

  function _validateTrackData($data) {
    if (!$data->subscriber || !$data->queue || !$data->newsletter) return false;
    $subscriber_token_match =
      Subscriber::verifyToken($data->subscriber->email, $data->subscriber_token);
    if (!$subscriber_token_match) {
      $this->terminate(403);
    }
    // return if this is a WP user previewing the newsletter
    if ($data->subscriber->isWPUser() && $data->preview) {
      return $data;
    }
    // check if the newsletter was sent to the subscriber
    return ($data->queue->isSubscriberProcessed($data->subscriber->id)) ?
      $data :
      false;
  }

  function terminate($code) {
    WPFunctions::get()->statusHeader($code);
    WPFunctions::get()->getTemplatePart((string)$code);
    exit;
  }
}
