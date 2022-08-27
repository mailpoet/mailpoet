<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\GATracking;
use MailPoet\Util\Helpers;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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

  /** @var LinksTask */
  private $linksTask;

  /** @var NewsletterLinks */
  private $newsletterLinks;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  public function __construct(
    WPFunctions $wp = null,
    PostsTask $postsTask = null,
    GATracking $gaTracking = null,
    Emoji $emoji = null
  ) {
    $trackingConfig = ContainerWrapper::getInstance()->get(TrackingConfig::class);
    $this->trackingEnabled = $trackingConfig->isEmailTrackingEnabled();
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
    if ($postsTask === null) {
      $postsTask = new PostsTask;
    }
    $this->postsTask = $postsTask;
    if ($gaTracking === null) {
      $gaTracking = ContainerWrapper::getInstance()->get(GATracking::class);
    }
    $this->gaTracking = $gaTracking;
    $this->loggerFactory = LoggerFactory::getInstance();
    if ($emoji === null) {
      $emoji = new Emoji();
    }
    $this->emoji = $emoji;
    $this->renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->newslettersRepository = ContainerWrapper::getInstance()->get(NewslettersRepository::class);
    $this->linksTask = ContainerWrapper::getInstance()->get(LinksTask::class);
    $this->newsletterLinks = ContainerWrapper::getInstance()->get(NewsletterLinks::class);
    $this->sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    $this->newsletterSegmentRepository = ContainerWrapper::getInstance()->get(NewsletterSegmentRepository::class);
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
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'pre-processing newsletter',
      ['newsletter_id' => $newsletter->id, 'task_id' => $sendingTask->taskId]
    );
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
    if (!$newsletterEntity) return false;
    // if tracking is enabled, do additional processing
    if ($this->trackingEnabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->trackingImageInserted = OpenTracking::addTrackingImage();
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletterEntity, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletterEntity);
      // hash and save all links
      $renderedNewsletter = $this->linksTask->process($renderedNewsletter, $newsletter, $sendingTask);
    } else {
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletterEntity, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletterEntity);
    }
    // check if this is a post notification and if it contains at least 1 ALC post
    if (
      $newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY &&
      $this->postsTask->getAlcPostsCount($renderedNewsletter, $newsletter) === 0
    ) {
      // delete notification history record since it will never be sent
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
        'no posts in post notification, deleting it',
        ['newsletter_id' => $newsletter->id, 'task_id' => $sendingTask->taskId]
      );
      $this->newslettersRepository->bulkDelete([(int)$newsletter->id]);
      return false;
    }
    // extract and save newsletter posts
    $this->postsTask->extractAndSave($renderedNewsletter, $newsletterEntity);

    if ($sendingTask->queue() instanceof SendingQueueModel) {
      $sendingQueueEntity = $this->sendingQueuesRepository->findOneById($sendingTask->queue()->id);
    } else {
      $sendingQueueEntity = null;
    }

    // update queue with the rendered and pre-processed newsletter
    $sendingTask->newsletterRenderedSubject = ShortcodesTask::process(
      $newsletter->subject,
      $renderedNewsletter['html'],
      $newsletterEntity,
      null,
      $sendingQueueEntity
    );
    // if the rendered subject is empty, use a default subject,
    // having no subject in a newsletter is considered spammy
    if (empty(trim((string)$sendingTask->newsletterRenderedSubject))) {
      $sendingTask->newsletterRenderedSubject = __('No subject', 'mailpoet');
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

  public function prepareNewsletterForSending($newsletter, SubscriberEntity $subscriber, $queue): array {
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

    if ($newsletter instanceof NewsletterModel) {
      $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
    } else {
      $newsletterEntity = null;
    }

    if ($queue->queue() instanceof SendingQueueModel) {
      $sendingQueueEntity = $this->sendingQueuesRepository->findOneById($queue->queue()->id);
    } else {
      $sendingQueueEntity = null;
    }

    $preparedNewsletter = ShortcodesTask::process(
      $preparedNewsletter,
      null,
      $newsletterEntity,
      $subscriber,
      $sendingQueueEntity
    );
    if ($this->trackingEnabled) {
      $preparedNewsletter = $this->newsletterLinks->replaceSubscriberData(
        $subscriber->getId(),
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

  public function markNewsletterAsSent(NewsletterEntity $newsletter, $queue) {
    // if it's a standard or notification history newsletter, update its status
    if (
      $newsletter->getType() === NewsletterModel::TYPE_STANDARD ||
       $newsletter->getType() === NewsletterModel::TYPE_NOTIFICATION_HISTORY
    ) {
      $newsletter->setStatus(NewsletterModel::STATUS_SENT);
      $newsletter->setSentAt(new Carbon($queue->processedAt));
      $this->newslettersRepository->persist($newsletter);
      $this->newslettersRepository->flush();
    }
  }

  public function getNewsletterSegments(NewsletterEntity $newsletter) {
    $newsletterSegments = $this->newsletterSegmentRepository->findBy(['newsletter' => $newsletter]);
    $segmentIds = array_map(
      function(NewsletterSegmentEntity $newsletterSegment) {
        $segment = $newsletterSegment->getSegment();

        if ($segment instanceof SegmentEntity) {
          return $segment->getId();
        }
      },
      $newsletterSegments
    );

    return $segmentIds;
  }

  public function stopNewsletterPreProcessing($errorCode = null) {
    MailerLog::processError(
      'queue_save',
      __('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.', 'mailpoet'),
      $errorCode
    );
  }
}
