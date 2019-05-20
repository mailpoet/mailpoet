<?php
namespace MailPoet\Newsletter;

use MailPoet\Router\Router;
use MailPoet\Router\Endpoints\ViewInBrowser as ViewInBrowserEndpoint;
use MailPoet\Models\Subscriber as SubscriberModel;

class Url {
  const TYPE_ARCHIVE = 'display_archive';
  const TYPE_LISTING_EDITOR = 'display_listing_editor';

  static function getViewInBrowserUrl(
    $type,
    $newsletter,
    $subscriber = false,
    $queue = false,
    $preview = false
  ) {
    if ($subscriber instanceof SubscriberModel) {
      $subscriber->token = SubscriberModel::generateToken($subscriber->email);
    }
    switch ($type) {
      case self::TYPE_ARCHIVE:
        // do not expose newsletter id when displaying archive newsletters
        $newsletter->id = null;
        $preview = true;
        break;
      case self::TYPE_LISTING_EDITOR:
        // enable preview when displaying from editor or listings
        $preview = true;
        break;
      default:
        // hide hash for all other display types
        $newsletter->hash = null;
        break;
    }
    $data = self::createUrlDataObject($newsletter, $subscriber, $queue, $preview);
    return Router::buildRequest(
      ViewInBrowserEndpoint::ENDPOINT,
      ViewInBrowserEndpoint::ACTION_VIEW,
      $data
    );
  }

  static function createUrlDataObject($newsletter, $subscriber, $queue, $preview) {
    return [
      (!empty($newsletter->id)) ?
        (int)$newsletter->id :
        0,
      (!empty($newsletter->hash)) ?
        $newsletter->hash :
        0,
      (!empty($subscriber->id)) ?
        (int)$subscriber->id :
        0,
      (!empty($subscriber->token)) ?
        $subscriber->token :
        0,
      (!empty($queue->id)) ?
        (int)$queue->id :
        0,
      (int)$preview,
    ];
  }

  static function transformUrlDataObject($data) {
    reset($data);
    if (!is_int(key($data))) return $data;
    $transformed_data = [];
    $transformed_data['newsletter_id'] = (!empty($data[0])) ? $data[0] : false;
    $transformed_data['newsletter_hash'] = (!empty($data[1])) ? $data[1] : false;
    $transformed_data['subscriber_id'] = (!empty($data[2])) ? $data[2] : false;
    $transformed_data['subscriber_token'] = (!empty($data[3])) ? $data[3] : false;
    $transformed_data['queue_id'] = (!empty($data[4])) ? $data[4] : false;
    $transformed_data['preview'] = (!empty($data[5])) ? $data[5] : false;
    return $transformed_data;
  }
}
