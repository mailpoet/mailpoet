<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
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

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  public function __construct(
    Clicks $clicks,
    Opens $opens,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersRepository $subscribersRepository,
    NewslettersRepository $newslettersRepository,
    NewsletterLinkRepository $newsletterLinkRepository,
    LinkTokens $linkTokens
  ) {
    $this->clicks = $clicks;
    $this->opens = $opens;
    $this->linkTokens = $linkTokens;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
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
    $data->queue = $this->sendingQueuesRepository->findOneById($data->queue_id);// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data->subscriber = $this->subscribersRepository->findOneById($data->subscriber_id); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data->newsletter = (isset($data->newsletter_id)) ? $this->newslettersRepository->findOneById($data->newsletter_id) : null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (!$data->newsletter && ($data->queue instanceof SendingQueueEntity)) {
      $data->newsletter = $data->queue->getNewsletter();
    }
    if (!empty($data->link_hash)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      $data->link = $this->newsletterLinkRepository->findOneBy([
        'hash' => $data->link_hash, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        'queue' => $data->queue_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ]);
    }
    return $this->_validateTrackData($data);
  }

  public function _validateTrackData($data) {
    if (!$data->subscriber || !$data->queue || !$data->newsletter) return false;
    $subscriberModel = Subscriber::findOne($data->subscriber->getId());
    $subscriberTokenMatch = $this->linkTokens->verifyToken($subscriberModel, $data->subscriber_token); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (!$subscriberTokenMatch) {
      $this->terminate(403);
    }
    // return if this is a WP user previewing the newsletter
    if ($subscriberModel->isWPUser() && $data->preview) {
      return $data;
    }
    // check if the newsletter was sent to the subscriber
    $queue = SendingQueue::findOne($data->queue_id); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (!$queue instanceof SendingQueue) return false;

    return ($queue->isSubscriberProcessed($data->subscriber->getId())) ?
      $data :
      false;
  }

  public function terminate($code) {
    WPFunctions::get()->statusHeader($code);
    WPFunctions::get()->getTemplatePart((string)$code);
    exit;
  }
}
