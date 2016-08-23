<?php
namespace MailPoet\Newsletter;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;

class ViewInBrowser {
  static function view($data) {
    $data = self::preProcessData($data);
    if(!self::validateData($data)) self::abort();
    $rendered_newsletter =
      self::getAndRenderNewsletter(
        $data->newsletter,
        $data->subscriber,
        $data->queue,
        $data->preview
      );
    header('Content-Type: text/html; charset=utf-8');
    echo $rendered_newsletter;
    exit;
  }

  static function preProcessData($data) {
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
    return $data;
  }

  static function validateData($data) {
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

  static function getAndRenderNewsletter($newsletter, $subscriber, $queue, $preview) {
    if($queue && $queue->newsletter_rendered_body) {
      $newsletter_body = $queue->getRenderedNewsletterBody();
    } else {
      $renderer = new Renderer($newsletter, $preview);
      $newsletter_body = $renderer->render();
    }
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue
    );
    $rendered_newsletter = $shortcodes->replace($newsletter_body['html']);
    if($queue && (boolean)Setting::getValue('tracking.enabled')) {
      $rendered_newsletter = Links::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $rendered_newsletter,
        $preview
      );
    }
    return $rendered_newsletter;
  }

  private static function abort() {
    status_header(404);
    exit;
  }
}