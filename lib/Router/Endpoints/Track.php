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
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Functions as WPFunctions;

class Track {
  const ENDPOINT = 'track';
  const ACTION_CLICK = 'click';
  const ACTION_OPEN = 'open';
  public $allowedActions = [
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

  /** @var LinkTokens */
  private $linkTokens;

  public function __construct(Clicks $clicks, Opens $opens, LinkTokens $linkTokens) {
    $this->clicks = $clicks;
    $this->opens = $opens;
    $this->linkTokens = $linkTokens;
  }

  public function click($data) {
    return $this->clicks->track($this->_processTrackData($data));
  }

  public function open($data) {
    return $this->opens->track($this->_processTrackData($data));
  }

  public function _processTrackData($data) {
    $data = (object)Links::transformUrlDataObject($data);
    if (empty($data->queue_id) || // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      empty($data->subscriber_id) || // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      empty($data->subscriber_token) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    ) {
      return false;
    }
    $data->queue = SendingQueue::findOne($data->queue_id); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if ($data->queue instanceof SendingQueue) {
      $data->queue = SendingTask::createFromQueue($data->queue);
    }
    $data->subscriber = Subscriber::findOne($data->subscriber_id) ?: null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data->newsletter = (!empty($data->queue->newsletter_id)) ? // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      Newsletter::findOne($data->queue->newsletter_id) : // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      false;
    if (!empty($data->link_hash)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      $data->link = NewsletterLink::where('hash', $data->link_hash) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        ->where('queue_id', $data->queue_id) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        ->findOne();
    }
    return $this->_validateTrackData($data);
  }

  public function _validateTrackData($data) {
    if (!$data->subscriber || !$data->queue || !$data->newsletter) return false;
    $subscriberTokenMatch = $this->linkTokens->verifyToken($data->subscriber, $data->subscriber_token); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (!$subscriberTokenMatch) {
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

  public function terminate($code) {
    WPFunctions::get()->statusHeader($code);
    WPFunctions::get()->getTemplatePart((string)$code);
    exit;
  }
}
