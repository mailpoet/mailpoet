<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriberLinkTokensTest extends \MailPoetTest {
  /** @var SubscriberLinkTokens */
  private $worker;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscriberLinkTokens::class);
  }

  public function testItCanSetLinkTokensWhenFieldIsNull() {
    $linkToken = 'some link token';
    $subscriberWithLinkToken = (new SubscriberFactory())->withLinkToken($linkToken)->create();
    $subscriberWithoutLinkToken1 = (new SubscriberFactory())->create();
    $subscriberWithoutLinkToken2 = (new SubscriberFactory())->create();

    $this->assertNull($subscriberWithoutLinkToken1->getLinkToken());
    $this->assertNull($subscriberWithoutLinkToken2->getLinkToken());

    $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    $this->entityManager->refresh($subscriberWithLinkToken);
    $this->entityManager->refresh($subscriberWithoutLinkToken1);
    $this->entityManager->refresh($subscriberWithoutLinkToken2);

    $this->assertSame($linkToken, $subscriberWithLinkToken->getLinkToken());
    $token1 = (string)$subscriberWithoutLinkToken1->getLinkToken();
    $token2 = (string)$subscriberWithoutLinkToken2->getLinkToken();
    $this->assertSame(SubscriberEntity::LINK_TOKEN_LENGTH, strlen($token1));
    $this->assertSame(SubscriberEntity::LINK_TOKEN_LENGTH, strlen($token2));
    $this->assertNotSame($token1, $token2);
  }
}
