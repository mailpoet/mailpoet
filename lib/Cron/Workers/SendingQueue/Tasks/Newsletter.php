<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\Logging\Logger;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterSegment as NewsletterSegmentModel;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Newsletter {
  public $tracking_enabled;
  public $tracking_image_inserted;

  /** @var WPFunctions */
  private $wp;

  /** @var PostsTask */
  private $posts_task;

  function __construct(WPFunctions $wp = null, PostsTask $posts_task = null) {
    $settings = new SettingsController();
    $this->tracking_enabled = (boolean)$settings->get('tracking.enabled');
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
    if ($posts_task === null) {
      $posts_task = new PostsTask;
    }
    $this->posts_task = $posts_task;
  }

  function getNewsletterFromQueue($queue) {
    // get existing active or sending newsletter
    $newsletter = $queue->newsletter()
      ->whereNull('deleted_at')
      ->whereAnyIs(
        [
          ['status' => NewsletterModel::STATUS_ACTIVE],
          ['status' => NewsletterModel::STATUS_SENDING],
        ]
      )
      ->findOne();
    if (!$newsletter) return false;
    // if this is a notification history, get existing active or sending parent newsletter
    if ($newsletter->type == NewsletterModel::TYPE_NOTIFICATION_HISTORY) {
      $parent_newsletter = $newsletter->parent()
        ->whereNull('deleted_at')
        ->whereAnyIs(
          [
            ['status' => NewsletterModel::STATUS_ACTIVE],
            ['status' => NewsletterModel::STATUS_SENDING],
          ]
        )
        ->findOne();
      if (!$parent_newsletter) return false;
    }
    return $newsletter;
  }

  function preProcessNewsletter(\MailPoet\Models\Newsletter $newsletter, $queue) {
    // return the newsletter if it was previously rendered
    if (!is_null($queue->getNewsletterRenderedBody())) {
      return (!$queue->validate()) ?
        $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-RENDER', $queue->id)) :
        $newsletter;
    }
    Logger::getLogger('newsletters')->addInfo(
      'pre-processing newsletter',
      ['newsletter_id' => $newsletter->id, 'task_id' => $queue->task_id]
    );
    // if tracking is enabled, do additional processing
    if ($this->tracking_enabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->tracking_image_inserted = OpenTracking::addTrackingImage();
      // render newsletter
      $rendered_newsletter = $newsletter->render();
      $rendered_newsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $rendered_newsletter,
        $newsletter
      );
      // hash and save all links
      $rendered_newsletter = LinksTask::process($rendered_newsletter, $newsletter, $queue);
    } else {
      // render newsletter
      $rendered_newsletter = $newsletter->render();
      $rendered_newsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $rendered_newsletter,
        $newsletter
      );
    }
    // check if this is a post notification and if it contains at least 1 ALC post
    if ($newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY &&
      $this->posts_task->getAlcPostsCount($rendered_newsletter, $newsletter) === 0
    ) {
      // delete notification history record since it will never be sent
      Logger::getLogger('post-notifications')->addInfo(
        'no posts in post notification, deleting it',
        ['newsletter_id' => $newsletter->id, 'task_id' => $queue->task_id]
      );
      $newsletter->delete();
      return false;
    }
    // extract and save newsletter posts
    $this->posts_task->extractAndSave($rendered_newsletter, $newsletter);
    // update queue with the rendered and pre-processed newsletter
    $queue->newsletter_rendered_subject = ShortcodesTask::process(
      $newsletter->subject,
      $rendered_newsletter['html'],
      $newsletter,
      null,
      $queue
    );
    // if the rendered subject is empty, use a default subject,
    // having no subject in a newsletter is considered spammy
    if (empty(trim($queue->newsletter_rendered_subject))) {
      $queue->newsletter_rendered_subject = WPFunctions::get()->__('No subject', 'mailpoet');
    }
    $queue->newsletter_rendered_body = $rendered_newsletter;
    $queue->save();
    // catch DB errors
    $queue_errors = $queue->getErrors();
    if (!$queue_errors) {
      // verify that the rendered body was successfully saved
      $queue = SendingQueueModel::findOne($queue->id);
      if ($queue instanceof SendingQueueModel) {
        $queue_errors = ($queue->validate() !== true);
      }
    }
    if ($queue_errors) {
      $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-SAVE', $queue->id));
    }
    return $newsletter;
  }

  function prepareNewsletterForSending($newsletter, $subscriber, $queue) {
    // shortcodes and links will be replaced in the subject, html and text body
    // to speed the processing, join content into a continuous string
    $rendered_newsletter = $queue->getNewsletterRenderedBody();
    $prepared_newsletter = Helpers::joinObject(
      [
        $queue->newsletter_rendered_subject,
        $rendered_newsletter['html'],
        $rendered_newsletter['text'],
      ]
    );
    $prepared_newsletter = ShortcodesTask::process(
      $prepared_newsletter,
      null,
      $newsletter,
      $subscriber,
      $queue
    );
    if ($this->tracking_enabled) {
      $prepared_newsletter = NewsletterLinks::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $prepared_newsletter
      );
    }
    $prepared_newsletter = Helpers::splitObject($prepared_newsletter);
    return [
      'id' => $newsletter->id,
      'subject' => $prepared_newsletter[0],
      'body' => [
        'html' => $prepared_newsletter[1],
        'text' => $prepared_newsletter[2],
      ],
    ];
  }

  function markNewsletterAsSent($newsletter, $queue) {
    // if it's a standard or notification history newsletter, update its status
    if ($newsletter->type === NewsletterModel::TYPE_STANDARD ||
       $newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY
    ) {
      $newsletter->status = NewsletterModel::STATUS_SENT;
      $newsletter->sent_at = $queue->processed_at;
      $newsletter->save();
    }
  }

  function getNewsletterSegments($newsletter) {
    $segments = NewsletterSegmentModel::where('newsletter_id', $newsletter->id)
      ->select('segment_id')
      ->findArray();
    return Helpers::flattenArray($segments);
  }

  function stopNewsletterPreProcessing($error_code = null) {
    MailerLog::processError(
      'queue_save',
      WPFunctions::get()->__('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.', 'mailpoet'),
      $error_code
    );
  }
}
