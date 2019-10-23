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
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Router;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterTest extends \MailPoetTest {
  /** @var NewsletterTask */
  private $newsletter_task;

  /** @var Subscriber */
  private $subscriber;

  /** @var Newsletter */
  private $newsletter;

  /** @var Newsletter */
  private $parent_newsletter;

  /** @var SendingTask */
  private $queue;

  /** @var LoggerFactory */
  private $logger_factory;

  function _before() {
    parent::_before();
    $this->newsletter_task = new NewsletterTask();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'test@example.com';
    $this->subscriber->first_name = 'John';
    $this->subscriber->last_name = 'Doe';
    $this->subscriber->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->status = Newsletter::STATUS_ACTIVE;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->preheader = '';
    $this->newsletter->save();
    $this->parent_newsletter = Newsletter::create();
    $this->parent_newsletter->type = Newsletter::TYPE_STANDARD;
    $this->parent_newsletter->status = Newsletter::STATUS_ACTIVE;
    $this->parent_newsletter->subject = 'parent newsletter';
    $this->parent_newsletter->body = 'parent body';
    $this->parent_newsletter->preheader = '';
    $this->parent_newsletter->save();
    $this->queue = SendingTask::create();
    $this->queue->newsletter_id = $this->newsletter->id;
    $this->queue->save();
    $this->logger_factory = LoggerFactory::getInstance();
  }

  function testItConstructs() {
    expect($this->newsletter_task->tracking_enabled)->true();
  }

  function testItDoesNotGetNewsletterWhenStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_DRAFT;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->false();

    // active or sending statuses return newsletter
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');

    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SENDING;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  function testItDoesNotGetDeletedNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->set_expr('deleted_at', 'NOW()');
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->false();
  }

  function testItDoesNotGetNewsletterWhenParentNewsletterStatusIsNotActiveOrSending() {
    // draft or any other status return false
    $parent_newsletter = $this->parent_newsletter;
    $parent_newsletter->status = Newsletter::STATUS_DRAFT;
    $parent_newsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $parent_newsletter->id;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->false();

    // active or sending statuses return newsletter
    $parent_newsletter = $this->parent_newsletter;
    $parent_newsletter->status = Newsletter::STATUS_ACTIVE;
    $parent_newsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $parent_newsletter->id;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');

    $parent_newsletter = $this->parent_newsletter;
    $parent_newsletter->status = Newsletter::STATUS_SENDING;
    $parent_newsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $parent_newsletter->id;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->isInstanceOf('Mailpoet\Models\Newsletter');
  }

  function testItDoesNotGetDeletedNewsletterWhenParentNewsletterIsDeleted() {
    $parent_newsletter = $this->parent_newsletter;
    $parent_newsletter->set_expr('deleted_at', 'NOW()');
    $parent_newsletter->save();
    $newsletter = $this->newsletter;
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $parent_newsletter->id;
    $newsletter->save();
    expect($this->newsletter_task->getNewsletterFromQueue($this->queue))->false();
  }

  function testItReturnsNewsletterObjectWhenRenderedNewsletterBodyExistsInTheQueue() {
    $queue = $this->queue;
    $queue->newsletter_rendered_body = ['html' => 'test', 'text' => 'test'];
    $result = $this->newsletter_task->preProcessNewsletter($this->newsletter, $queue);
    expect($result instanceof \MailPoet\Models\Newsletter)->true();
  }

  function testItHashesLinksAndInsertsTrackingImageWhenTrackingIsEnabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletter_task = new NewsletterTask($wp);
    $newsletter_task->tracking_enabled = true;
    $newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    $rendered_newsletter = $updated_queue->getNewsletterRenderedBody();
    expect($rendered_newsletter['html'])
      ->contains('[mailpoet_click_data]-' . $link->hash);
    expect($rendered_newsletter['html'])
      ->contains('[mailpoet_open_data]');

    $hook_name = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hook_name))->true();
    expect(WPHooksHelper::getFilterApplied($hook_name)[0])->internalType('array');
    expect(WPHooksHelper::getFilterApplied($hook_name)[1] instanceof Newsletter)->true();
  }

  function testItDoesNotHashLinksAndInsertTrackingCodeWhenTrackingIsDisabled() {
    $wp = Stub::make(new WPFunctions, [
      'applyFilters' => asCallable([WPHooksHelper::class, 'applyFilters']),
    ]);
    $newsletter_task = new NewsletterTask($wp);
    $newsletter_task->tracking_enabled = false;
    $newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($link)->false();
    $updated_queue = SendingTask::createFromQueue(SendingQueue::findOne($this->queue->id));
    $rendered_newsletter = $updated_queue->getNewsletterRenderedBody();
    expect($rendered_newsletter['html'])
      ->notContains('[mailpoet_click_data]');
    expect($rendered_newsletter['html'])
      ->notContains('[mailpoet_open_data]');

    $hook_name = 'mailpoet_sending_newsletter_render_after';
    expect(WPHooksHelper::isFilterApplied($hook_name))->true();
    expect(WPHooksHelper::getFilterApplied($hook_name)[0])->internalType('array');
    expect(WPHooksHelper::getFilterApplied($hook_name)[1] instanceof Newsletter)->true();
  }

  function testItReturnsFalseAndDeletesNewsletterWhenPostNotificationContainsNoPosts() {
    $newsletter = $this->newsletter;

    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->parent_id = $newsletter->id;
    // replace post id data tag with something else
    $newsletter->body = str_replace('data-post-id', 'id', $newsletter->body);
    $newsletter->save();
    // returned result is false
    $result = $this->newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    expect($result)->false();
    // newsletter is deleted
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter)->false();
  }

  function testItSavesNewsletterPosts() {
    $this->newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $this->newsletter->parent_id = $this->newsletter->id;
    $posts_task = $this->make(PostsTask::class, [
      'getAlcPostsCount' => 1,
      'logger_factory' => $this->logger_factory,
    ]);
    $newsletter_task = new NewsletterTask(new WPFunctions, $posts_task);
    $result = $newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    $newsletter_post = NewsletterPost::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($result)->notEquals(false);
    expect($newsletter_post->post_id)->equals('10');
  }

  function testItUpdatesStatusAndSetsSentAtDateOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newsletter;
    $queue = new \stdClass();
    $queue->processed_at = date('Y-m-d H:i:s');

    // newsletter type is 'standard'
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter, $queue);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updated_newsletter->sent_at)->equals($queue->processed_at);

    // newsletter type is 'notification history'
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter, $queue);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);
    expect($updated_newsletter->sent_at)->equals($queue->processed_at);

    // all other newsletter types
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter, $queue);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->notEquals(Newsletter::STATUS_SENT);
  }

  function testItDoesNotRenderSubscriberShortcodeInSubjectWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = 'Newsletter for [subscriber:firstname] [date:dordinal]';
    $queue = $this->queue;
    $newsletter = $this->newsletter_task->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    $wp = new WPFunctions();
    expect($queue->newsletter_rendered_subject)
      ->contains(date_i18n('jS', $wp->currentTime('timestamp')));
  }

  function testItUsesADefaultSubjectIfRenderedSubjectIsEmptyWhenPreprocessingNewsletter() {
    $newsletter = $this->newsletter;
    $newsletter->subject = '  [custom_shortcode:should_render_empty]  ';
    $queue = $this->queue;
    $newsletter = $this->newsletter_task->preProcessNewsletter($newsletter, $queue);
    $queue = SendingTask::getByNewsletterId($newsletter->id);
    expect($queue->newsletter_rendered_subject)
      ->equals('No subject');
  }

  function testItUsesRenderedNewsletterBodyAndSubjectFromQueueObjectWhenPreparingNewsletterForSending() {
    $queue = $this->queue;
    $queue->newsletter_rendered_body = [
      'html' => 'queue HTML body',
      'text' => 'queue TEXT body',
    ];
    $queue->newsletter_rendered_subject = 'queue subject';
    $emoji = $this->make(
      Emoji::class,
      ['decodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })],
      $this
    );
    $newsletter_task = new NewsletterTask(null, null, null, $emoji);
    $result = $newsletter_task->prepareNewsletterForSending(
      $this->newsletter,
      $this->subscriber,
      $queue
    );
    expect($result['subject'])->equals('queue subject');
    expect($result['body']['html'])->equals('queue HTML body');
    expect($result['body']['text'])->equals('queue TEXT body');
  }

  function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletter = $this->newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    $result = $this->newsletter_task->prepareNewsletterForSending(
      $newsletter,
      $this->subscriber,
      $this->queue
    );
    expect($result['subject'])->contains($this->subscriber->first_name);
    expect($result['body']['html'])
      ->contains(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->contains(Router::NAME . '&endpoint=track&action=click&data=');
  }

  function testItDoesNotReplaceSubscriberDataInLinksWhenTrackingIsNotEnabled() {
    $newsletter_task = $this->newsletter_task;
    $newsletter_task->tracking_enabled = false;
    $newsletter = $newsletter_task->preProcessNewsletter($this->newsletter, $this->queue);
    $result = $newsletter_task->prepareNewsletterForSending(
      $newsletter,
      $this->subscriber,
      $this->queue
    );
    expect($result['body']['html'])
      ->notContains(Router::NAME . '&endpoint=track&action=click&data=');
    expect($result['body']['text'])
      ->notContains(Router::NAME . '&endpoint=track&action=click&data=');
  }

  function testItGetsSegments() {
    for ($i = 1; $i <= 3; $i++) {
      $newsletter_segment = NewsletterSegment::create();
      $newsletter_segment->newsletter_id = $this->newsletter->id;
      $newsletter_segment->segment_id = $i;
      $newsletter_segment->save();
    }
    expect($this->newsletter_task->getNewsletterSegments($this->newsletter))->equals(
      [1,2,3]
    );
  }

  function testItLogsErrorWhenQueueWithCannotBeSaved() {
    $queue = $this->queue;
    $queue->non_existent_column = true; // this will trigger save error
    try {
      $this->newsletter_task->preProcessNewsletter($this->newsletter, $queue);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailer_log = MailerLog::getMailerLog();
      expect($mailer_log['error']['operation'])->equals('queue_save');
      expect($mailer_log['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
    }
  }

  function testItLogsErrorWhenExistingRenderedNewsletterBodyIsInvalid() {
    $queue_mock = $this->createMock(SendingTask::class);
    $queue_mock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn('a:2:{s:4:"html"');
    $queue_mock
      ->expects($this->once())
      ->method('validate')
      ->willReturn(false);
    try {
      $this->newsletter_task->preProcessNewsletter($this->newsletter, $queue_mock);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailer_log = MailerLog::getMailerLog();
      expect($mailer_log['error']['operation'])->equals('queue_save');
      expect($mailer_log['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
    }
  }

  function testItLogsErrorWhenNewlyRenderedNewsletterBodyIsInvalid() {
    $queue = $this->queue;
    $queue_mock = $this->createMock(SendingTask::class);
    $queue_mock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $queue_mock
      ->expects($this->once())
      ->method('save');
    $queue_mock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $queue_mock->id = $queue->id;
    $queue_mock->task_id = $queue->task_id;

    $sending_queue = \ORM::forTable(SendingQueue::$_table)->findOne($queue->id);
    $sending_queue->set('newsletter_rendered_body', 'a:2:{s:4:"html"');
    $sending_queue->save();
    try {
      $this->newsletter_task->preProcessNewsletter($this->newsletter, $queue_mock);
      self::fail('Sending error exception was not thrown.');
    } catch (\Exception $e) {
      $mailer_log = MailerLog::getMailerLog();
      expect($mailer_log['error']['operation'])->equals('queue_save');
      expect($mailer_log['error']['error_message'])->equals('There was an error processing your newsletter during sending. If possible, please contact us and report this issue.');
    }
  }

  function testItPreProcessesNewsletterWhenNewlyRenderedNewsletterBodyIsValid() {
    $queue = $this->queue;
    $queue_mock = $this->createMock(SendingTask::class);
    $queue_mock
      ->expects($this->any())
      ->method('__call')
      ->with('getNewsletterRenderedBody')
      ->willReturn(null);
    $queue_mock
      ->expects($this->once())
      ->method('save');
    $queue_mock
      ->expects($this->once())
      ->method('getErrors')
      ->willReturn([]);
    $queue_mock->id = $queue->id;
    $queue_mock->task_id = $queue->task_id;

    // properly serialized object
    $sending_queue = \ORM::forTable(SendingQueue::$_table)->findOne($queue->id);
    $sending_queue->set('newsletter_rendered_body', 'a:2:{s:4:"html";s:4:"test";s:4:"text";s:4:"test";}');
    $sending_queue->save();

    $emoji = $this->make(
      Emoji::class,
      ['encodeEmojisInBody' => Expected::once(function ($params) {
        return $params;
      })],
      $this
    );
    $newsletter_task = new NewsletterTask(null, null, null, $emoji);
    expect($newsletter_task->preProcessNewsletter($this->newsletter, $queue_mock))->equals($this->newsletter);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
  }
}
