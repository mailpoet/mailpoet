<?php
use Codeception\Util\Fixtures;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Newsletter as NewsletterTask;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterPost;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Router;

if(!defined('ABSPATH')) exit;

class NewsletterTaskTest extends MailPoetTest {
  function _before() {
    $this->newsletter_task = new NewsletterTask();
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'test@example.com';
    $this->subscriber->first_name = 'John';
    $this->subscriber->last_name = 'Doe';
    $this->subscriber->save();
    $this->newsletter = Newsletter::create();
    $this->newsletter->type = Newsletter::TYPE_STANDARD;
    $this->newsletter->subject = Fixtures::get('newsletter_subject_template');
    $this->newsletter->body = Fixtures::get('newsletter_body_template');
    $this->newsletter->save();
    $this->queue = SendingQueue::create();
    $this->queue->newsletter_id = $this->newsletter->id;
    $this->queue->save();
  }

  function testItConstructs() {
    expect($this->newsletter_task->tracking_enabled)->true();
  }

  function testItFailsToGetAndProcessNewsletterWhenNewsletterDoesNotExist() {
    $queue = $this->queue;
    $queue->newsletter_id = 0;
    expect($this->newsletter_task->getAndPreProcess($queue))->false();
  }

  function testItReturnsNewsletterObjectWhenRenderedNewssletterBodyExistsInTheQueue() {
    $queue = $this->queue;
    $queue->newsletter_rendered_body = true;
    $result = $this->newsletter_task->getAndPreProcess($queue);
    expect($result instanceof \MailPoet\Models\Newsletter)->true();
  }

  function testItHashesLinksAndInsertsTrackingImageWhenTrackingIsEnabled() {
    WPHooksHelper::interceptApplyFilters();
    $newsletter_task = $this->newsletter_task;
    $newsletter_task->tracking_enabled = true;
    $newsletter_task->getAndPreProcess($this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    $updated_queue = SendingQueue::findOne($this->queue->id);
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
    WPHooksHelper::interceptApplyFilters();
    $newsletter_task = $this->newsletter_task;
    $newsletter_task->tracking_enabled = false;
    $newsletter_task->getAndPreProcess($this->queue);
    $link = NewsletterLink::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($link)->false();
    $updated_queue = SendingQueue::findOne($this->queue->id);
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

  function testItReturnsFalseAndDeletesNewsletterWhenPostNotificationContainsNoPostsn() {
    $newsletter = $this->newsletter;

    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    // replace post id data tag with something else
    $newsletter->body = str_replace('data-post-id', 'id', $newsletter->body);
    $newsletter->save();
    // returned result is false
    $result = $this->newsletter_task->getAndPreProcess($this->queue);
    expect($result)->false();
    // newsletter is deleted
    $newsletter = Newsletter::findOne($newsletter->id);
    expect($newsletter)->false();
  }

  function testItSavesNewsletterPosts() {
    $result = $this->newsletter_task->getAndPreProcess($this->queue);
    $newsletter_post = NewsletterPost::where('newsletter_id', $this->newsletter->id)
      ->findOne();
    expect($result)->notEquals(false);
    expect($newsletter_post->post_id)->equals('10');
  }

  function testItUpdatesStatusToSentOnlyForStandardAndPostNotificationNewsletters() {
    $newsletter = $this->newsletter;

    // newsletter type is 'standard'
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // newsletter type is 'notification history'
    $newsletter->type = Newsletter::TYPE_NOTIFICATION_HISTORY;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->equals(Newsletter::STATUS_SENT);

    // all other newsletter types
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = 'not_sent';
    $newsletter->save();
    $this->newsletter_task->markNewsletterAsSent($newsletter);
    $updated_newsletter = Newsletter::findOne($newsletter->id);
    expect($updated_newsletter->status)->notEquals(Newsletter::STATUS_SENT);
  }

  function testItRendersShortcodesAndReplacesSubscriberDataInLinks() {
    $newsletter = $this->newsletter_task->getAndPreProcess($this->queue);
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
    $newsletter = $newsletter_task->getAndPreProcess($this->queue);
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
    for($i = 1; $i<=3; $i++) {
      $newsletter_segment = NewsletterSegment::create();
      $newsletter_segment->newsletter_id = $this->newsletter->id;
      $newsletter_segment->segment_id = $i;
      $newsletter_segment->save();
    }
    expect($this->newsletter_task->getSegments($this->newsletter))->equals(
      array(1,2,3)
    );
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
  }
}