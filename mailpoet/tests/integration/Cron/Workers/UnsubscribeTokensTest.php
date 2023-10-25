<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class UnsubscribeTokensTest extends \MailPoetTest {

  /** @var SubscriberEntity */
  private $subscriberWithToken;
  private $newsletterWithToken;

  /** @var SubscriberEntity */
  private $subscriberWithoutToken;
  private $newsletterWithoutToken;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->subscriberWithToken = (new SubscriberFactory())
      ->withEmail('subscriber1@test.com')
      ->withUnsubscribeToken('aaabbbcccdddeee')
      ->create();

    $this->subscriberWithoutToken = (new SubscriberFactory())
      ->withEmail('subscriber2@test.com')
      ->create();

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
    verify($this->subscriberWithoutToken->getUnsubscribeToken())->null();
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $subscriberWithToken = $this->subscribersRepository->findOneById($this->subscriberWithToken->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberWithToken);
    $subscriberWithoutToken = $this->subscribersRepository->findOneById($this->subscriberWithoutToken->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberWithoutToken);
    verify($subscriberWithToken->getUnsubscribeToken())->equals('aaabbbcccdddeee');
    verify(strlen($subscriberWithoutToken->getUnsubscribeToken() ?? ''))->equals(15);
  }

  public function testItAddsTokensToNewsletters() {
    $worker = new UnsubscribeTokens();
    $worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->newsletterWithToken = Newsletter::findOne($this->newsletterWithToken->id);
    $this->newsletterWithoutToken = Newsletter::findOne($this->newsletterWithoutToken->id);
    $this->assertInstanceOf(Newsletter::class, $this->newsletterWithToken);
    $this->assertInstanceOf(Newsletter::class, $this->newsletterWithoutToken);
    verify($this->newsletterWithToken->unsubscribeToken)->equals('aaabbbcccdddeee');
    verify(strlen($this->newsletterWithoutToken->unsubscribeToken))->equals(15);
  }
}
