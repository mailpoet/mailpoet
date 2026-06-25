<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Fixtures;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
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

  /** @var UnsubscribeTokens */
  private $worker;

  public function _before() {
    parent::_before();
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
    // The worker writes via a direct SQL UPDATE, so refresh the managed entities to read
    // the persisted values rather than the stale ones cached in the identity map.
    $this->entityManager->refresh($this->subscriberWithToken);
    $this->entityManager->refresh($this->subscriberWithoutToken);
    verify($this->subscriberWithToken->getUnsubscribeToken())->equals('aaabbbcccdddeee');
    verify(strlen($this->subscriberWithoutToken->getUnsubscribeToken() ?? ''))->equals(15);
  }

  public function testItAddsTokensToNewsletters() {
    verify($this->newsletterWithoutToken->getUnsubscribeToken())->null();
    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));
    $this->entityManager->refresh($this->newsletterWithToken);
    $this->entityManager->refresh($this->newsletterWithoutToken);
    verify($this->newsletterWithToken->getUnsubscribeToken())->equals('aaabbbcccdddeee');
    verify(strlen($this->newsletterWithoutToken->getUnsubscribeToken() ?? ''))->equals(15);
  }
}
