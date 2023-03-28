<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Router\Router;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
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

  /** @var SendingTask */
  private $sendingTask;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

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

    $this->sendingTask = SendingTask::create();
    $this->sendingTask->newsletter_id = $this->newsletter->getId();
    $this->sendingTask->save();
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
  }

  public function testItConstructs() {
    expect($this->newsletterTask->trackingEnabled)->true();
  }

  public function testItDoesNotGetNewsletterWhenStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $newsletterEntity->setStatus(NewsletterEntity::STATUS_DRAFT);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->null();

    // active or sending statuses return newsletter
    $newsletterEntity->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf(NewsletterEntity::class);

    $newsletterEntity->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->newslettersRepository->persist($newsletterEntity);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf(NewsletterEntity::class);
  }

  public function testItDoesNotGetDeletedNewsletter() {
    $this->newsletter->setDeletedAt(new Carbon());
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->null();
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
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->null();

    // active or sending statuses return newsletter
    $parentNewsletterEntity->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $this->newslettersRepository->persist($parentNewsletterEntity);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf(NewsletterEntity::class);

    $parentNewsletterEntity->setStatus(NewsletterEntity::STATUS_SENDING);
    $this->newslettersRepository->persist($parentNewsletterEntity);
    $this->newslettersRepository->flush();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf(NewsletterEntity::class);
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
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->null();
  }

  public function testItReturnsNewsletterObjectWhenRenderedNewsletterBodyExistsInTheQueue() {
    $this->sendingTask->newsletterRenderedBody = ['html' => 'test', 'text' => 'test'];
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    expect($result instanceof NewsletterEntity)->true();
  }

  public function testItHashesLinksAndInsertsTrackingImageWhenTrackingIsEnabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = true;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    $this->assertInstanceOf(NewsletterLinkEntity::class, $link);
    $updatedQueue = SendingTask::getByNewsletterId($this->newsletter->getId());
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_click_data]-' . $link->getHash());
    expect($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after_pre_process';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->array();
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof NewsletterEntity)->true();
  }

  public function testItDoesNotHashLinksAndInsertTrackingCodeWhenTrackingIsDisabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = false;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    expect($link)->null();
    $updatedQueue = SendingTask::getByNewsletterId($this->newsletter->getId());
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_click_data]');
    expect($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after_pre_process';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->array();
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof NewsletterEntity)->true();
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
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    expect($result)->false();
    // newsletter is deleted.
    $this->entityManager->clear(); // needed while part of the code uses Paris models and part uses Doctrine
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    expect($newsletter)->null();
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
    $result = $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $newsletterPost = $newsletterPostRepository->findOneBy(['newsletter' => $this->newsletter->getId()]);
    expect($newsletterPost)->isInstanceOf(NewsletterPostEntity::class);
    expect($result)->notEquals(false);
    expect($newsletterPost->getPostId())->equals('10');
  }

  public function testItUpdatesStatusAndSetsSentAtDateOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $sendingQueue = $this->sendingTask->queue();
    $sendingQueue->processedAt = new \DateTime();

    // newsletter type is 'standard'
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $this->sendingTask);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    expect($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    expect($sentAt->getTimestamp())->equals($sendingQueue->processedAt->getTimestamp(), 1);

    // newsletter type is 'notification history'
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $this->sendingTask);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    expect($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    expect($sentAt->getTimestamp())->equals($sendingQueue->processedAt->getTimestamp(), 1);

    // all other newsletter types
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $this->sendingTask);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $updatedNewsletter);
    expect($updatedNewsletter->getStatus())->notEquals(NewsletterEntity::STATUS_SENT);
  }

  public function testItDoesNotRenderSubscriberShortcodeInSubjectWhenPreprocessingNewsletter() {
    $this->newsletter->setSubject('Newsletter for [subscriber:firstname] [date:dordinal]');
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    $this->newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $this->sendingTask = SendingTask::getByNewsletterId($this->newsletter->getId());
    $wp = new WPFunctions();
    expect($this->sendingTask->newsletterRenderedSubject)
      ->stringContainsString(date_i18n('jS', $wp->currentTime('timestamp')));
  }

  public function testItUsesADefaultSubjectIfRenderedSubjectIsEmptyWhenPreprocessingNewsletter() {
    $this->newsletter->setSubject('  [custom_shortcode:should_render_empty]  ');
    $this->newslettersRepository->persist($this->newsletter);
    $this->newslettersRepository->flush();
    $this->newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $this->sendingTask = SendingTask::getByNewsletterId($this->newsletter->getId());
    expect($this->sendingTask->newsletterRenderedSubject)
      ->equals('No subject');
  }

  public function testItUsesRenderedNewsletterBodyAndSubjectFromQueueObjectWhenPreparingNewsletterForSending() {
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->sendingTask->newsletterRenderedBody = [
      'html' => 'queue HTML body',
      'text' => 'queue TEXT body',
    ];
    $this->sendingTask->newsletterRenderedSubject = 'queue subject';
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
      $this->sendingTask
    );
    expect($result['subject'])->equals('queue subject');
    expect($result['body']['html'])->equals('queue HTML body');
    expect($result['body']['text'])->equals('queue TEXT body');
  }

  public function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingTask
    );
    expect($result['subject'])->stringContainsString($this->subscriber->getFirstName());
    expect($result['body']['html'])
      ->stringContainsString(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->stringContainsString(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItDoesNotReplaceSubscriberDataInLinksWhenTrackingIsNotEnabled() {
    $newsletterTask = $this->newsletterTask;
    $newsletterTask->trackingEnabled = false;
    $newsletter = $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingTask
    );
    expect($result['body']['html'])
      ->stringNotContainsString(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->stringNotContainsString(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItLogsErrorWhenQueueWithCannotBeSaved() {
    $this->sendingTask->nonExistentColumn = true; // this will trigger save error
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();

      expect(is_array($mailerLog['error']));
      if (is_array($mailerLog['error'])) {
        expect($mailerLog['error']['operation'])->equals('queue_save');
        expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
      }
    }
  }

  public function testItLogsErrorWhenExistingRenderedNewsletterBodyIsInvalid() {
    $sendingTaskMock = $this->createMock(SendingTask::class);
    $sendingTaskMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn('a:2:{s:4:"html"');
    $sendingTaskMock
      ->expects($this->once())
      ->method('validate')
      ->willReturn(false);
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $sendingTaskMock);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();
      expect(is_array($mailerLog['error']));
      if (is_array($mailerLog['error'])) {
        expect($mailerLog['error']['operation'])->equals('queue_save');
        expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
      }
    }
  }

  public function testItLogsErrorWhenNewlyRenderedNewsletterBodyIsInvalid() {
    $sendingTaskMock = $this->createMock(SendingTask::class);
    $sendingTaskMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $sendingTaskMock
      ->expects($this->once())
      ->method('save');
    $sendingTaskMock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $sendingTaskMock
      ->expects($this->any())
      ->method('__get')
      ->will($this->onConsecutiveCalls($this->sendingTask->id, $this->sendingTask->taskId, $this->sendingTask->id));

    $sendingQueuesTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $conn = $this->entityManager->getConnection();
    $stmt = $conn->prepare("UPDATE $sendingQueuesTable SET newsletter_rendered_body = :invalid_body WHERE id = :id");
    $stmt->executeQuery([
      'invalid_body' => 'a:2:{s:4:"html"',
      'id' => $this->sendingTask->id,
    ]);
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $sendingTaskMock);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();
      expect(is_array($mailerLog['error']));
      if (is_array($mailerLog['error'])) {
        expect($mailerLog['error']['operation'])->equals('queue_save');
        expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
      }
    }
  }

  public function testItPreProcessesNewsletterWhenNewlyRenderedNewsletterBodyIsValid() {
    $sendingTaskMock = $this->createMock(SendingTask::class);
    $sendingTaskMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $sendingTaskMock
      ->expects($this->once())
      ->method('save');
    $sendingTaskMock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $sendingTaskMock
      ->expects($this->any())
      ->method('__get')
      ->will($this->onConsecutiveCalls($this->sendingTask->id, $this->sendingTask->taskId, $this->sendingTask->id, $this->sendingTask->newsletterRenderedBody));

    // properly serialized object
    $sendingQueue = $this->sendingQueuesRepository->findOneById($this->sendingTask->id);
    $this->assertInstanceOf(SendingQueueEntity::class, $sendingQueue);
    $sendingQueue->setNewsletterRenderedBody(['html' => 'test', 'text' => 'test']);
    $this->sendingQueuesRepository->persist($sendingQueue);
    $this->sendingQueuesRepository->flush();

    $emoji = $this->make(
      Emoji::class,
      ['encodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })]
    );
    $newsletterTask = new NewsletterTask(null, null, null, $emoji);
    expect($newsletterTask->preProcessNewsletter($this->newsletter, $sendingTaskMock))->equals($this->newsletter);
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
    $this->sendingTask->newsletterId = $newsletter->getId();
    $this->sendingTask->save();

    $newsletter = $newsletterTask->preProcessNewsletter($newsletter, $this->sendingTask);
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletterEntity,
      $this->subscriber,
      $this->sendingTask
    );
    $wooCommerceHelper = $this->diContainer->get(Helper::class);
    $coupon = (string)$wooCommerceHelper->getLatestCoupon();

    expect($result['body']['html'])->stringNotContainsString(Coupon::CODE_PLACEHOLDER);
    expect($result['body']['html'])->stringContainsString($coupon);
    expect($result['body']['text'])->stringNotContainsString(Coupon::CODE_PLACEHOLDER);
    expect($result['body']['text'])->stringContainsString($coupon);
  }

  public function testCampaignIdDoesNotChangeIfContentStaysTheSame() {
    $newsletter = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    $campaignId = $this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters);
    expect($campaignId)->equals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
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
    expect($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
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
    expect($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $updatedRenderedNewsletters));
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    expect($originalCampaignId)->equals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewsletters));
  }

  public function testCampaignIdDependsOnNewsletterId() {
    $newsletter1 = (new NewsletterFactory())->withSubject('Subject')->create();
    $newsletter2 = (new NewsletterFactory())->withSubject('Subject')->create();
    $renderedNewsletters = [
      'text' => 'text body',
    ];
    expect($this->newsletterTask->calculateCampaignId($newsletter1, $renderedNewsletters))->notEquals($this->newsletterTask->calculateCampaignId($newsletter2, $renderedNewsletters));
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
    expect($originalCampaignId)->notEquals($this->newsletterTask->calculateCampaignId($newsletter, $renderedNewslettersDifferentImageSrc));
  }
}
