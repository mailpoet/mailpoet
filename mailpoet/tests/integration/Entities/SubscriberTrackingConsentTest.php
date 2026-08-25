<?php declare(strict_types = 1);

namespace MailPoet\Entities;

use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

/**
 * A subscriber row holding an out-of-range tracking_consent value must never take the
 * whole plugin down. Validation lives in the setter, so a bad value that is already
 * stored stays harmless on read and on an unrelated flush. STOMAIL-8365.
 */
class SubscriberTrackingConsentTest extends \MailPoetTest {
  public function testSetTrackingConsentRejectsAnInvalidValue(): void {
    $subscriber = new SubscriberEntity();
    $this->expectException(\InvalidArgumentException::class);
    $subscriber->setTrackingConsent('not-a-real-value');
  }

  public function testSetTrackingConsentAcceptsEachValidValue(): void {
    $subscriber = new SubscriberEntity();

    $validValues = [
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN,
    ];

    foreach ($validValues as $value) {
      $subscriber->setTrackingConsent($value);
      verify($subscriber->getTrackingConsent())->equals($value);
    }
  }

  public function testABadStoredValueDoesNotThrowOnAnUnrelatedFlush(): void {
    // The support case: a row already holds an out-of-range value. Loading it and
    // flushing something unrelated must not throw. Only a caller trying to WRITE a
    // bad value should.
    $subscriber = (new SubscriberFactory())->withEmail('bad-stored-consent@example.com')->create();
    $table = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE `{$table}` SET tracking_consent = 'legacy-junk' WHERE id = ?",
      [$subscriber->getId()]
    );
    $this->entityManager->clear();

    $reloaded = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $reloaded);
    verify($reloaded->getTrackingConsent())->equals('legacy-junk');

    $reloaded->setFirstName('Touched');
    $this->entityManager->flush();

    verify($reloaded->getFirstName())->equals('Touched');
  }

  public function testAnEmptyStoredValueDoesNotThrowOnAnUnrelatedFlush(): void {
    // The exact shape the customer's database was in.
    $subscriber = (new SubscriberFactory())->withEmail('empty-stored-consent@example.com')->create();
    $table = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE `{$table}` SET tracking_consent = '' WHERE id = ?",
      [$subscriber->getId()]
    );
    $this->entityManager->clear();

    $reloaded = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $reloaded);
    $reloaded->setLastName('Touched');
    $this->entityManager->flush();

    verify($reloaded->getLastName())->equals('Touched');
  }
}
