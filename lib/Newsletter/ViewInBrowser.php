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

  function __construct($data) {
    $this->data = $data;
  }

  function view($data = false) {
    $data = ($data) ? $data : $this->data;
    $newsletter = ($data['newsletter'] !== false) ?
      Newsletter::findOne($data['newsletter']) :
      false;
    if(!$newsletter) $this->abort();
    $subscriber = ($data['subscriber'] !== false) ?
      $this->verifySubscriber($data['subscriber'], $data['subscriber_token']) :
      false;
    $queue = ($data['queue'] !== false) ?
      SendingQueue::findOne($data['queue']) :
      false;
    $rendered_newsletter =
      $this->getAndRenderNewsletter($newsletter, $subscriber, $queue);
    header('Content-Type: text/html; charset=utf-8');
    echo $rendered_newsletter;
    exit;
  }

  function verifySubscriber($subscriber_id, $subscriber_token) {
    $subscriber = Subscriber::findOne($subscriber_id);
    if(!$subscriber ||
      !Subscriber::verifyToken($subscriber->email, $subscriber_token)
    ) {
      return false;
    }
    return $subscriber;
  }

  function getAndRenderNewsletter($newsletter, $subscriber, $queue) {
    if($queue) {
      $newsletter_body = json_decode($queue->newsletter_rendered_body, true);
    } else {
      $renderer = new Renderer($newsletter->asArray());
      $newsletter_body = $renderer->render();
    }
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue
    );
    $rendered_newsletter = $shortcodes->replace($newsletter_body['html']);
    if($queue && (boolean) Setting::getValue('tracking.enabled')) {
      $rendered_newsletter = Links::replaceSubscriberData(
        $newsletter->id,
        $subscriber->id,
        $queue->id,
        $rendered_newsletter
      );
    }
    return $rendered_newsletter;
  }

  private function abort() {
    header('HTTP/1.0 404 Not Found');
    exit;
  }
}