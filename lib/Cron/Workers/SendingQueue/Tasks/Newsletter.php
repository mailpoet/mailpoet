<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterSegment as NewsletterSegmentModel;
use MailPoet\Models\Setting;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Util\Helpers;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class Newsletter {
  public $tracking_enabled;
  public $tracking_image_inserted;

  function __construct() {
    $this->tracking_enabled = (boolean)Setting::getValue('tracking.enabled');
  }

  function getNewsletterFromQueue($queue) {
    // get existing active or sending newsletter
    $newsletter = $queue->newsletter()
      ->whereNull('deleted_at')
      ->whereAnyIs(array(
        array('status' => NewsletterModel::STATUS_ACTIVE),
        array('status' => NewsletterModel::STATUS_SENDING)
      ))
      ->findOne();
    if(!$newsletter) return false;
    // if this is a notification history, get existing active or sending parent newsletter
    if($newsletter->type == NewsletterModel::TYPE_NOTIFICATION_HISTORY) {
      $parent_newsletter = $newsletter->parent()
        ->whereNull('deleted_at')
        ->whereAnyIs(array(
          array('status' => NewsletterModel::STATUS_ACTIVE),
          array('status' => NewsletterModel::STATUS_SENDING)
        ))
        ->findOne();
      if(!$parent_newsletter) return false;
    }
    return $newsletter;
  }

  function preProcessNewsletter($newsletter, $queue) {
    // return the newsletter if it was previously rendered
    if(!is_null($queue->getNewsletterRenderedBody())) {
      return $newsletter;
    }
    // if tracking is enabled, do additional processing
    if($this->tracking_enabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->tracking_image_inserted = OpenTracking::addTrackingImage();
      // render newsletter
      $rendered_newsletter = $newsletter->render();
      $rendered_newsletter = Hooks::applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $rendered_newsletter,
        $newsletter
      );
      // hash and save all links
      $rendered_newsletter = LinksTask::process($rendered_newsletter, $newsletter, $queue);
    } else {
      // render newsletter
      $rendered_newsletter = $newsletter->render();
      $rendered_newsletter = Hooks::applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $rendered_newsletter,
        $newsletter
      );
    }
    // check if this is a post notification and if it contains posts
    $newsletter_contains_posts = strpos($rendered_newsletter['html'], 'data-post-id');
    if($newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY &&
       !$newsletter_contains_posts
    ) {
      // delete notification history record since it will never be sent
      $newsletter->delete();
      return false;
    }
    // extract and save newsletter posts
    PostsTask::extractAndSave($rendered_newsletter, $newsletter);
    // update queue with the rendered and pre-processed newsletter
    $queue->newsletter_rendered_body = $rendered_newsletter;
    $queue->save();
    return $newsletter;
  }

  function prepareNewsletterForSending($newsletter, $subscriber, $queue) {
    // shortcodes and links will be replaced in the subject, html and text body
    // to speed the processing, join content into a continuous string
    $rendered_newsletter = $queue->getNewsletterRenderedBody();
    $prepared_newsletter = Helpers::joinObject(
      array(
        $newsletter->subject,
        $rendered_newsletter['html'],
        $rendered_newsletter['text']
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
    // if it's a standard or notification history newsletter, update its status
    if($newsletter->type === NewsletterModel::TYPE_STANDARD ||
       $newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY
    ) {
      $newsletter->setStatus(NewsletterModel::STATUS_SENT);
    }
  }

  function getNewsletterSegments($newsletter) {
    $segments = NewsletterSegmentModel::where('newsletter_id', $newsletter->id)
      ->select('segment_id')
      ->findArray();
    return Helpers::flattenArray($segments);
  }
}