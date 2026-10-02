<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriberLinkTokensTest extends \MailPoetTest {
  /** @var SubscriberLinkTokens */
  private $worker;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscriberLinkTokens::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    $this->settings->delete(SubscriberLinkTokens::LAST_UPGRADED_SUBSCRIBER_ID_SETTING);
  }

  public function _after() {
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    $this->settings->delete(SubscriberLinkTokens::LAST_UPGRADED_SUBSCRIBER_ID_SETTING);
    parent::_after();
  }

  public function testItCanSetLinkTokensWhenFieldIsNull() {
    $linkToken = 'some link token';
    $subscriberWithLinkToken = (new SubscriberFactory())->withLinkToken($linkToken)->create();
    $subscriberWithoutLinkToken1 = (new SubscriberFactory())->create();
    $subscriberWithoutLinkToken2 = (new SubscriberFactory())->create();

    $this->assertNull($subscriberWithoutLinkToken1->getLinkToken());
    $this->assertNull($subscriberWithoutLinkToken2->getLinkToken());

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

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

  public function testItUpgradesObsoleteTokensAndKeepsTheirObsoletePart() {
    $longToken = str_repeat('a', SubscriberEntity::LINK_TOKEN_LENGTH);
    $subscriberWithLongToken = (new SubscriberFactory())->withLinkToken($longToken)->create();
    $subscriberWithObsoleteToken1 = (new SubscriberFactory())->withLinkToken('abcdef')->create();
    $subscriberWithObsoleteToken2 = (new SubscriberFactory())->withLinkToken('012345')->create();

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->entityManager->refresh($subscriberWithLongToken);
    $this->entityManager->refresh($subscriberWithObsoleteToken1);
    $this->entityManager->refresh($subscriberWithObsoleteToken2);

    $upgradedToken1 = (string)$subscriberWithObsoleteToken1->getLinkToken();
    $upgradedToken2 = (string)$subscriberWithObsoleteToken2->getLinkToken();
    $this->assertSame($longToken, $subscriberWithLongToken->getLinkToken());
    $this->assertUpgradedToken('abcdef', $upgradedToken1);
    $this->assertUpgradedToken('012345', $upgradedToken2);
    $this->assertNotSame(substr($upgradedToken1, 7), substr($upgradedToken2, 7));
  }

  public function testItStartsGracePeriodForObsoleteTokens() {
    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->assertGreaterThan(time(), (int)$this->settings->get(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING));
  }

  public function testItContinuesAfterLastUpgradedSubscriber() {
    $upgradedSubscriber = (new SubscriberFactory())->withLinkToken('abcdef')->create();
    $nextSubscriber = (new SubscriberFactory())->withLinkToken('012345')->create();
    $this->settings->set(SubscriberLinkTokens::LAST_UPGRADED_SUBSCRIBER_ID_SETTING, $upgradedSubscriber->getId());

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->entityManager->refresh($upgradedSubscriber);
    $this->entityManager->refresh($nextSubscriber);
    $this->assertSame('abcdef', $upgradedSubscriber->getLinkToken());
    $this->assertUpgradedToken('012345', (string)$nextSubscriber->getLinkToken());
  }

  public function testItUpgradesObsoleteTokensOfSubscribersAddedAfterPreviousRun() {
    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));
    $subscriber = (new SubscriberFactory())->withLinkToken('abcdef')->create();

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->entityManager->refresh($subscriber);
    $this->assertUpgradedToken('abcdef', (string)$subscriber->getLinkToken());
  }

  public function testItRemembersLastUpgradedSubscriber() {
    (new SubscriberFactory())->withLinkToken('abcdef')->create();
    $lastSubscriber = (new SubscriberFactory())->withLinkToken('012345')->create();

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->assertSame($lastSubscriber->getId(), (int)$this->settings->get(SubscriberLinkTokens::LAST_UPGRADED_SUBSCRIBER_ID_SETTING));
  }

  public function testItUpgradesObsoleteTokensAcrossBatches() {
    $lastIdInFirstBatch = SubscriberLinkTokens::BATCH_SIZE;
    $idInThirdBatch = 2 * SubscriberLinkTokens::BATCH_SIZE + 5;
    $this->createSubscriberWithId($lastIdInFirstBatch, 'abcdef');
    $this->createSubscriberWithId($lastIdInFirstBatch + 1, '012345');
    $this->createSubscriberWithId($idInThirdBatch, '6789ab');

    $this->worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->assertUpgradedToken('abcdef', $this->getLinkToken($lastIdInFirstBatch));
    $this->assertUpgradedToken('012345', $this->getLinkToken($lastIdInFirstBatch + 1));
    $this->assertUpgradedToken('6789ab', $this->getLinkToken($idInThirdBatch));
    $this->assertSame($idInThirdBatch, (int)$this->settings->get(SubscriberLinkTokens::LAST_UPGRADED_SUBSCRIBER_ID_SETTING));
  }

  private function assertUpgradedToken(string $obsoleteToken, string $token): void {
    $this->assertSame(SubscriberEntity::LINK_TOKEN_LENGTH, strlen($token));
    $this->assertSame($obsoleteToken . LinkTokens::UPGRADED_TOKEN_SEPARATOR, substr($token, 0, 7));
    $this->assertTrue(ctype_xdigit(substr($token, 7)));
  }

  private function createSubscriberWithId(int $id, string $linkToken): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->connection->executeStatement(
      "INSERT INTO {$subscribersTable} (id, email, status, link_token, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
      [$id, "subscriber{$id}@example.com", SubscriberEntity::STATUS_SUBSCRIBED, $linkToken]
    );
  }

  private function getLinkToken(int $id): string {
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $id);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return (string)$subscriber->getLinkToken();
  }

  private function createRunningTask(): ScheduledTaskEntity {
    return (new ScheduledTaskFactory())->create(SubscriberLinkTokens::TASK_TYPE, null);
  }
}
