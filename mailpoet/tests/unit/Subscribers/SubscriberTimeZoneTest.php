<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers;

use MailPoet\Entities\SubscriberEntity;

class SubscriberTimeZoneTest extends \MailPoetUnitTest {
  public function testItAcceptsValidIanaTimeZones(): void {
    verify(SubscriberEntity::sanitizeTimeZone('Europe/Bratislava'))->equals('Europe/Bratislava');
    verify(SubscriberEntity::isValidTimeZone('America/New_York'))->true();
  }

  public function testItRejectsInvalidTimeZones(): void {
    verify(SubscriberEntity::sanitizeTimeZone('Not/AZone'))->null();
    verify(SubscriberEntity::sanitizeTimeZone(''))->null();
    verify(SubscriberEntity::isValidTimeZone(null))->false();
  }

  public function testItRejectsNonStringInputWithoutThrowing(): void {
    verify(SubscriberEntity::sanitizeTimeZone(['Europe/Prague']))->null();
    verify(SubscriberEntity::sanitizeTimeZone(123))->null();
    verify(SubscriberEntity::sanitizeTimeZone(true))->null();
    verify(SubscriberEntity::sanitizeTimeZone(null))->null();
    verify(SubscriberEntity::isValidTimeZone(['Europe/Prague']))->false();
    verify(SubscriberEntity::isValidTimeZone(123))->false();
  }
}
