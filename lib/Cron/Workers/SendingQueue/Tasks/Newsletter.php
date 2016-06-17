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
  static function get($newsletter_id) {
    $newsletter = NewsletterModel::findOne($newsletter_id);
    return ($newsletter) ? $newsletter->asArray() : false;
  }

  static function getAndPreProcess(array $queue) {
    $newsletter = self::get($queue['newsletter_id']);
    if(!$newsletter) {
      return false;
    }
    // if the newsletter was previously rendered, return it
    // otherwise, process/render it
    if(!is_null($queue['newsletter_rendered_body'])) {
      $newsletter['rendered_body'] = json_decode($queue['newsletter_rendered_body'], true);
      return $newsletter;
    }
    // if tracking is enabled, do additional processing
    if((boolean) Setting::getValue('tracking.enabled')) {
      // add tracking image
      OpenTracking::addTrackingImage();
      // render newsletter
      $newsletter = self::render($newsletter);
      // hash and save all links
      $newsletter = LinksTask::process($newsletter, $queue);
    } else {
      // render newsletter
      $newsletter = self::render($newsletter);
    }
    // check if this is a post notification and if it contains posts
    $newsletter_contains_posts = strpos($newsletter['rendered_body']['html'], 'data-post-id');
    if($newsletter['type'] === 'notification' && !$newsletter_contains_posts) {
      return false;
    }
    // save all posts
    $newsletter = PostsTask::extractAndSave($newsletter);
    return $newsletter;
  }

  static function render($newsletter) {
    $renderer = new Renderer($newsletter);
    $newsletter['rendered_body'] = $renderer->render();
    return $newsletter;
  }

  static function prepareNewsletterForSending(
    array $newsletter, array $subscriber, array $queue
  ) {
    // shortcodes and links will be replaced in the subject, html and text body
    // to speed the processing, join content into a continuous string
    $prepared_newsletter = Helpers::joinObject(
      array(
        $newsletter['subject'],
        $newsletter['rendered_body']['html'],
        $newsletter['rendered_body']['text']
      )
    );
    $prepared_newsletter = ShortcodesTask::process(
      $prepared_newsletter,
      $newsletter,
      $subscriber,
      $queue
    );
    if((boolean) Setting::getValue('tracking.enabled')) {
      $prepared_newsletter = NewsletterLinks::replaceSubscriberData(
        $newsletter['id'],
        $subscriber['id'],
        $queue['id'],
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
}