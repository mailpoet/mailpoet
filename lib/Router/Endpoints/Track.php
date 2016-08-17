<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;

if(!defined('ABSPATH')) exit;

class Track {
  const ENDPOINT = 'track';
  const ACTION_CLICK = 'click';
  const ACTION_OPEN = 'open';

  static function click($data) {
    Clicks::track(self::_processTrackData($data));
  }

  static function open($data) {
    Opens::track(self::_processTrackData($data));
  }

  static function _processTrackData($data) {
    if(empty($data['queue_id']) ||
       empty($data['subscriber_id']) ||
       empty($data['subscriber_token'])
    ) {
      return false;
    }
    $data['queue'] = self::_getQueue($data['queue_id']);
    $data['subscriber'] = self::_getSubscriber($data['subscriber_id']);
    $data['newsletter'] = (!empty($data['queue']['newsletter_id'])) ?
      self::_getNewsletter($data['queue']['newsletter_id']) :
      false;
    if(!empty($data['link_hash'])) {
      $data['link'] = self::_getLink($data['link_hash']);
    }
    $data_processed_successfully =
      ($data['queue'] && $data['subscriber'] && $data['newsletter']);
    return ($data_processed_successfully) ?
      self::_validateTrackData($data) :
      false;
  }

  static function _validateTrackData($data) {
    if(!$data['subscriber']) return false;
    $subscriber_token_match =
      Subscriber::verifyToken($data['subscriber']['email'], $data['subscriber_token']);
    // return if this is an administrator user previewing the newsletter
    if($data['subscriber']['wp_user_id'] && $data['preview']) {
      return ($subscriber_token_match) ? $data : false;
    }
    // check if the newsletter was sent to the subscriber
    $is_valid_subscriber =
      (!empty($data['queue']['subscribers']['processed']) &&
        in_array($data['subscriber']['id'], $data['queue']['subscribers']['processed']));
    return ($is_valid_subscriber && $subscriber_token_match) ? $data : false;
  }

  static function _getNewsletter($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    return ($newsletter) ? $newsletter->asArray() : $newsletter;
  }

  static function _getSubscriber($subscriber_id) {
    $subscriber = Subscriber::findOne($subscriber_id);
    return ($subscriber) ? $subscriber->asArray() : $subscriber;
  }

  static function _getQueue($queue_id) {
    $queue = SendingQueue::findOne($queue_id);
    return ($queue) ? $queue->asArray() : $queue;
  }

  static function _getLink($link_hash) {
    $link = NewsletterLink::where('hash', $link_hash)
      ->findOne();
    return ($link) ? $link->asArray() : $link;
  }
}