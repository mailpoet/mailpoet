<?php declare(strict_types = 1);

namespace MailPoet\Test\Migrations\Db;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrations\Db\Migration_20260427_100000;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260427_100000_Test extends \MailPoetTest {
  /** @var Migration_20260427_100000 */
  private $migration;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20260427_100000($this->diContainer);
  }

  public function testItAddsNullableLastConfirmationEmailSentAtColumn(): void {
    $this->migration->run();
    $this->migration->run();

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $column = $this->entityManager->getConnection()->executeQuery(
      "SHOW COLUMNS FROM {$subscribersTable} LIKE 'last_confirmation_email_sent_at'"
    )->fetchAssociative();
    $this->assertIsArray($column);

    verify($column['Null'])->equals('YES');
    verify($column['Default'])->null();
  }

  public function testSubscriberEntityPersistsLastConfirmationEmailSentAt(): void {
    $sentAt = new \DateTimeImmutable('2026-04-27 10:00:00');
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('confirmation-timestamp@example.com');
    $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $subscriber->setLastConfirmationEmailSentAt($sentAt);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    $this->entityManager->clear();

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertInstanceOf(\DateTimeInterface::class, $subscriber->getLastConfirmationEmailSentAt());
    verify($subscriber->getLastConfirmationEmailSentAt()->format('Y-m-d H:i:s'))->equals('2026-04-27 10:00:00');
  }
}
