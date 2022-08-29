<?php

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
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterTest extends \MailPoetTest {
  /** @var NewsletterTask */
  private $newsletterTask;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var Newsletter */
  private $newsletter;

  /** @var Newsletter */
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
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = NewsletterEntity::TYPE_STANDARD;
    $this->newsletter->status = NewsletterEntity::STATUS_ACTIVE;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->preheader = '';
    $this->newsletter->save();
    $this->parentNewsletter = Newsletter::create();
    $this->parentNewsletter->type = NewsletterEntity::TYPE_STANDARD;
    $this->parentNewsletter->status = NewsletterEntity::STATUS_ACTIVE;
    $this->parentNewsletter->subject = 'parent newsletter';
    $this->parentNewsletter->body = 'parent body';
    $this->parentNewsletter->preheader = '';
    $this->parentNewsletter->save();
    $this->sendingTask = SendingTask::create();
    $this->sendingTask->newsletter_id = $this->newsletter->id;
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
    $newsletter = $this->newsletter;
    $newsletter->status = NewsletterEntity::STATUS_DRAFT;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->false();

    // active or sending statuses return newsletter
    $newsletter = $this->newsletter;
    $newsletter->status = NewsletterEntity::STATUS_ACTIVE;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf('Mailpoet\Models\Newsletter');

    $newsletter = $this->newsletter;
    $newsletter->status = NewsletterEntity::STATUS_SENDING;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  public function testItDoesNotGetDeletedNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->set_expr('deleted_at', 'NOW()');
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->false();
  }

  public function testItDoesNotGetNewsletterWhenParentNewsletterStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = NewsletterEntity::STATUS_DRAFT;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->false();

    // active or sending statuses return newsletter
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = NewsletterEntity::STATUS_ACTIVE;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf('Mailpoet\Models\Newsletter');

    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = NewsletterEntity::STATUS_SENDING;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  public function testItDoesNotGetDeletedNewsletterWhenParentNewsletterIsDeleted() {
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->set_expr('deleted_at', 'NOW()');
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->sendingTask))->false();
  }

  public function testItReturnsNewsletterObjectWhenRenderedNewsletterBodyExistsInTheQueue() {
    $queue = $this->sendingTask;
    $queue->newsletterRenderedBody = ['html' => 'test', 'text' => 'test'];
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $queue);
    expect($result instanceof \MailPoet\Models\Newsletter)->true();
  }

  public function testItHashesLinksAndInsertsTrackingImageWhenTrackingIsEnabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = true;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->id]);
    assert($link instanceof NewsletterLinkEntity);
    $updatedQueue = SendingTask::getByNewsletterId($this->newsletter->id);
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_click_data]-' . $link->getHash());
    expect($renderedNewsletter['html'])
      ->stringContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->array();
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof Newsletter)->true();
  }

  public function testItDoesNotHashLinksAndInsertTrackingCodeWhenTrackingIsDisabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = false;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $link = $this->newsletterLinkRepository->findOneBy(['newsletter' => $this->newsletter->id]);
    expect($link)->null();
    $updatedQueue = SendingTask::getByNewsletterId($this->newsletter->id);
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_click_data]');
    expect($renderedNewsletter['html'])
      ->stringNotContainsString('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->array();
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof Newsletter)->true();
  }

  public function testItReturnsFalseAndDeletesNewsletterWhenPostNotificationContainsNoPosts() {
    $newsletter = $this->newsletter;

    $newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $newsletter->id;
    // replace post id data tag with something else
    $newsletter->body = str_replace('data-post-id', 'id', $newsletter->getBodyString());
    $newsletter->save();
    // returned result is false
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    expect($result)->false();
    // newsletter is deleted.
    $this->entityManager->clear(); // needed while part of the code uses Paris models and part uses Doctrine
    $newsletter = $this->newslettersRepository->findOneById($newsletter->id);
    expect($newsletter)->null();
  }

  public function testItSavesNewsletterPosts() {
    $newsletterPostRepository = ContainerWrapper::getInstance()->get(NewsletterPostsRepository::class);
    $this->newsletter->type = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
    $this->newsletter->parentId = $this->newsletter->id;
    $postsTask = $this->make(PostsTask::class, [
      'getAlcPostsCount' => 1,
      'loggerFactory' => $this->loggerFactory,
      'newsletterPostRepository' => $newsletterPostRepository,
    ]);
    $this->newsletter->save();
    $newsletterTask = new NewsletterTask(new WPFunctions, $postsTask);
    $result = $newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $newsletterPost = $newsletterPostRepository->findOneBy(['newsletter' => $this->newsletter->id]);
    expect($newsletterPost)->isInstanceOf(NewsletterPostEntity::class);
    expect($result)->notEquals(false);
    expect($newsletterPost->getPostId())->equals('10');
  }

  public function testItUpdatesStatusAndSetsSentAtDateOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = new \stdClass();
    $queue->processedAt = date('Y-m-d H:i:s');

    // newsletter type is 'standard'
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    assert($updatedNewsletter instanceof NewsletterEntity);
    expect($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    expect($sentAt->format('Y-m-d H:i:s'))->equals($queue->processedAt);

    // newsletter type is 'notification history'
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    assert($updatedNewsletter instanceof NewsletterEntity);
    expect($updatedNewsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    $sentAt = $updatedNewsletter->getSentAt();
    $this->assertInstanceOf(\DateTime::class, $sentAt);
    expect($sentAt->format('Y-m-d H:i:s'))->equals($queue->processedAt);

    // all other newsletter types
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus('not_sent');
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    assert($updatedNewsletter instanceof NewsletterEntity);
    expect($updatedNewsletter->getStatus())->notEquals(NewsletterEntity::STATUS_SENT);
  }

  public function testItDoesNotRenderSubscriberShortcodeInSubjectWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = 'Newsletter for [subscriber:firstname] [date:dordinal]';
    $queue = $this->sendingTask;
    $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    $wp = new WPFunctions();
    expect($queue->newsletterRenderedSubject)
      ->stringContainsString(date_i18n('jS', $wp->currentTime('timestamp')));
  }

  public function testItUsesADefaultSubjectIfRenderedSubjectIsEmptyWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = '  [custom_shortcode:should_render_empty]  ';
    $queue = $this->sendingTask;
    $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    expect($queue->newsletterRenderedSubject)
      ->equals('No subject');
  }

  public function testItUsesRenderedNewsletterBodyAndSubjectFromQueueObjectWhenPreparingNewsletterForSending() {
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $queue = $this->sendingTask;
    $queue->newsletterRenderedBody = [
      'html' => 'queue HTML body',
      'text' => 'queue TEXT body',
    ];
    $queue->newsletterRenderedSubject = 'queue subject';
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
      $queue
    );
    expect($result['subject'])->equals('queue subject');
    expect($result['body']['html'])->equals('queue HTML body');
    expect($result['body']['text'])->equals('queue TEXT body');
  }

  public function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->sendingTask);
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
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
    $newsletterEntity = $this->newslettersRepository->findOneById($newsletter->id);
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

  public function testItGetsSegments() {
    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $segmentIds = [];

    for ($i = 1; $i <= 3; $i++) {
      $segment = (new SegmentFactory())->create();
      $segmentIds[] = $segment->getId();
      $newsletterSegment = new NewsletterSegmentEntity($newsletterEntity, $segment);
      $this->entityManager->persist($newsletterSegment);
    }
    $this->entityManager->flush();

    expect($this->newsletterTask->getNewsletterSegments($newsletterEntity))->equals(
      $segmentIds
    );
  }

  public function testItLogsErrorWhenQueueWithCannotBeSaved() {
    $queue = $this->sendingTask;
    $queue->nonExistentColumn = true; // this will trigger save error
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $queue);
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
    $queueMock = $this->createMock(SendingTask::class);
    $queueMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn('a:2:{s:4:"html"');
    $queueMock
      ->expects($this->once())
      ->method('validate')
      ->willReturn(false);
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $queueMock);
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
    $queue = $this->sendingTask;
    $queueMock = $this->createMock(SendingTask::class);
    $queueMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $queueMock
      ->expects($this->once())
      ->method('save');
    $queueMock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $queueMock
      ->expects($this->any())
      ->method('__get')
      ->will($this->onConsecutiveCalls($queue->id, $queue->taskId, $queue->id));

    $sendingQueuesTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $conn = $this->entityManager->getConnection();
    $stmt = $conn->prepare("UPDATE $sendingQueuesTable SET newsletter_rendered_body = :invalid_body WHERE id = :id");
    $stmt->executeQuery([
      'invalid_body' => 'a:2:{s:4:"html"',
      'id' => $queue->id,
    ]);
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $queueMock);
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
    $queue = $this->sendingTask;
    $queueMock = $this->createMock(SendingTask::class);
    $queueMock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $queueMock
      ->expects($this->once())
      ->method('save');
    $queueMock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $queueMock
      ->expects($this->any())
      ->method('__get')
      ->will($this->onConsecutiveCalls($queue->id, $queue->taskId, $queue->id, $queue->newsletterRenderedBody));

    // properly serialized object
    $sendingQueue = $this->sendingQueuesRepository->findOneById($queue->id);
    assert($sendingQueue instanceof SendingQueueEntity);
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
    expect($newsletterTask->preProcessNewsletter($this->newsletter, $queueMock))->equals($this->newsletter);
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(NewsletterPostEntity::class);
  }
}
