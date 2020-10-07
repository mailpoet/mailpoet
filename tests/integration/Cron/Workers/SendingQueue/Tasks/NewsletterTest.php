<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class NewsletterTest extends \MailPoetTest {
  /** @var NewsletterTask */
  private $newsletterTask;

  /** @var Subscriber */
  private $subscriber;

  /** @var Newsletter */
  private $newsletter;

  /** @var Newsletter */
  private $parentNewsletter;

  /** @var SendingTask */
  private $queue;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function _before() {
    parent::_before();
    $this->newsletterTask = new NewsletterTask();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'test@example.com';
    $this->subscriber->firstName = 'John';
    $this->subscriber->lastName = 'Doe';
    $this->subscriber->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->status = Newsletter::STATUS_ACTIVE;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->preheader = '';
    $this->newsletter->save();
    $this->parentNewsletter = Newsletter::create();
    $this->parentNewsletter->type = Newsletter::TYPE_STANDARD;
    $this->parentNewsletter->status = Newsletter::STATUS_ACTIVE;
    $this->parentNewsletter->subject = 'parent newsletter';
    $this->parentNewsletter->body = 'parent body';
    $this->parentNewsletter->preheader = '';
    $this->parentNewsletter->save();
    $this->queue = SendingTask::create();
    $this->queue->newsletter_id = $this->newsletter->id;
    $this->queue->save();
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function testItConstructs() {
    expect($this->newsletterTask->trackingEnabled)->true();
  }

  public function testItDoesNotGetNewsletterWhenStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_DRAFT;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->false();

    // active or sending statuses return newsletter
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');

    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SENDING;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  public function testItDoesNotGetDeletedNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->set_expr('deleted_at', 'NOW()');
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->false();
  }

  public function testItDoesNotGetNewsletterWhenParentNewsletterStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = Newsletter::STATUS_DRAFT;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->false();

    // active or sending statuses return newsletter
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = Newsletter::STATUS_ACTIVE;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');

    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->status = Newsletter::STATUS_SENDING;
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  public function testItDoesNotGetDeletedNewsletterWhenParentNewsletterIsDeleted() {
    $parentNewsletter = $this->parentNewsletter;
    $parentNewsletter->set_expr('deleted_at', 'NOW()');
    $parentNewsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $parentNewsletter->id;
    $newsletter->save();
    expect($this->newsletterTask->getNewsletterFromQueue($this->queue))->false();
  }

  public function testItReturnsNewsletterObjectWhenRenderedNewsletterBodyExistsInTheQueue() {
    $queue = $this->queue;
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
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->contains('[mailpoet_click_data]-' . $link->hash);
    expect($renderedNewsletter['html'])
      ->contains('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->internalType('array');
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof Newsletter)->true();
  }

  public function testItDoesNotHashLinksAndInsertTrackingCodeWhenTrackingIsDisabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletterTask = new NewsletterTask($wp);
    $newsletterTask->trackingEnabled = false;
    $newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($link)->false();
    /** @var SendingQueue $updatedQueue */
    $updatedQueue = SendingQueue::findOne($this->queue->id);
    $updatedQueue = SendingTask::createFromQueue($updatedQueue);
    $renderedNewsletter = $updatedQueue->getNewsletterRenderedBody();
    expect($renderedNewsletter['html'])
      ->notContains('[mailpoet_click_data]');
    expect($renderedNewsletter['html'])
      ->notContains('[mailpoet_open_data]');

    $hookName = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hookName))->true();
    expect(WPHooksHelper::getFilterApplied($hookName)[0])->internalType('array');
    expect(WPHooksHelper::getFilterApplied($hookName)[1] instanceof Newsletter)->true();
  }

  public function testItReturnsFalseAndDeletesNewsletterWhenPostNotificationContainsNoPosts() {
    $newsletter = $this->newsletter;

    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parentId = $newsletter->id;
    // replace post id data tag with something else
    $newsletter->body = str_replace('data-post-id', 'id', $newsletter->getBodyString());
    $newsletter->save();
    // returned result is false
    $result = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    expect($result)->false();
    // newsletter is deleted
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter)->false();
  }

  public function testItSavesNewsletterPosts() {
    $this->newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $this->newsletter->parentId = $this->newsletter->id;
    $postsTask = $this->make(PostsTask::class, [
      'getAlcPostsCount' => 1,
      'loggerFactory' => $this->loggerFactory,
    ]);
    $newsletterTask = new NewsletterTask(new WPFunctions, $postsTask);
    $result = $newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    $newsletterPost = NewsletterPost::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($result)->notEquals(false);
    expect($newsletterPost->postId)->equals('10');
  }

  public function testItUpdatesStatusAndSetsSentAtDateOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newsletter;
    $queue = new \stdClass();
    $queue->processedAt = date('Y-m-d H:i:s');

    // newsletter type is 'standard'
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updatedNewsletter->sentAt)->equals($queue->processedAt);

    // newsletter type is 'notification history'
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    expect($updatedNewsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updatedNewsletter->sentAt)->equals($queue->processedAt);

    // all other newsletter types
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletterTask->markNewsletterAsSent($newsletter, $queue);
    $updatedNewsletter = Newsletter::findOne($newsletter->id);
    expect($updatedNewsletter->status)->notEquals(Newsletter::STATUS_SENT);
  }

  public function testItDoesNotRenderSubscriberShortcodeInSubjectWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = 'Newsletter for [subscriber:firstname] [date:dordinal]';
    $queue = $this->queue;
    $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    $wp = new WPFunctions();
    expect($queue->newsletterRenderedSubject)
      ->contains(date_i18n('jS', $wp->currentTime('timestamp')));
  }

  public function testItUsesADefaultSubjectIfRenderedSubjectIsEmptyWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = '  [custom_shortcode:should_render_empty]  ';
    $queue = $this->queue;
    $newsletter = $this->newsletterTask->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    expect($queue->newsletterRenderedSubject)
      ->equals('No subject');
  }

  public function testItUsesRenderedNewsletterBodyAndSubjectFromQueueObjectWhenPreparingNewsletterForSending() {
    $queue = $this->queue;
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
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    expect($result['subject'])->equals('queue subject');
    expect($result['body']['html'])->equals('queue HTML body');
    expect($result['body']['text'])->equals('queue TEXT body');
  }

  public function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletter = $this->newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    $result = $this->newsletterTask->prepareNewsletterForSending(
      $newsletter,
      $this->subscriber,
      $this->queue
    );
    expect($result['subject'])->contains($this->subscriber->firstName);
    expect($result['body']['html'])
      ->contains(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->contains(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItDoesNotReplaceSubscriberDataInLinksWhenTrackingIsNotEnabled() {
    $newsletterTask = $this->newsletterTask;
    $newsletterTask->trackingEnabled = false;
    $newsletter = $newsletterTask->preProcessNewsletter($this->newsletter, $this->queue);
    $result = $newsletterTask->prepareNewsletterForSending(
      $newsletter,
      $this->subscriber,
      $this->queue
    );
    expect($result['body']['html'])
      ->notContains(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->notContains(Router::NAME . '&endpoint=track&action=click&data=');
  }

  public function testItGetsSegments() {
    for ($i = 1; $i <= 3; $i++) {
      $newsletterSegment = NewsletterSegment::create();
      $newsletterSegment->newsletterId = $this->newsletter->id;
      $newsletterSegment->segmentId = $i;
      $newsletterSegment->save();
    }
    expect($this->newsletterTask->getNewsletterSegments($this->newsletter))->equals(
      [1,2,3]
    );
  }

  public function testItLogsErrorWhenQueueWithCannotBeSaved() {
    $queue = $this->queue;
    $queue->nonExistentColumn = true; // this will trigger save error
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $queue);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();
      expect($mailerLog['error']['operation'])->equals('queue_save');
      expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
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
      expect($mailerLog['error']['operation'])->equals('queue_save');
      expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
    }
  }

  public function testItLogsErrorWhenNewlyRenderedNewsletterBodyIsInvalid() {
    $queue = $this->queue;
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
    $queueMock->id = $queue->id;
    $queueMock->taskId = $queue->taskId;

    $sendingQueue = ORM::forTable(SendingQueue::$_table)->findOne($queue->id);
    $sendingQueue->set('newsletter_rendered_body', 'a:2:{s:4:"html"');
    $sendingQueue->save();
    try {
      $this->newsletterTask->preProcessNewsletter($this->newsletter, $queueMock);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailerLog = MailerLog::getMailerLog();
      expect($mailerLog['error']['operation'])->equals('queue_save');
      expect($mailerLog['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
    }
  }

  public function testItPreProcessesNewsletterWhenNewlyRenderedNewsletterBodyIsValid() {
    $queue = $this->queue;
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
    $queueMock->id = $queue->id;
    $queueMock->taskId = $queue->taskId;

    // properly serialized object
    $sendingQueue = ORM::forTable(SendingQueue::$_table)->findOne($queue->id);
    $sendingQueue->set('newsletter_rendered_body', 'a:2:{s:4:"html";s:4:"test";s:4:"text";s:4:"test";}');
    $sendingQueue->save();

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
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
  }
}
