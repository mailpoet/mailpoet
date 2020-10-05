<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterSegment as NewsletterSegmentModel;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\GATracking;
use MailPoet\Util\Helpers;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

class Newsletter {
  public $trackingEnabled;
  public $trackingImageInserted;

  /** @var WPFunctions */
  private $wp;

  /** @var PostsTask */
  private $postsTask;

  /** @var GATracking */
  private $gaTracking;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var Renderer */
  private $renderer;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var Emoji */
  private $emoji;

  public function __construct(WPFunctions $wp = null, PostsTask $postsTask = null, GATracking $gaTracking = null, Emoji $emoji = null) {
    $settings = SettingsController::getInstance();
    $this->trackingEnabled = (boolean)$settings->get('tracking.enabled');
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
    if ($postsTask === null) {
      $postsTask = new PostsTask;
    }
    $this->postsTask = $postsTask;
    if ($gaTracking === null) {
      $gaTracking = new GATracking;
    }
    $this->gaTracking = $gaTracking;
    $this->loggerFactory = LoggerFactory::getInstance();
    if ($emoji === null) {
      $emoji = new Emoji();
    }
    $this->emoji = $emoji;
    $this->renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->newslettersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
  }

  public function getNewsletterFromQueue($queue) {
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
      $parentNewsletter = $newsletter->parent()
        ->whereNull('deleted_at')
        ->whereAnyIs(
          [
            ['status' => NewsletterModel::STATUS_ACTIVE],
            ['status' => NewsletterModel::STATUS_SENDING],
          ]
        )
        ->findOne();
      if (!$parentNewsletter) return false;
    }
    return $newsletter;
  }

  public function preProcessNewsletter(\MailPoet\Models\Newsletter $newsletter, $sendingTask) {
    // return the newsletter if it was previously rendered
    if (!is_null($sendingTask->getNewsletterRenderedBody())) {
      return (!$sendingTask->validate()) ?
        $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-RENDER', $sendingTask->id)) :
        $newsletter;
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->addInfo(
      'pre-processing newsletter',
      ['newsletter_id' => $newsletter->id, 'task_id' => $sendingTask->taskId]
    );
    // if tracking is enabled, do additional processing
    if ($this->trackingEnabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->trackingImageInserted = OpenTracking::addTrackingImage();
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletter, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
      // hash and save all links
      $renderedNewsletter = LinksTask::process($renderedNewsletter, $newsletter, $sendingTask);
    } else {
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletter, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
    }
    // check if this is a post notification and if it contains at least 1 ALC post
    if ($newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY &&
      $this->postsTask->getAlcPostsCount($renderedNewsletter, $newsletter) === 0
    ) {
      // delete notification history record since it will never be sent
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
        'no posts in post notification, deleting it',
        ['newsletter_id' => $newsletter->id, 'task_id' => $sendingTask->taskId]
      );
      $this->newslettersRepository->bulkDelete([(int)$newsletter->id]);
      return false;
    }
    // extract and save newsletter posts
    $this->postsTask->extractAndSave($renderedNewsletter, $newsletter);
    // update queue with the rendered and pre-processed newsletter
    $sendingTask->newsletterRenderedSubject = ShortcodesTask::process(
      $newsletter->subject,
      $renderedNewsletter['html'],
      $newsletter,
      null,
      $sendingTask
    );
    // if the rendered subject is empty, use a default subject,
    // having no subject in a newsletter is considered spammy
    if (empty(trim($sendingTask->newsletterRenderedSubject))) {
      $sendingTask->newsletterRenderedSubject = WPFunctions::get()->__('No subject', 'mailpoet');
    }
    $renderedNewsletter = $this->emoji->encodeEmojisInBody($renderedNewsletter);
    $sendingTask->newsletterRenderedBody = $renderedNewsletter;
    $sendingTask->save();
    // catch DB errors
    $queueErrors = $sendingTask->getErrors();
    if (!$queueErrors) {
      // verify that the rendered body was successfully saved
      $sendingQueue = SendingQueueModel::findOne($sendingTask->id);
      if ($sendingQueue instanceof SendingQueueModel) {
        $queueErrors = ($sendingQueue->validate() !== true);
      }
    }
    if ($queueErrors) {
      $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-SAVE', $sendingTask->id));
    }
    return $newsletter;
  }

  public function prepareNewsletterForSending($newsletter, $subscriber, $queue) {
    // shortcodes and links will be replaced in the subject, html and text body
    // to speed the processing, join content into a continuous string
    $renderedNewsletter = $queue->getNewsletterRenderedBody();
    $renderedNewsletter = $this->emoji->decodeEmojisInBody($renderedNewsletter);
    $preparedNewsletter = Helpers::joinObject(
      [
        $queue->newsletterRenderedSubject,
        $renderedNewsletter['html'],
        $renderedNewsletter['text'],
      ]
    );
    $preparedNewsletter = ShortcodesTask::process(
      $preparedNewsletter,
      null,
      $newsletter,
      $subscriber,
      $queue
    );
    if ($this->trackingEnabled) {
      $preparedNewsletter = NewsletterLinks::replaceSubscriberData(
        $subscriber->id,
        $queue->id,
        $preparedNewsletter
      );
    }
    $preparedNewsletter = Helpers::splitObject($preparedNewsletter);
    return [
      'id' => $newsletter->id,
      'subject' => $preparedNewsletter[0],
      'body' => [
        'html' => $preparedNewsletter[1],
        'text' => $preparedNewsletter[2],
      ],
    ];
  }

  public function markNewsletterAsSent($newsletter, $queue) {
    // if it's a standard or notification history newsletter, update its status
    if ($newsletter->type === NewsletterModel::TYPE_STANDARD ||
       $newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY
    ) {
      $newsletter->status = NewsletterModel::STATUS_SENT;
      $newsletter->sentAt = $queue->processedAt;
      $newsletter->save();
    }
  }

  public function getNewsletterSegments($newsletter) {
    $segments = NewsletterSegmentModel::where('newsletter_id', $newsletter->id)
      ->select('segment_id')
      ->findArray();
    return Helpers::flattenArray($segments);
  }

  public function stopNewsletterPreProcessing($errorCode = null) {
    MailerLog::processError(
      'queue_save',
      WPFunctions::get()->__('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.', 'mailpoet'),
      $errorCode
    );
  }
}
