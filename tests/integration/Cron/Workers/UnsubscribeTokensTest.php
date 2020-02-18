<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class UnsubscribeTokensTest extends \MailPoetTest {

  private $subscriberWithToken;
  private $newsletterWithToken;
  private $subscriberWithoutToken;
  private $newsletterWithoutToken;

  public function _before() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    parent::_before();
    $this->subscriberWithToken = Subscriber::createOrUpdate(['email' => 'subscriber1@test.com']);
    $this->subscriberWithToken->set('unsubscribe_token', 'aaabbbcccdddeee');
    $this->subscriberWithToken->save();

    $this->subscriberWithoutToken = Subscriber::createOrUpdate(['email' => 'subscriber2@test.com']);
    $this->subscriberWithoutToken->set('unsubscribe_token', null);
    $this->subscriberWithoutToken->save();

    $this->newsletterWithToken = Newsletter::createOrUpdate([
      'subject' => 'My Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $this->newsletterWithToken->set('unsubscribe_token', 'aaabbbcccdddeee');
    $this->newsletterWithToken->save();

    $this->newsletterWithoutToken = Newsletter::createOrUpdate([
      'subject' => 'My Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $this->newsletterWithoutToken->set('unsubscribe_token', null);
    $this->newsletterWithoutToken->save();
  }

  public function testItAddsTokensToSubscribers() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate(), microtime(true));
    $this->subscriberWithToken = Subscriber::findOne($this->subscriberWithToken->id);
    $this->subscriberWithoutToken = Subscriber::findOne($this->subscriberWithoutToken->id);
    expect($this->subscriberWithToken->unsubscribe_token)->equals('aaabbbcccdddeee');
    expect(strlen($this->subscriberWithoutToken->unsubscribe_token))->equals(15);
  }

  public function testItAddsTokensToNewsletters() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate(), microtime(true));
    $this->newsletterWithToken = Newsletter::findOne($this->newsletterWithToken->id);
    $this->newsletterWithoutToken = Newsletter::findOne($this->newsletterWithoutToken->id);
    expect($this->newsletterWithToken->unsubscribeToken)->equals('aaabbbcccdddeee');
    expect(strlen($this->newsletterWithoutToken->unsubscribeToken))->equals(15);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
  }
}
