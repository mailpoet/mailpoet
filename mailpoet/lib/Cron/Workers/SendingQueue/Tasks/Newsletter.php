<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links as LinksTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes as ShortcodesTask;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockDetector;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationContextBuilder;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkResolver;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Links\Links as NewsletterLinks;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Shortcodes\Categories\Link as LinkShortcodeCategory;
use MailPoet\NewsletterProcessingException;
use MailPoet\RuntimeException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\GATracking;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Util\Helpers;
use MailPoet\Util\pQuery\pQuery;
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

  /** @var NewsletterDeleteController  */
  private $newsletterDeleteController;

  /** @var Emoji */
  private $emoji;

  /** @var LinksTask */
  private $linksTask;

  /** @var NewsletterLinks */
  private $newsletterLinks;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var Personalizer */
  private $personalizer;

  private CouponBlockDetector $couponBlockDetector;
  private OrderReviewUrl $orderReviewUrl;
  private PersonalizationContextBuilder $personalizationContextBuilder;
  private PersonalizationTagLinkResolver $personalizationTagLinkResolver;
  private NewsletterLinkRepository $newsletterLinkRepository;

  /** @var array<int, bool> queue id => whether the queue tracks an order review URL link */
  private array $orderReviewUrlLinkByQueue = [];

  private TrackingConsentController $trackingConsentController;

  private LinkShortcodeCategory $linkShortcodeCategory;

  public function __construct(
    ?WPFunctions $wp = null,
    ?PostsTask $postsTask = null,
    ?GATracking $gaTracking = null,
    ?Emoji $emoji = null
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
    $this->newsletterDeleteController = ContainerWrapper::getInstance()->get(NewsletterDeleteController::class);
    $this->linksTask = ContainerWrapper::getInstance()->get(LinksTask::class);
    $this->newsletterLinks = ContainerWrapper::getInstance()->get(NewsletterLinks::class);
    $this->sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    $this->segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
    $this->personalizationContextBuilder = ContainerWrapper::getInstance()->get(PersonalizationContextBuilder::class);
    $this->personalizationTagLinkResolver = ContainerWrapper::getInstance()->get(PersonalizationTagLinkResolver::class);
    $this->newsletterLinkRepository = ContainerWrapper::getInstance()->get(NewsletterLinkRepository::class);
    $this->couponBlockDetector = ContainerWrapper::getInstance()->get(CouponBlockDetector::class);
    $this->orderReviewUrl = ContainerWrapper::getInstance()->get(OrderReviewUrl::class);
    $this->trackingConsentController = ContainerWrapper::getInstance()->get(TrackingConsentController::class);
    $this->linkShortcodeCategory = ContainerWrapper::getInstance()->get(LinkShortcodeCategory::class);
  }

  /**
   * Put real destinations back for a recipient we may not track.
   *
   * Restoring the saved link turns a plain URL back into itself, but a link
   * shortcode back into raw `[link:...]` text: the shortcode pass deliberately
   * leaves those alone while site tracking is on, because the click redirect is
   * normally what resolves them. Since these recipients get no redirect, we
   * resolve the shortcodes here with the same call the redirect would have
   * made, so they land on exactly the same page.
   *
   * A restored URL may also carry a non-link shortcode of its own, such as
   * `http://example.com/?email=[subscriber:email]`. Clicks::processUrl() runs a
   * full shortcode pass over those at click time, so this does the same at send
   * time (STOMAIL-8340).
   *
   * Links of block emails whose URL is a personalization tag token are stored
   * symbolically too and resolved here with the same resolver the redirect uses.
   *
   * @param array<string, mixed>|null $personalizationContext
   */
  private function untrackLinks(
    string $content,
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    ?array $personalizationContext
  ): string {
    $resolveTokenUrl = function (string $url) use ($personalizationContext): string {
      if ($personalizationContext === null || !$this->personalizationTagLinkResolver->isTokenUrl($url)) {
        return $url;
      }
      // An unresolvable token would otherwise ship as literal text.
      return $this->personalizationTagLinkResolver->resolveWithContext($url, $personalizationContext) ?? '';
    };
    // true = convert every hashed link, not only the shortcode ones.
    $content = $this->newsletterLinks->convertHashedLinksToShortcodesAndUrls(
      $content,
      $queue->getId(),
      true,
      $resolveTokenUrl
    );

    // Matches the whole shortcode, arguments included, because a link shortcode
    // may carry one: [link:action | name:value]. The (?!\/\/) guard mirrors the
    // extractor in Shortcodes::extract() so text like [link://example.com] is
    // left alone rather than resolved to nothing and dropped. Case-insensitive
    // for the same reason: that extractor is too, so [LINK:...] gets stored.
    $content = (string)preg_replace_callback(
      '/\[link:(?!\/\/)(?<action>[^\]]+)\]/i',
      function (array $matches) use ($newsletter, $subscriber, $queue): string {
        // Pass the full shortcode, as Statistics\Track\Clicks::processUrl() does:
        // processShortcodeAction() parses the brackets itself, and only sees the
        // arguments when they are still attached.
        $url = $this->linkShortcodeCategory->processShortcodeAction(
          $matches[0],
          $newsletter,
          $subscriber,
          $queue
        );
        // An unresolvable shortcode would otherwise ship as literal text.
        return $url ?? '';
      },
      $content
    );

    // Anything still unresolved was reintroduced by the restore above: the pass
    // in prepareNewsletterForSending() already ran, and at that point every link
    // was a hashed tag, so URL-embedded shortcodes were not in the content to be
    // seen. Running it again therefore only touches the restored URLs.
    return ShortcodesTask::process(
      $content,
      null,
      $newsletter,
      $subscriber,
      $queue
    );
  }

  public function getNewsletterFromQueue(ScheduledTaskEntity $task): ?NewsletterEntity {
    // get existing active or sending newsletter
    $queue = $task->getSendingQueue();
    $newsletter = $queue ? $queue->getNewsletter() : null;

    $allowedStatuses = [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_SENDING];
    if (
      $queue
      && NewsletterReplayMetadata::isLatestNewsletterReplayMeta($queue->getMeta())
      && $newsletter
      && $newsletter->getType() === NewsletterEntity::TYPE_STANDARD
    ) {
      $allowedStatuses[] = NewsletterEntity::STATUS_SENT;
    }

    if (
      is_null($newsletter)
      || $newsletter->getDeletedAt() !== null
      || !in_array($newsletter->getStatus(), $allowedStatuses, true)
    ) {
      $this->recoverFromInvalidState($task);
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

  /**
   * Pre-processes the newsletter before sending.
   * - Renders the newsletter
   * - Adds tracking
   * - Extracts links
   * - Checks if the newsletter is a post notification and if it contains at least 1 ALC post.
   *   If not it deletes the notification history record and all associate entities.
   *
   * @return NewsletterEntity|false - Returns false only if the newsletter is a post notification history and was deleted.
   *
   */
  public function preProcessNewsletter(NewsletterEntity $newsletter, ScheduledTaskEntity $task) {
    // return the newsletter if it was previously rendered
    $queue = $task->getSendingQueue();
    if (!$queue) {
      throw new RuntimeException('Can‘t pre-process newsletter without queue.');
    }
    if ($queue->getNewsletterRenderedBody() !== null) {
      return $newsletter;
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->info(
      'pre-processing newsletter',
      ['newsletter_id' => $newsletter->getId(), 'task_id' => $task->getId()]
    );

    $campaignId = null;
    $this->preflightCouponBlockGeneration($newsletter, $queue);

    // if tracking is enabled, do additional processing
    if ($this->trackingEnabled) {
      // hook to the newsletter post-processing filter and add tracking image
      $this->trackingImageInserted = OpenTracking::addTrackingImage();
      // render newsletter
      $renderedNewsletter = $this->renderNewsletterOrStop($newsletter, $queue);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after_pre_process',
        $renderedNewsletter,
        $newsletter
      );
      if (is_array($renderedNewsletter)) {
        $campaignId = $this->calculateCampaignId($newsletter, $renderedNewsletter);
      }
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
      // hash and save all links
      $renderedNewsletter = $this->linksTask->process($renderedNewsletter, $newsletter, $queue);
    } else {
      // render newsletter
      $renderedNewsletter = $this->renderNewsletterOrStop($newsletter, $queue);
      $renderedNewsletter = $this->wp->applyFilters(
        'mailpoet_sending_newsletter_render_after_pre_process',
        $renderedNewsletter,
        $newsletter
      );
      if (is_array($renderedNewsletter)) {
        $campaignId = $this->calculateCampaignId($newsletter, $renderedNewsletter);
      }
      $renderedNewsletter = $this->gaTracking->applyGATracking($renderedNewsletter, $newsletter);
    }

    // check if this is a post notification and if it contains at least 1 ALC post
    if (
      $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY &&
      $this->postsTask->getAlcPostsCount($renderedNewsletter, $newsletter) === 0
    ) {
      // delete notification history record since it will never be sent
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
        'no posts in post notification, deleting it',
        ['newsletter_id' => $newsletter->getId(), 'task_id' => $task->getId()]
      );
      $this->newsletterDeleteController->bulkDelete([(int)$newsletter->getId()]);
      return false;
    }
    // extract and save newsletter posts
    $this->postsTask->extractAndSave($renderedNewsletter, $newsletter);

    if ($campaignId !== null) {
      $this->sendingQueuesRepository->saveCampaignId($queue, $campaignId);
    }

    $filterSegmentId = $newsletter->getFilterSegmentId();
    if ($filterSegmentId) {
      $filterSegment = $this->segmentsRepository->findOneById($filterSegmentId);
      if ($filterSegment instanceof SegmentEntity && $filterSegment->getType() === SegmentEntity::TYPE_DYNAMIC) {
        $this->sendingQueuesRepository->saveFilterSegmentMeta($queue, $filterSegment);
      }
    }

    // update queue with the rendered and pre-processed newsletter
    $queue->setNewsletterRenderedSubject(
      ShortcodesTask::process(
        $newsletter->getSubject(),
        $renderedNewsletter['html'],
        $newsletter,
        null,
        $queue
      )
    );

    // if the rendered subject is empty, use a default subject,
    // having no subject in a newsletter is considered spammy
    if (empty(trim((string)$queue->getNewsletterRenderedSubject()))) {
      $queue->setNewsletterRenderedSubject(__('No subject', 'mailpoet'));
    }
    $renderedNewsletter = $this->emoji->encodeEmojisInBody($renderedNewsletter);
    $queue->setNewsletterRenderedBody($renderedNewsletter);

    try {
      $this->sendingQueuesRepository->flush();
    } catch (\Throwable $e) {
      $this->stopNewsletterPreProcessing(sprintf('QUEUE-%d-SAVE', $queue->getId()));
    }
    return $newsletter;
  }

  private function preflightCouponBlockGeneration(NewsletterEntity $newsletter, SendingQueueEntity $queue): void {
    $wpPostEntity = $newsletter->getWpPost();
    $wpPost = $wpPostEntity ? $wpPostEntity->getWpPostInstance() : null;
    if (!$wpPost instanceof \WP_Post) {
      return;
    }

    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if (!$this->couponBlockDetector->hasCreateNewCouponBlock($wpPost->post_content)) {
      return;
    }

    $isAutomationSingleRecipient = $this->isAutomationType($newsletter) && $this->getTaskSubscriberCount($queue) === 1;
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $hasRecipientRestriction = $this->couponBlockDetector->hasRecipientRestrictedCreateNewCouponBlock($wpPost->post_content);
    if ($isAutomationSingleRecipient || ($newsletter->getType() === NewsletterEntity::TYPE_STANDARD && !$hasRecipientRestriction)) {
      return;
    }

    $message = $hasRecipientRestriction
      ? __('Recipient-restricted generated coupons are only supported in automation emails sent to one subscriber at a time. Disable recipient restriction, remove the generated coupon block, or use an existing coupon before sending this email.', 'mailpoet')
      : __('Auto-generated coupon codes are only supported in regular newsletters and automation emails sent to one subscriber at a time. Remove the generated coupon block or use an existing coupon before sending this email.', 'mailpoet');
    $this->failCouponBlockSend($newsletter, $queue, $message);
    throw NewsletterProcessingException::create()->withMessage($message);
  }

  private function renderNewsletterOrStop(NewsletterEntity $newsletter, SendingQueueEntity $queue): array {
    try {
      return $this->renderer->render($newsletter, $queue);
    } catch (NewsletterProcessingException $e) {
      $this->failCouponBlockSend($newsletter, $queue, $e->getMessage());
      throw NewsletterProcessingException::create($e)->withMessage($e->getMessage());
    }
  }

  private function failCouponBlockSend(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    string $message
  ): void {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_COUPONS)->error(
      $message,
      [
        'newsletter_id' => $newsletter->getId(),
        'queue_id' => $queue->getId(),
      ]
    );
    if (!NewsletterReplayMetadata::isLatestNewsletterReplayMeta($queue->getMeta())) {
      $this->newslettersRepository->setAsCorrupt($newsletter);
    }
    $this->sendingQueuesRepository->pause($queue);
  }

  private function isAutomationType(NewsletterEntity $newsletter): bool {
    return in_array($newsletter->getType(), [
      NewsletterEntity::TYPE_AUTOMATION,
      NewsletterEntity::TYPE_AUTOMATION_NOTIFICATION,
      NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
    ], true);
  }

  private function getTaskSubscriberCount(SendingQueueEntity $queue): int {
    $task = $queue->getTask();
    $subscribers = $task ? $task->getSubscribers() : null;
    return $subscribers ? count($subscribers) : 0;
  }

  /**
   * Shortcodes and links will be replaced in the subject, html and text body
   * to speed the processing, join content into a continuous string.
   */
  public function prepareNewsletterForSending(NewsletterEntity $newsletter, SubscriberEntity $subscriber, SendingQueueEntity $queue): array {
    $renderedNewsletter = $queue->getNewsletterRenderedBody();
    $renderedNewsletter = $this->emoji->decodeEmojisInBody($renderedNewsletter);
    $preparedNewsletter = Helpers::joinObject(
      [
        $queue->getNewsletterRenderedSubject(),
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
    $context = $newsletter->getWpPostId() !== null
      ? $this->personalizationContextBuilder->build($newsletter, $subscriber, $queue)
      : null;
    if ($this->trackingEnabled) {
      if ($this->trackingConsentController->isTrackingAllowed($subscriber)) {
        $preparedNewsletter = $this->newsletterLinks->replaceSubscriberData(
          $subscriber->getId(),
          $queue->getId(),
          $preparedNewsletter
        );
      } else {
        // CNIL/Garante: withdrawal must stop the reading operation itself, not
        // merely the recording of it. A tracked link still tells our server the
        // recipient clicked, so these recipients get the plain destination
        // instead of a redirect through us.
        $preparedNewsletter = $this->untrackLinks($preparedNewsletter, $newsletter, $subscriber, $queue, $context);
        $preparedNewsletter = OpenTracking::removeTrackingImage($preparedNewsletter);
      }
    }
    [$subject, $html, $text] = Helpers::splitObject($preparedNewsletter);
    if ($context !== null) {
      $this->guardOrderReviewUrlPersonalization($newsletter, $queue, [$subject, $html, $text], $context);

      $this->personalizer->set_context($context);
      $subject = $this->personalizer->personalize_content($subject, Personalizer::RENDERING_CONTEXT_TEXT);
      $html = $this->personalizer->personalize_content($html, Personalizer::RENDERING_CONTEXT_HTML);
      $text = $this->personalizer->personalize_content($text, Personalizer::RENDERING_CONTEXT_TEXT);
      // Token links that were not hashed (tracking disabled) are still literal in the text body.
      $text = $this->personalizationTagLinkResolver->resolveMarkdownLinks($text, $context);
      $personalizedHtml = $this->wp->applyFilters('mailpoet_automation_email_personalize_html_after', $html, $context);
      if (is_string($personalizedHtml)) {
        $html = $personalizedHtml;
      }
      $personalizedText = $this->wp->applyFilters('mailpoet_automation_email_personalize_text_after', $text, $context);
      if (is_string($personalizedText)) {
        $text = $personalizedText;
      }
    }
    return [
      'id' => $newsletter->getId(),
      'subject' => $subject,
      'body' => [
        'html' => $html,
        'text' => $text,
      ],
    ];
  }

  /**
   * @param array<int|string, mixed> $contentParts
   * @param array<string, mixed> $context
   */
  private function guardOrderReviewUrlPersonalization(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    array $contentParts,
    array $context
  ): void {
    // The email uses no order review link when neither the content carries the token
    // nor the queue tracks it as a stored link (for tracked recipients the content
    // holds only a hash and the token lives in newsletter_links).
    if (!$this->contentContainsOrderReviewUrlToken($contentParts) && !$this->queueTracksOrderReviewUrlLink($queue)) {
      return;
    }

    if ($this->orderReviewUrl->getUrl($context) !== '') {
      return;
    }

    $message = __('Cannot send the email because WooCommerce cannot generate an order review link for this order.', 'mailpoet');
    $this->failOrderReviewUrlSend($newsletter, $queue, $message);
    throw NewsletterProcessingException::create()->withMessage($message);
  }

  /** @param mixed $content */
  private function contentContainsOrderReviewUrlToken($content): bool {
    if (is_array($content)) {
      foreach ($content as $contentPart) {
        if ($this->contentContainsOrderReviewUrlToken($contentPart)) {
          return true;
        }
      }
      return false;
    }

    if (!is_string($content)) {
      return false;
    }

    $normalizedContent = rawurldecode(str_replace('\\/', '/', $content));
    return strpos($normalizedContent, '[woocommerce/order-review-url]') !== false;
  }

  private function queueTracksOrderReviewUrlLink(SendingQueueEntity $queue): bool {
    $queueId = (int)$queue->getId();
    if (!isset($this->orderReviewUrlLinkByQueue[$queueId])) {
      $this->orderReviewUrlLinkByQueue[$queueId] = $this->newsletterLinkRepository->findOneBy([
        'queue' => $queueId,
        'url' => '[woocommerce/order-review-url]',
      ]) !== null;
    }
    return $this->orderReviewUrlLinkByQueue[$queueId];
  }

  private function failOrderReviewUrlSend(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    string $message
  ): void {
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
      $message,
      [
        'newsletter_id' => $newsletter->getId(),
        'queue_id' => $queue->getId(),
      ]
    );
    $this->sendingQueuesRepository->pause($queue);
  }

  public function markNewsletterAsSent(NewsletterEntity $newsletter) {
    // if it's a standard or notification history newsletter, update its status
    if (
      $newsletter->getType() === NewsletterEntity::TYPE_STANDARD ||
       $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY
    ) {
      $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
      $newsletter->setSentAt(Carbon::now()->millisecond(0));
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

  /**
   * @param NewsletterEntity $newsletter
   * @param array $renderedNewsletters - The pre-processed renderered newsletters, before link tracking has been added or shortcodes have been processed.
   *
   * @return string
   */
  public function calculateCampaignId(NewsletterEntity $newsletter, array $renderedNewsletters): string {
    $relevantContent = [
      $newsletter->getId(),
      $newsletter->getSubject(),
    ];

    if (isset($renderedNewsletters['text'])) {
      $relevantContent[] = $renderedNewsletters['text'];
    }

    // The text version of emails contains just the alt text of images, which could be the same for multiple images. In order to ensure
    // campaign IDs change when images change, we should consider all image URLs.
    if (isset($renderedNewsletters['html'])) {
      $html = pQuery::parseStr($renderedNewsletters['html']);
      foreach ($html->query('img') as $imageNode) {
        $src = $imageNode->getAttribute('src');
        if (is_string($src)) {
          $relevantContent[] = $src;
        }
      }
    }
    return substr(md5(implode('|', $relevantContent)), 0, 16);
  }

  /**
   * This method recovers the scheduled task and newsletter from a state when sending cannot proceed.
   */
  private function recoverFromInvalidState(ScheduledTaskEntity $task): void {
    // When newsletter does not exist, we need to remove the scheduled task and sending queue.
    $queue = $task->getSendingQueue();
    $newsletter = $queue ? $queue->getNewsletter() : null;
    if (!$newsletter) {
      $this->scheduledTasksRepository->remove($task);
      if ($queue) {
        $this->sendingQueuesRepository->remove($queue);
      }
      $this->sendingQueuesRepository->flush();
      return;
    }

    // Only deleted newsletter or newsletter with unexpected state should pass here.
    // Because this state cannot proceed with sending, we need to pause the scheduled task.
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $this->scheduledTasksRepository->flush();
  }
}
