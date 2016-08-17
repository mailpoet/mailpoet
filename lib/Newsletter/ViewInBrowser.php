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
  public $data;

  static function view($data) {
    $data = self::processData($data);
    if(!$data) return false;
    $rendered_newsletter =
      self::getAndRenderNewsletter(
        $data['newsletter'],
        $data['subscriber'],
        $data['queue'],
        $data['preview']
      );
    header('Content-Type: text/html; charset=utf-8');
    echo $rendered_newsletter;
    exit;
  }

  static function processData($data) {
    if(empty($data['subscriber_id']) ||
      empty($data['subscriber_token']) ||
      empty($data['newsletter_id'])
    ) {
      return false;
    }
    $data['newsletter'] = self::getNewsletter($data['newsletter_id']);
    $data['subscriber'] = self::getSubscriber($data['subscriber_id']);
    $data['queue'] = self::getQueue($data['queue_id']);
    $data_processed_successfully =
      ($data['subscriber'] && $data['newsletter']);
    return ($data_processed_successfully) ?
      self::validateData($data) :
      false;
  }

  static function validateData($data) {
    if(!$data['subscriber']) return false;
    $subscriber_token_match =
      Subscriber::verifyToken($data['subscriber']['email'], $data['subscriber_token']);
    // return if this is an administrator user previewing the newsletter
    if($data['subscriber']['wp_user_id'] && $subscriber_token_match && $data['preview']) {
      return ($subscriber_token_match) ? $data : false;
    }
    // if queue exists, check if the newsletter was sent to the subscriber
    if($data['queue'] && $data['subscriber']) {
      $is_valid_subscriber =
        (!empty($data['queue']['subscribers']['processed']) &&
          in_array($data['subscriber']['id'], $data['queue']['subscribers']['processed']));
      $data = ($is_valid_subscriber && $subscriber_token_match) ? $data : false;
    } else {
      $data = ($subscriber_token_match) ? $data : false;
    }
    return $data;
  }

  static function getNewsletter($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    return ($newsletter) ? $newsletter->asArray() : $newsletter;
  }

  static function getQueue($queue_id) {
    $queue = SendingQueue::findOne($queue_id);
    return ($queue) ? $queue->asArray() : $queue;
  }

  static function getSubscriber($subscriber_id) {
    $subscriber = Subscriber::findOne($subscriber_id);
    return ($subscriber) ? $subscriber->asArray() : $subscriber;
  }

  static function getAndRenderNewsletter($newsletter, $subscriber, $queue, $preview) {
    if($queue && $queue['newsletter_rendered_body']) {
      $newsletter_body = json_decode($queue['newsletter_rendered_body'], true);
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
        $subscriber['id'],
        $queue['id'],
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