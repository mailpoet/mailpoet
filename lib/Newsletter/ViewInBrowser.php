<?php
namespace MailPoet\Newsletter;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;

class ViewInBrowser {
  function view($data) {
    $wp_user_preview = (
      ($data->subscriber && $data->subscriber->isWPUser() && $data->preview) ||
      ($data->preview && $data->newsletter_hash)
    );
    return $this->renderNewsletter(
      $data->newsletter,
      $data->subscriber,
      $data->queue,
      $wp_user_preview
    );
  }

  function renderNewsletter($newsletter, $subscriber, $queue, $wp_user_preview) {
    if($queue && $queue->getNewsletterRenderedBody()) {
      $newsletter_body = $queue->getNewsletterRenderedBody('html');
      // rendered newsletter body has shortcodes converted to links; we need to
      // isolate "view in browser", "unsubscribe" and "manage subscription" links
      // and convert them to shortcodes, which later will be replaced with "#" when
      // newsletter is previewed
      if($wp_user_preview && preg_match(Links::getLinkRegex(), $newsletter_body)) {
        $newsletter_body = Links::convertHashedLinksToShortcodesAndUrls(
          $newsletter_body,
          $queue_id = $queue->id,
          $convert_all = true
        );
        // remove open tracking link
        $newsletter_body = str_replace(Links::DATA_TAG_OPEN, '', $newsletter_body);
      }
    } else {
      $renderer = new Renderer($newsletter, $wp_user_preview);
      $newsletter_body = $renderer->render('html');
    }
    $shortcodes = new Shortcodes(
      $newsletter,
      $subscriber,
      $queue,
      $wp_user_preview
    );
    $rendered_newsletter = $shortcodes->replace($newsletter_body);
    if(!$wp_user_preview && $queue && $subscriber && (boolean)Setting::getValue('tracking.enabled')) {
      $rendered_newsletter = Links::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $rendered_newsletter
      );
    }
    return $rendered_newsletter;
  }
}