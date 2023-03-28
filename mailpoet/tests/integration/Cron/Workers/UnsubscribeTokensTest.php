<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;

class UnsubscribeTokensTest extends \MailPoetTest {

  private $subscriberWithToken;
  private $newsletterWithToken;
  private $subscriberWithoutToken;
  private $newsletterWithoutToken;

  public function _before() {
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
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->subscriberWithToken = Subscriber::findOne($this->subscriberWithToken->id);
    $this->subscriberWithoutToken = Subscriber::findOne($this->subscriberWithoutToken->id);
    expect($this->subscriberWithToken->unsubscribe_token)->equals('aaabbbcccdddeee');
    expect(strlen($this->subscriberWithoutToken->unsubscribe_token))->equals(15);
  }

  public function testItAddsTokensToNewsletters() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->newsletterWithToken = Newsletter::findOne($this->newsletterWithToken->id);
    $this->newsletterWithoutToken = Newsletter::findOne($this->newsletterWithoutToken->id);
    $this->assertInstanceOf(Newsletter::class, $this->newsletterWithToken);
    $this->assertInstanceOf(Newsletter::class, $this->newsletterWithoutToken);
    expect($this->newsletterWithToken->unsubscribeToken)->equals('aaabbbcccdddeee');
    expect(strlen($this->newsletterWithoutToken->unsubscribeToken))->equals(15);
  }
}
