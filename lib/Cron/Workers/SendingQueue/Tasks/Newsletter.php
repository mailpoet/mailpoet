<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\Setting;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Newsletter {
  public $tracking_enabled;
  public $tracking_image_inserted;

  function __construct() {
    $this->tracking_enabled = (boolean)Setting::getValue('tracking.enabled');
  }

  function getAndPreProcess($queue) {
    $newsletter = $queue->getNewsletter();
    if(!$newsletter) {
      return false;
    }
    // if the newsletter was previously rendered, return it
    // otherwise, process/render it
    if(!is_null($queue->newsletter_rendered_body)) {
      $newsletter->_transient->rendered_body = $queue->getRenderedNewsletterBody();
      return $newsletter;
    }
    // if tracking is enabled, do additional processing
    if($this->tracking_enabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->tracking_image_inserted = OpenTracking::addTrackingImage();
      // render newsletter
      $newsletter->_transient->rendered_body = $newsletter->render();
      // hash and save all links
      $newsletter = LinksTask::process($newsletter, $queue);
    } else {
      // render newsletter
      $newsletter->_transient->rendered_body = $newsletter->render();
    }
    // check if this is a post notification and if it contains posts
    $newsletter_contains_posts = strpos($newsletter->_transient->rendered_body['html'], 'data-post-id');
    if($newsletter->type === 'notification' && !$newsletter_contains_posts) {
      return false;
    }
    // extract and save newsletter posts
    PostsTask::extractAndSave($newsletter);
    return $newsletter;
  }

  function prepareNewsletterForSending($newsletter, $subscriber, $queue) {
    // shortcodes and links will be replaced in the subject, html and text body
    // to speed the processing, join content into a continuous string
    $prepared_newsletter = Helpers::joinObject(
      array(
        $newsletter->subject,
        $newsletter->_transient->rendered_body['html'],
        $newsletter->_transient->rendered_body['text']
      )
    );
    $prepared_newsletter = ShortcodesTask::process(
      $prepared_newsletter,
      $newsletter,
      $subscriber,
      $queue
    );
    if($this->tracking_enabled) {
      $prepared_newsletter = NewsletterLinks::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $prepared_newsletter
      );
    }
    $prepared_newsletter = Helpers::splitObject($prepared_newsletter);
    return array(
      'subject' => $prepared_newsletter[0],
      'body' => array(
        'html' => $prepared_newsletter[1],
        'text' => $prepared_newsletter[2]
      )
    );
  }

  function markNewsletterAsSent($newsletter) {
    // if it's a standard newsletter, update its status
    if($newsletter->type === NewsletterModel::TYPE_STANDARD) {
      $newsletter->setStatus(NewsletterModel::STATUS_SENT);
    }
  }
}