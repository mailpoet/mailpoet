<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

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
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
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
    $parentNewsletterEntity->setStatus( NewsletterEntity::STATUS_DRAFT);
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
      ->stringContainsString(date_i18n('jS', $wp->currentTime('timestamp')));
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
