<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\GATracking;
use MailPoet\Tasks\Sending;
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
  }

  public function getNewsletterFromQueue(Sending $sendingTask): ?NewsletterEntity {
    // get existing active or sending newsletter
    $sendingQueue = $sendingTask->getSendingQueueEntity();
    $newsletter = $sendingQueue->getNewsletter();

    if (
      is_null($newsletter)
      || $newsletter->getDeletedAt() !== null
      || !in_array($newsletter->getStatus(), [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_SENDING])
      || $newsletter->getStatus() === NewsletterEntity::STATUS_CORRUPT
    ) {
      return null;
    }

    // if this is a notification history, get existing active or sending parent newsletter
    if ($newsletter->getType() == NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $parentNewsletter = $newsletter->getParent();

      if (
        is_null($parentNewsletter)
        || $parentNewsletter->getDeletedAt() !== null
        || !in_array($parentNewsletter->getStatus(), [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_SENDING])
      ) {
        return null;
      }
    }

    return $newsletter;
  }

  public function preProcessNewsletter(NewsletterEntity $newsletter, Sending $sendingTask) {
    // return the newsletter if it was previously rendered
    /** @phpstan-ignore-next-line - SendingQueue::getNewsletterRenderedBody() is called inside Sending using __call(). Sending will be refactored soon to stop using Paris models. */
    if (!is_null($sendingTask->getNewsletterRenderedBody())) {
      return (!$sendingTask->validate()) ?
        $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-RENDER', $sendingTask->id)) :
        $newsletter;
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'pre-processing newsletter',
      ['newsletter_id' => $newsletter->getId(), 'task_id' => $sendingTask->taskId]
    );

    // if tracking is enabled, do additional processing
    if ($this->trackingEnabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->trackingImageInserted = OpenTracking::addTrackingImage();
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletter, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after_pre_process',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
      // hash and save all links
      $renderedNewsletter = $this->linksTask->process($renderedNewsletter, $newsletter, $sendingTask);
    } else {
      // render newsletter
      $renderedNewsletter = $this->renderer->render($newsletter, $sendingTask);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after_pre_process',
        $renderedNewsletter,
        $newsletter
      );
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
    }

    // This deprecated notice can be removed after 2023-03-23
    if ($this->wp->hasFilter('mailpoet_sending_newsletter_render_after')) {
      $this->wp->deprecatedHook(
        'mailpoet_sending_newsletter_render_after',
        '3.98.1',
        'mailpoet_sending_newsletter_render_after_pre_process',
        __('Please note that mailpoet_sending_newsletter_render_after no longer runs and that the list of parameters of the new filter is different.', 'mailpoet')
      );
    }

    // check if this is a post notification and if it contains at least 1 ALC post
    if (
      $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY &&
      $this->postsTask->getAlcPostsCount($renderedNewsletter, $newsletter) === 0
    ) {
      // delete notification history record since it will never be sent
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
        'no posts in post notification, deleting it',
        ['newsletter_id' => $newsletter->getId(), 'task_id' => $sendingTask->taskId]
      );
      $this->newslettersRepository->bulkDelete([(int)$newsletter->getId()]);
      return false;
    }
    // extract and save newsletter posts
    $this->postsTask->extractAndSave($renderedNewsletter, $newsletter);

    $sendingQueueEntity = $sendingTask->getSendingQueueEntity();

    // update queue with the rendered and pre-processed newsletter
    $sendingTask->newsletterRenderedSubject = ShortcodesTask::process(
      $newsletter->getSubject(),
      $renderedNewsletter['html'],
      $newsletter,
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

  /**
   * Shortcodes and links will be replaced in the subject, html and text body
   * to speed the processing, join content into a continuous string.
   */
  public function prepareNewsletterForSending(NewsletterEntity $newsletter, SubscriberEntity $subscriber, Sending $sendingTask): array {
    $sendingQueue = $sendingTask->queue();
    $renderedNewsletter = $sendingQueue->getNewsletterRenderedBody();
    $renderedNewsletter = $this->emoji->decodeEmojisInBody($renderedNewsletter);
    $preparedNewsletter = Helpers::joinObject(
      [
        $sendingTask->newsletterRenderedSubject,
        $renderedNewsletter['html'],
        $renderedNewsletter['text'],
      ]
    );

    $sendingQueueEntity = $sendingTask->getSendingQueueEntity();

    $preparedNewsletter = ShortcodesTask::process(
      $preparedNewsletter,
      null,
      $newsletter,
      $subscriber,
      $sendingQueueEntity
    );
    if ($this->trackingEnabled) {
      $preparedNewsletter = $this->newsletterLinks->replaceSubscriberData(
        $subscriber->getId(),
        $sendingTask->id,
        $preparedNewsletter
      );
    }
    $preparedNewsletter = Helpers::splitObject($preparedNewsletter);
    return [
      'id' => $newsletter->getId(),
      'subject' => $preparedNewsletter[0],
      'body' => [
        'html' => $preparedNewsletter[1],
        'text' => $preparedNewsletter[2],
      ],
    ];
  }

  public function markNewsletterAsSent(NewsletterEntity $newsletter, Sending $sendingTask) {
    // if it's a standard or notification history newsletter, update its status
    if (
      $newsletter->getType() === NewsletterEntity::TYPE_STANDARD ||
       $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY
    ) {
      $scheduledTask = $sendingTask->task();
      $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
      $newsletter->setSentAt(new Carbon($scheduledTask->processedAt));
      $this->newslettersRepository->persist($newsletter);
      $this->newslettersRepository->flush();
    }
  }

  public function stopNewsletterPreProcessing($errorCode = null) {
    MailerLog::processError(
      'queue_save',
      __('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.', 'mailpoet'),
      $errorCode
    );
  }
}
