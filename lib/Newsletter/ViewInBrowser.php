<?php
namespace MailPoet\Newsletter;

use MailPoet\Models\Setting;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;

class ViewInBrowser {
  function view($data) {
    if(!$data) {
      return $this->abort();
    }
    $wp_user_preview = ($data->preview && $data->subscriber->isWPUser());
    $rendered_newsletter =
      $this->renderNewsletter(
        $data->newsletter,
        $data->subscriber,
        $data->queue,
        $wp_user_preview
      );
    return $this->displayNewsletter($rendered_newsletter);
  }

  function renderNewsletter($newsletter, $subscriber, $queue, $wp_user_preview) {
    if($queue && $queue->newsletter_rendered_body) {
      $newsletter_body = $queue->getRenderedNewsletterBody();
    } else {
      $renderer = new Renderer($newsletter, $wp_user_preview);
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
        $wp_user_preview
      );
    }
    return $rendered_newsletter;
  }

  function displayNewsletter($rendered_newsletter) {
    header('Content-Type: text/html; charset=utf-8');
    echo $rendered_newsletter;
    exit;
  }

  function abort() {
    status_header(404);
    exit;
  }
}