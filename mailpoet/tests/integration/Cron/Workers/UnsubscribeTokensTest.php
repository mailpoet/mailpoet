<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class UnsubscribeTokensTest extends \MailPoetTest {

  /** @var SubscriberEntity */
  private $subscriberWithToken;

  /** @var NewsletterEntity */
  private $newsletterWithToken;

  /** @var SubscriberEntity */
  private $subscriberWithoutToken;

  /** @var NewsletterEntity */
  private $newsletterWithoutToken;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var UnsubscribeTokens */
  private $worker;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->worker = $this->diContainer->get(UnsubscribeTokens::class);

    $this->subscriberWithToken = (new SubscriberFactory())
      ->withEmail('subscriber1@test.com')
      ->withUnsubscribeToken('aaabbbcccdddeee')
      ->create();

    $this->subscriberWithoutToken = (new SubscriberFactory())
      ->withEmail('subscriber2@test.com')
      ->create();

    $this->newsletterWithToken = (new NewsletterFactory())
      ->withSubject('My Newsletter')
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withUnsubscribeToken('aaabbbcccdddeee')
      ->create();

    $this->newsletterWithoutToken = (new NewsletterFactory())
      ->withSubject('My Newsletter')
      ->withBody(Fixtures::get('newsletter_body_template'))
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->create();
  }

  public function testItAddsTokensToSubscribers() {
    verify($this->subscriberWithoutToken->getUnsubscribeToken())->null();
    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $subscriberWithToken = $this->subscribersRepository->findOneById($this->subscriberWithToken->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberWithToken);
    $subscriberWithoutToken = $this->subscribersRepository->findOneById($this->subscriberWithoutToken->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberWithoutToken);
    verify($subscriberWithToken->getUnsubscribeToken())->equals('aaabbbcccdddeee');
    verify(strlen($subscriberWithoutToken->getUnsubscribeToken() ?? ''))->equals(15);
  }

  public function testItAddsTokensToNewsletters() {
    verify($this->newsletterWithoutToken->getUnsubscribeToken())->null();
    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $newsletterWithToken = $this->newslettersRepository->findOneById($this->newsletterWithToken->getId());
    $newsletterWithoutToken = $this->newslettersRepository->findOneById($this->newsletterWithoutToken->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterWithToken);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterWithoutToken);
    verify($newsletterWithToken->getUnsubscribeToken())->equals('aaabbbcccdddeee');
    verify(strlen($newsletterWithoutToken->getUnsubscribeToken() ?? ''))->equals(15);
  }
}
