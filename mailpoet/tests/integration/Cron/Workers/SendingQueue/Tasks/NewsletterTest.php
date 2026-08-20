<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\NewsletterProcessingException;
use MailPoet\Router\Router;
use MailPoet\RuntimeException;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class NewsletterTest extends \MailPoetTest {
  /** @var NewsletterTask */
  private $newsletterTask;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var NewsletterEntity */
  private $parentNewsletter;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var ScheduledTaskEntity */
  private $scheduledTaskEntity;

  /** @var SendingQueueEntity */
  private $sendingQueueEntity;

  public function _before() {
    parent::_before();
    $this->newsletterTask = new NewsletterTask();
    $this->subscriber = (new SubscriberFactory())->create();
    $this->newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->withBody(json_decode(Fixtures::get('newsletter_body_template'), true))
      ->create();

    $this->parentNewsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject('parent newsletter')
      ->create();

    $this->scheduledTaskEntity = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->sendingQueueEntity = (new SendingQueueFactory())->create($this->scheduledTaskEntity, $this->newsletter);

    $this->loggerFactory = LoggerFactory::getInstance();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItConstructs() {
    verify($this->newsletterTask->trackingEnabled)->true();
  }

  public function testItDoesNotGetNewsletterWhenStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterEntity->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->null();

    // active or sending statuses return newsletter
    $newsletterEntity->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->instanceOf(NewsletterEntity::class);

    $newsletterEntity->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->instanceOf(NewsletterEntity::class);
  }

  public function testItDoesNotGetDeletedNewsletter() {
    $this->newsletter->setDeletedAt(new Carbon());
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->null();
  }

  public function testItDoesNotGetNewsletterWhenParentNewsletterStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $parentNewsletterEntity = $this->newslettersRepository->findOneById($this->parentNewsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $parentNewsletterEntity);
    $parentNewsletterEntity->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->newslettersRepository->persist($parentNewsletterEntity);
    $this->newslettersRepository->flush();
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterEntity->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletterEntity->setParent($parentNewsletterEntity);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->null();

    // active or sending statuses return newsletter
    $parentNewsletterEntity->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->newslettersRepository->persist($parentNewsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->instanceOf(NewsletterEntity::class);

    $parentNewsletterEntity->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->newslettersRepository->persist($parentNewsletterEntity);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->instanceOf(NewsletterEntity::class);
  }

  public function testItDoesNotGetDeletedNewsletterWhenParentNewsletterIsDeleted() {
    $this->parentNewsletter->setDeletedAt(new Carbon());
    $this->newslettersRepository->persist($this->parentNewsletter);
    $this->newslettersRepository->flush();
    $newsletter = $this->newsletter;
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setParent($this->parentNewsletter);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    verify($this->newsletterTask->getNewsletterFromQueue($this->scheduledTaskEntity))->null();
  }

  public function testItReturnsNewsletterObjectWhenRenderedNewsletterBodyExistsInTheQueue() {
    $this->sendingQueueEntity->setNewsletterRenderedBody(['html' => 'test', 'text' => 'test']);
    $this->entityManager->persist($this->sendingQueueEntity);
    $this->entityManager->flush();
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    verify($result instanceof NewsletterEntity)->true();
  }

  public function testItHashesLinksAndInsertsTrackingImageWhenTrackingIsEnabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    verify($this->sendingQueueEntity->getNewsletterRenderedBody())->null();
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = true;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $link);
    $renderedNewsletter = $this->sendingQueueEntity->getNewsletterRenderedBody();
    $this->assertIsArray($renderedNewsletter);
    verify($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_click_data]-' . $link->getHash());
    verify($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after_pre_process';
    verify(WPHooksHelper::isFilterApplied($hookName))->true();
    verify(WPHooksHelper::getFilterApplied($hookName)[0])->isArray();
    verify(WPHooksHelper::getFilterApplied($hookName)[1] instanceof NewsletterEntity)->true();
  }

  public function testItDoesNotHashLinksAndInsertTrackingCodeWhenTrackingIsDisabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    verify($this->sendingQueueEntity->getNewsletterRenderedBody())->null();
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = false;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    verify($link)->null();
    $renderedNewsletter = $this->sendingQueueEntity->getNewsletterRenderedBody();
    $this->assertIsArray($renderedNewsletter);
    verify($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_click_data]');
    verify($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after_pre_process';
    verify(WPHooksHelper::isFilterApplied($hookName))->true();
    verify(WPHooksHelper::getFilterApplied($hookName)[0])->isArray();
    verify(WPHooksHelper::getFilterApplied($hookName)[1] instanceof NewsletterEntity)->true();
  }

  public function testItReturnsFalseAndDeletesNewsletterWhenPostNotificationContainsNoPosts() {
    $this->newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $this->newsletter->setParent($this->newsletter);
    // replace post id data tag with something else
    $body = $this->newsletter->getBody();
    $body['content'] = json_decode(str_replace('data-post-id', 'id', $this->newsletter->getContent()), true);
    $this->newsletter->setBody($body);
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    // returned result is false
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    verify($result)->false();
    // newsletter is deleted.
    $this->entityManager->clear(); // needed while part of the code uses Paris models and part uses Doctrine
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    verify($newsletter)->null();
  }

  public function testItSavesNewsletterPosts() {
    $newsletterPostRepository = ContainerWrapper::getInstance()->get(NewsletterPostsRepository::class);
    $this->newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $this->newsletter->setParent($this->newsletter);
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    $postsTask = $this->make(PostsTask::class, [
      'getAlcPostsCount' => 1,
      'loggerFactory' => $this->loggerFactory,
      'newsletterPostRepository' => $newsletterPostRepository,
    ]);
    $newsletterTask = new NewsletterTask(new WPFunctions, $postsTask);
    $result = $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $newsletterPost = $newsletterPostRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    verify($newsletterPost)->instanceOf(NewsletterPostEntity::class);
    verify($result)->notEquals(false);
    $this->assertInstanceOf(NewsletterPostEntity::class, $newsletterPost);
    verify($newsletterPost->getPostId())->equals('10');
  }

  public function testItUpdatesStatusAndSetsSentAtDateOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);

    $this->scheduledTaskEntity->setProcessedAt(new Carbon());
    $this->scheduledTasksRepository->persist($this->scheduledTaskEntity);
    $this->scheduledTasksRepository->flush();

    // newsletter type is 'standard'
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    verify($sentAt)->equalsWithDelta($this->scheduledTaskEntity->getProcessedAt(), 1);

    // newsletter type is 'notification history'
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    verify($sentAt)->equalsWithDelta($this->scheduledTaskEntity->getProcessedAt(), 1);

    // all other newsletter types
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    verify($updatedNewsletter->getStatus())->notEquals(NewsletterEntity::STATUS_SENT);
  }

  public function testItDoesNotRenderSubscriberShortcodeInSubjectWhenPreprocessingNewsletter() {
    $this->newsletter->setSubject('Newsletter for [subscriber:firstname] [date:dordinal]');
    $this->newslettersRepository->persist($this->newsletter);
    $newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->newsletter = $newsletter;

    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $this->newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $wp = new WPFunctions();
    verify($sendingQueue->getNewsletterRenderedSubject())
      ->stringContainsString(date_i18n('jS', $wp->currentTime('timestamp', true)));
  }

  public function testItUsesADefaultSubjectIfRenderedSubjectIsEmptyWhenPreprocessingNewsletter() {
    $this->newsletter->setSubject('  [custom_shortcode:should_render_empty]  ');
    $this->newslettersRepository->persist($this->newsletter);
    $newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->newsletter = $newsletter;

    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $this->newsletter]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    verify($sendingQueue->getNewsletterRenderedSubject())
      ->equals('No subject');
  }

  public function testItUsesRenderedNewsletterBodyAndSubjectFromQueueObjectWhenPreparingNewsletterForSending() {
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);

    $this->sendingQueueEntity->setNewsletterRenderedBody([
      'html' => 'queue HTML body',
      'text' => 'queue TEXT body',
    ]);
    $this->sendingQueueEntity->setNewsletterRenderedSubject('queue subject');
    $this->entityManager->persist($this->sendingQueueEntity);

    $emoji = $this->make(
      Emoji::class,
      ['decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })]
    );
    $newsletterTask = new NewsletterTask(null, null, null, $emoji);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );
    verify($result['subject'])->equals('queue subject');
    verify($result['body']['html'])->equals('queue HTML body');
    verify($result['body']['text'])->equals('queue TEXT body');
  }

  public function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );
    verify($result['subject'])->stringContainsString($this->subscriber->getFirstName());
    verify($result['body']['html'])
      ->stringContainsString(Router::NAME . '&endpoint=track&action=click&data=');
    verify($result['body']['text'])
      ->stringContainsString(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItDoesNotReplaceSubscriberDataInLinksWhenTrackingIsNotEnabled() {
    $newsletterTask = $this->newsletterTask;
    $newsletterTask->trackingEnabled = false;
    $newsletterEntity = $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );
    verify($result['body']['html'])
      ->stringNotContainsString(Router::NAME . '&endpoint=track&action=click&data=');
    verify($result['body']['text'])
      ->stringNotContainsString(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItRemovesOpenTrackingPixelForSubscriberWithoutConsent() {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );

    // The pixel must not ship: neither the placeholder data tag, a
    // stripped-to-empty-src <img>, nor a resolved open-tracking URL (which
    // is what the client would actually request on open) may remain.
    $this->assertStringNotContainsString(Links::DATA_TAG_OPEN, $result['body']['html']);
    $this->assertStringNotContainsString('<img alt="" class="" src=""', $result['body']['html']);
    $this->assertStringNotContainsString('endpoint=track&action=open', $result['body']['html']);
  }

  public function testItDoesNotRewriteClickLinksForSubscriberWithoutConsent() {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );

    // No tracked click URL may ship: following one tells our server they
    // clicked, whether or not we record it.
    $this->assertStringNotContainsString('endpoint=track&action=click', $result['body']['html']);
    $this->assertStringNotContainsString('endpoint=track&action=click', $result['body']['text']);
    // And no placeholder may leak into the delivered email either.
    $this->assertStringNotContainsString(Links::DATA_TAG_CLICK, $result['body']['html']);
  }

  public function testItStillDeliversWorkingLinksToSubscriberWithoutConsent() {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );

    // Untracking must not break the email: the real destination still ships,
    // and link shortcodes (unsubscribe, manage subscription) are still
    // resolved to real URLs rather than left as raw shortcode text.
    $this->assertStringContainsString('http://example.com', $result['body']['html']);
    $this->assertStringNotContainsString('[link:', $result['body']['html']);
    $this->assertStringNotContainsString('[link:', $result['body']['text']);
  }

  public function testItResolvesLinkShortcodesWithArgumentsForSubscriberWithoutConsent() {
    // Link shortcodes may carry an argument ([link:action | name:value]); the
    // stored link keeps the whole thing, so untracking has to resolve it rather
    // than shipping the raw shortcode as an href.
    $shortcode = '[link:mailpoet_test_custom_url | token:abc123]';
    $resolvedUrl = 'http://example.com/resolved-custom-url';
    $receivedArguments = null;
    $wp = new WPFunctions();
    $wp->addFilter(
      'mailpoet_newsletter_shortcode_link',
      function ($url, $newsletter, $subscriber, $queue, $arguments) use ($resolvedUrl, &$receivedArguments) {
        $receivedArguments = $arguments;
        return $resolvedUrl;
      },
      10,
      6
    );

    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject('Parameterised link shortcode')
      ->withBody($this->bodyWithLink('<a href="' . $shortcode . '">Custom link</a>'))
      ->create();
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $queue = (new SendingQueueFactory())->create($task, $newsletter);

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($newsletter, $task);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $queue
    );

    $wp->removeAllFilters('mailpoet_newsletter_shortcode_link');

    $this->assertStringNotContainsString('[link:', $result['body']['html']);
    $this->assertStringContainsString($resolvedUrl, $result['body']['html']);
    // The argument has to survive too. Passing only the inner text of the
    // shortcode resolves the action but silently loses the arguments, which
    // would still satisfy the two assertions above.
    $this->assertSame(['token' => 'abc123'], $receivedArguments);
  }

  /**
   * @return array<string, mixed>
   */
  private function bodyWithLink(string $html): array {
    return [
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => ['block' => []],
        'blocks' => [
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => ['block' => []],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => ['block' => []],
                'blocks' => [
                  [
                    'type' => 'text',
                    'text' => $html,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  public function testItResolvesUrlEmbeddedShortcodesForSubscriberWithoutConsent() {
    // A link URL may itself contain a personalisation shortcode. The tracked
    // path resolves those at click time in Clicks::processUrl(); the untracked
    // path has to do it at send time or the recipient gets a literal
    // placeholder in the address (STOMAIL-8340).
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $result = $this->prepareWithBody(
      '<a href="http://example.com/?email=[subscriber:email]">Personalised link</a>'
    );

    $this->assertStringNotContainsString('[subscriber:email]', $result['body']['html']);
    $this->assertStringContainsString(
      'http://example.com/?email=' . $this->subscriber->getEmail(),
      $result['body']['html']
    );
  }

  public function testItResolvesUppercaseLinkShortcodesForSubscriberWithoutConsent() {
    // The extractor that stores these is case-insensitive (Shortcodes::extract),
    // so an uppercased shortcode reaches the untracked path too.
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();

    $result = $this->prepareWithBody(
      '<a href="[LINK:subscription_unsubscribe_url]">Unsubscribe</a>'
    );

    $this->assertStringNotContainsString('[LINK:', $result['body']['html']);
    $this->assertStringContainsString('action=confirm_unsubscribe', $result['body']['html']);
  }

  private function prepareWithBody(string $linkHtml): array {
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject('Untracking shortcodes in URLs')
      ->withBody($this->bodyWithLink($linkHtml))
      ->create();
    $task = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $queue = (new SendingQueueFactory())->create($task, $newsletter);

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($newsletter, $task);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);

    return $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $queue
    );
  }

  public function testItKeepsOpenTrackingPixelForConsentingSubscriber() {
    $this->subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->entityManager->flush();

    $newsletterEntity = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );

    // Placeholder is gone because it was replaced with a real tracked URL.
    $this->assertStringNotContainsString(Links::DATA_TAG_OPEN, $result['body']['html']);
    $this->assertStringContainsString('endpoint=track&action=open', $result['body']['html']);
  }

  public function testItPausesSendingWhenOrderReviewUrlCannotBeResolved(): void {
    $postId = WPFunctions::get()->wpInsertPost([
      'post_type' => 'mailpoet_email',
      'post_status' => 'private',
      'post_title' => 'Order review email',
      'post_content' => '<!-- wp:button {"url":"[woocommerce/order-review-url]"} --><div class="wp-block-button"><a href="[woocommerce/order-review-url]">Leave a review</a></div><!-- /wp:button -->',
    ]);
    $this->assertIsInt($postId);
    $this->assertGreaterThan(0, $postId);

    $newsletter = (new NewsletterFactory())
      ->withAutomationType()
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withWpPostId($postId)
      ->create();
    $scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $sendingQueue = (new SendingQueueFactory())->create($scheduledTask, $newsletter);
    $sendingQueue->setCountTotal(1);
    $sendingQueue->setNewsletterRenderedSubject('Subject');
    $sendingQueue->setNewsletterRenderedBody([
      'html' => '<a data-link-href="[woocommerce/order-review-url]">Leave a review</a>',
      'text' => '[Leave a review]([woocommerce/order-review-url])',
    ]);
    $this->sendingQueuesRepository->persist($sendingQueue);
    $this->sendingQueuesRepository->flush();

    try {
      $this->newsletterTask->prepareNewsletterForSending(
        $newsletter,
        $this->subscriber,
        $sendingQueue
      );
      $this->fail('Expected order review URL resolution to stop sending.');
    } catch (NewsletterProcessingException $exception) {
      $this->assertSame('Cannot send the email because WooCommerce cannot generate an order review link for this order.', $exception->getMessage());
    }

    $this->entityManager->refresh($scheduledTask);
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $scheduledTask->getStatus());
  }

  public function testItPausesSendingWhenTrackedOrderReviewUrlLinkCannotBeResolved(): void {
    [$newsletter, $scheduledTask, $sendingQueue] = $this->createBlockEmailQueueWithTrackedLink(
      '[woocommerce/order-review-url]',
      'Leave a review'
    );

    try {
      $this->newsletterTask->prepareNewsletterForSending($newsletter, $this->subscriber, $sendingQueue);
      $this->fail('Expected order review URL resolution to stop sending.');
    } catch (NewsletterProcessingException $exception) {
      $this->assertSame('Cannot send the email because WooCommerce cannot generate an order review link for this order.', $exception->getMessage());
    }

    $this->entityManager->refresh($scheduledTask);
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $scheduledTask->getStatus());
  }

  public function testItKeepsTagLinksTrackedForConsentingSubscriber(): void {
    $this->subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->entityManager->flush();
    [$newsletter, , $sendingQueue] = $this->createBlockEmailQueueWithTrackedLink(
      '[mailpoet/subscription-unsubscribe-url]',
      'Unsubscribe'
    );

    $result = $this->newsletterTask->prepareNewsletterForSending($newsletter, $this->subscriber, $sendingQueue);

    $this->assertStringContainsString('endpoint=track&action=click', $result['body']['html']);
    $this->assertStringContainsString('endpoint=track&action=click', $result['body']['text']);
    $this->assertStringNotContainsString('[mailpoet/subscription-unsubscribe-url]', $result['body']['html']);
  }

  public function testItResolvesTagLinksForSubscriberWithoutConsent(): void {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();
    [$newsletter, , $sendingQueue] = $this->createBlockEmailQueueWithTrackedLink(
      '[mailpoet/subscription-unsubscribe-url]',
      'Unsubscribe'
    );

    $result = $this->newsletterTask->prepareNewsletterForSending($newsletter, $this->subscriber, $sendingQueue);

    foreach (['html', 'text'] as $part) {
      $this->assertStringNotContainsString('endpoint=track', $result['body'][$part]);
      $this->assertStringNotContainsString('[mailpoet/subscription-unsubscribe-url]', $result['body'][$part]);
      $this->assertStringNotContainsString(Links::DATA_TAG_CLICK, $result['body'][$part]);
      $this->assertStringContainsString('action=confirm_unsubscribe', $result['body'][$part]);
    }
  }

  public function testItStripsUnresolvableTagLinksForSubscriberWithoutConsent(): void {
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK
    );
    $this->entityManager->flush();
    [$newsletter, , $sendingQueue] = $this->createBlockEmailQueueWithTrackedLink(
      '[acme/dead-url]',
      'Gone'
    );
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);

    try {
      $registry->register(new Personalization_Tag('Dead URL', 'acme/dead-url', 'Test', function (): string {
        return '';
      }));
      $result = $this->newsletterTask->prepareNewsletterForSending($newsletter, $this->subscriber, $sendingQueue);
    } finally {
      $registry->unregister('[acme/dead-url]');
    }

    $this->assertStringContainsString('<a href="">Gone</a>', $result['body']['html']);
    $this->assertStringContainsString('[Gone]()', $result['body']['text']);
  }

  public function testItResolvesTagLinksInTextBodyWhenTrackingIsDisabled(): void {
    $newsletterTask = Stub::copy($this->newsletterTask, ['trackingEnabled' => false]);
    [$newsletter, , $sendingQueue] = $this->createBlockEmailQueueWithTrackedLink(
      '[mailpoet/subscription-unsubscribe-url]',
      'Unsubscribe'
    );
    // Without tracking the normalized token stays in the body instead of a hash
    $sendingQueue->setNewsletterRenderedBody([
      'html' => '<a href="[mailpoet/subscription-unsubscribe-url]">Unsubscribe</a>',
      'text' => '[Unsubscribe]([mailpoet/subscription-unsubscribe-url])',
    ]);
    $this->sendingQueuesRepository->flush();

    $result = $newsletterTask->prepareNewsletterForSending($newsletter, $this->subscriber, $sendingQueue);

    foreach (['html', 'text'] as $part) {
      $this->assertStringNotContainsString('[mailpoet/subscription-unsubscribe-url]', $result['body'][$part]);
      $this->assertStringContainsString('action=confirm_unsubscribe', $result['body'][$part]);
    }
  }

  /**
   * Block email whose rendered body already went through link tracking: the link is a hashed
   * placeholder and its URL is stored in newsletter_links.
   *
   * @return array{0: NewsletterEntity, 1: ScheduledTaskEntity, 2: SendingQueueEntity}
   */
  private function createBlockEmailQueueWithTrackedLink(string $url, string $linkText): array {
    $postId = WPFunctions::get()->wpInsertPost([
      'post_type' => 'mailpoet_email',
      'post_status' => 'private',
      'post_title' => 'Block email',
      'post_content' => '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->',
    ]);
    $this->assertIsInt($postId);

    $newsletter = (new NewsletterFactory())
      ->withAutomationType()
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withWpPostId($postId)
      ->create();
    $scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    $sendingQueue = (new SendingQueueFactory())->create($scheduledTask, $newsletter);
    $link = new NewsletterLinkEntity($newsletter, $sendingQueue, $url, 'abcdef');
    $this->entityManager->persist($link);
    $hashedLink = Links::DATA_TAG_CLICK . '-' . $link->getHash();
    $sendingQueue->setCountTotal(1);
    $sendingQueue->setNewsletterRenderedSubject('Subject');
    $sendingQueue->setNewsletterRenderedBody([
      'html' => '<a href="' . $hashedLink . '">' . $linkText . '</a>',
      'text' => '[' . $linkText . '](' . $hashedLink . ')',
    ]);
    $this->sendingQueuesRepository->persist($sendingQueue);
    $this->sendingQueuesRepository->flush();

    return [$newsletter, $scheduledTask, $sendingQueue];
  }

  public function testItLogsErrorWhenQueueWithCannotBeSaved() {
    $sendingQueuesRepositoryStub = $this->createStub(SendingQueuesRepository::class);
    $sendingQueuesRepositoryStub->method('flush')
      ->willThrowException(new \Exception());

    $newsletterTask = Stub::copy(
      new NewsletterTask(),
      ['sendingQueuesRepository' => $sendingQueuesRepositoryStub]
    );

    try {
      $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();

      expect(is_array($mailerLog['error']));
      if (is_array($mailerLog['error'])) {
        verify($mailerLog['error']['operation'])->equals('queue_save');
        verify($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
      }
    }
  }

  public function testItJustReturnsNewsletterWhenRenderedBodyAlreadyExists() {
    // properly serialized object
    $this->sendingQueueEntity->setNewsletterRenderedBody(['html' => 'test', 'text' => 'test']);
    $this->sendingQueuesRepository->persist($this->sendingQueueEntity);
    $this->sendingQueuesRepository->flush();

    $emoji = $this->make(
      Emoji::class,
      ['encodeEmojisInBody' => Expected::never()]
    );

    $newsletterTask = new NewsletterTask(null, null, null, $emoji);
    verify($newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity))->equals($this->newsletter);
  }

  public function testItThrowsExceptionWhenNewsletterRenderedBodyIsInvalid() {
    $emoji = $this->make(
      Emoji::class,
      ['encodeEmojisInBody' => Expected::once(function ($params) {
        return 'Invalid rendered body';
      })]
    );
    $newsletterTask = new NewsletterTask(null, null, null, $emoji);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Sending is waiting to be retried.');
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->scheduledTaskEntity);
  }

  /**
   * @group woo
   */
  public function testItGeneratesWooCommerceCouponForCouponBlock(): void {
    $newsletter = (new NewsletterFactory())
      ->loadBodyFrom('newsletterWithCoupon.json')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->create();
    $newsletterTask = $this->newsletterTask;
    // set newsletter with coupon
    $this->sendingQueueEntity->setNewsletter($newsletter);
    $this->sendingQueuesRepository->persist($this->sendingQueueEntity);

    $newsletterEntity = $newsletterTask->preProcessNewsletter($newsletter, $this->scheduledTaskEntity);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingQueueEntity
    );
    $wooCommerceHelper = $this->diContainer->get(Helper::class);
    $coupon = (string)$wooCommerceHelper->getLatestCoupon();

    verify($result['body']['html'])->stringNotContainsString(Coupon::CODE_PLACEHOLDER);
    verify($result['body']['html'])->stringContainsString($coupon);
    verify($result['body']['text'])->stringNotContainsString(Coupon::CODE_PLACEHOLDER);
    verify($result['body']['text'])->stringContainsString($coupon);
  }

  public function testCampaignIdDoesNotChangeIfContentStaysTheSame() {
    $newsletter = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    $campaignId = $this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters);
    verify($campaignId)->equals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
  }

  public function testCampaignIdChangesIfSubjectChanges() {
    $newsletter = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    $originalCampaignId = $this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters);
    $newsletter->setSubject('Subject 2');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    verify($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
  }

  public function testCampaignIdRevertsIfContentReverts() {
    $newsletter = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    $originalCampaignId = $this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters);
    $newsletter->setSubject('Subject 2');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    $updatedRenderedNewsletters = [
      'text' => 'text body updated',
    ];
    verify($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $updatedRenderedNewsletters));
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    verify($originalCampaignId)->equals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
  }

  public function testCampaignIdDependsOnNewsletterId() {
    $newsletter1 = (new NewsletterFactory())->withSubject('Subject')->create();
    $newsletter2 = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    verify($this->newsletterTask->calculateCampaignId($newsletter1, $renderedNewsletters))->notEquals($this->newsletterTask->calculateCampaignId($newsletter2, $renderedNewsletters));
  }

  public function testCampaignIdChangesIfImageChanges() {
    $newsletter = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => '[alt text] Text',
      'html' => '<img src="http://example.com/image.jpg" alt="alt text"><p>Text</p>',
    ];
    $originalCampaignId = $this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters);
    $renderedNewslettersDifferentImageSrc = [
      'text' => '[alt text] Text',
      'html' => '<img src="http://example.com/different-image-same-alt.jpg" alt="alt text"><p>Text</p>',
    ];
    verify($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewslettersDifferentImageSrc));
  }

  public function testPreProcessingSavesFilterSegmentData(): void {
    $filterSegment = (new DynamicSegment())->withEngagementScoreFilter(50, 'higherThan')->create();
    $this->newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->withBody(json_decode(Fixtures::get('newsletter_body_template'), true))
      ->withOptions([NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID => $filterSegment->getId()])
      ->withSendingQueue()
      ->create();

    // properly serialized object
    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    $newsletterTask = new NewsletterTask();
    $sendingQueueMeta = $sendingQueue->getMeta();
    verify($sendingQueueMeta)->null();
    verify($newsletterTask->preProcessNewsletter($this->newsletter, $scheduledTask))->equals($this->newsletter);
    $this->entityManager->refresh($sendingQueue);
    $updatedMeta = $sendingQueue->getMeta();
    verify($updatedMeta)->isArray();
    verify($updatedMeta)->arrayHasKey('filterSegment');
    $filterData = $updatedMeta['filterSegment']['filters'][0]['data'] ?? [];
    verify($filterData['value'])->equals(50);
    verify($filterData['operator'])->equals('higherThan');
    verify($filterData['connect'])->equals('and');
  }

  public function testItRecoverNewsletterFromInvalidSendingState(): void {
    // testing recovering newsletter when the welcome newsletter is draft
    $invalidNewsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_WELCOME)
      ->withStatus(NewsletterEntity::STATUS_DRAFT)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->withBody(json_decode(Fixtures::get('newsletter_body_template'), true))
      ->withSendingQueue(['status' => null])
      ->create();

    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $invalidNewsletter->getId()]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    $this->assertNull($scheduledTask->getStatus());
    $this->assertNull($this->newsletterTask->getNewsletterFromQueue($scheduledTask));
    $this->assertSame($scheduledTask->getStatus(), ScheduledTaskEntity::STATUS_PAUSED);

    // testing recovering newsletter when the standard newsletter is deleted
    $deletedNewsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withDeleted()
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->withBody(json_decode(Fixtures::get('newsletter_body_template'), true))
      ->withSendingQueue(['status' => null])
      ->create();

    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['newsletter' => $deletedNewsletter->getId()]);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $scheduledTask = $sendingQueue->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $scheduledTask);
    $this->entityManager->refresh($scheduledTask);
    $this->assertNull($scheduledTask->getStatus());
    $this->assertNull($this->newsletterTask->getNewsletterFromQueue($scheduledTask));
    $this->entityManager->refresh($scheduledTask);
    $this->assertSame($scheduledTask->getStatus(), ScheduledTaskEntity::STATUS_PAUSED);

    // testing recovering when a newsletter is deleted
    $scheduledTask = (new ScheduledTaskFactory())
      ->create(SendingQueue::TASK_TYPE, null);
    $sendingQueue = (new SendingQueueFactory())
      ->create($scheduledTask, $this->entityManager->getReference(NewsletterEntity::class, 999));

    $scheduledTaskId = $scheduledTask->getId();
    $sendingQueueId = $sendingQueue->getId();
    $this->assertNull($this->newsletterTask->getNewsletterFromQueue($scheduledTask));
    $this->entityManager->clear();
    $this->assertNull($this->scheduledTasksRepository->findOneById($scheduledTaskId));
    $this->assertNull($this->sendingQueuesRepository->findOneById($sendingQueueId));
  }

  public function testItThrowsExceptionWhenTaskHasNoQueue(): void {
    $scheduledTask = new ScheduledTaskEntity();
    $this->entityManager->persist($scheduledTask);
    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSubject(Fixtures::get('newsletter_subject_template'))
      ->withBody(json_decode(Fixtures::get('newsletter_body_template'), true))
      ->create();
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Can‘t pre-process newsletter without queue.');
    $this->newsletterTask->preProcessNewsletter($newsletter, $scheduledTask);
  }
}
