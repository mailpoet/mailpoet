<?php
namespace MailPoet\Test\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Newsletter;
use Codeception\Util\Fixtures;

class UnsubscribeTokensTest extends \MailPoetTest {

  private $subscriber_with_token;
  private $newsletter_with_token;
  private $subscriber_without_token;
  private $newsletter_without_token;

  function _before() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    parent::_before();
    $this->subscriber_with_token = Subscriber::createOrUpdate(['email' => 'subscriber1@test.com']);
    $this->subscriber_with_token->set('unsubscribe_token', 'aaabbbcccdddeee');
    $this->subscriber_with_token->save();

    $this->subscriber_without_token = Subscriber::createOrUpdate(['email' => 'subscriber2@test.com']);
    $this->subscriber_without_token->set('unsubscribe_token', null);
    $this->subscriber_without_token->save();

    $this->newsletter_with_token = Newsletter::createOrUpdate([
      'subject' => 'My Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $this->newsletter_with_token->set('unsubscribe_token', 'aaabbbcccdddeee');
    $this->newsletter_with_token->save();

    $this->newsletter_without_token = Newsletter::createOrUpdate([
      'subject' => 'My Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $this->newsletter_without_token->set('unsubscribe_token', null);
    $this->newsletter_without_token->save();
  }

  function testItAddsTokensToSubscribers() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate());
    $this->subscriber_with_token = Subscriber::findOne($this->subscriber_with_token->id);
    $this->subscriber_without_token = Subscriber::findOne($this->subscriber_without_token->id);
    expect($this->subscriber_with_token->unsubscribe_token)->equals('aaabbbcccdddeee');
    expect(strlen($this->subscriber_without_token->unsubscribe_token))->equals(15);
  }

  function testItAddsTokensToNewsletters() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate());
    $this->newsletter_with_token = Newsletter::findOne($this->newsletter_with_token->id);
    $this->newsletter_without_token = Newsletter::findOne($this->newsletter_without_token->id);
    expect($this->newsletter_with_token->unsubscribe_token)->equals('aaabbbcccdddeee');
    expect(strlen($this->newsletter_without_token->unsubscribe_token))->equals(15);
  }

  function testItSchedulesNextRunWhenFinished() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));

    $task = ScheduledTask::where('type', UnsubscribeTokens::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();

    expect($task)->isInstanceOf(ScheduledTask::class);
    expect($task->scheduled_at)->greaterThan(new Carbon());
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}
