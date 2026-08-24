<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\ConflictException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscriberSaveControllerTest extends \MailPoetTest {
  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->saveController = $this->diContainer->get(SubscriberSaveController::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('collect_subscriber_timezones', ['enabled' => '1']);
  }

  public function testItCreatesNewSubscriber(): void {
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $segmentTwo = $this->segmentsRepository->createOrUpdate('Segment Two');
    $data = [
      'email' => 'first@test.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
        $segmentTwo->getId(),
      ],
      'created_at' => '2020-04-30 13:14:15',
      'confirmed_at' => '2020-04-31 13:14:15',
      'confirmed_ip' => '192.168.1.32',
      'subscribed_ip' => '192.168.1.16',
      'wp_user_id' => 7,
      'tags' => [
        'First',
        'Second',
      ],
    ];

    $subscriber = $this->saveController->save($data);
    verify($subscriber->getEmail())->equals($data['email']);
    verify($subscriber->getStatus())->equals($data['status']);
    verify($subscriber->getFirstName())->equals($data['first_name']);
    verify($subscriber->getLastName())->equals($data['last_name']);
    verify($subscriber->getCreatedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', $data['created_at']));
    verify($subscriber->getConfirmedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', $data['confirmed_at']));
    verify($subscriber->getConfirmedIp())->equals($data['confirmed_ip']);
    verify($subscriber->getSubscribedIp())->equals($data['subscribed_ip']);
    verify($subscriber->getWpUserId())->equals($data['wp_user_id']);
    verify($subscriber->getUnsubscribeToken())->notNull();
    verify($subscriber->getLinkToken())->notNull();
    verify($subscriber->getId())->notNull();
    verify($subscriber->getLastSubscribedAt())->notNull();
    verify($subscriber->getSegments())->arrayCount(2);
    verify($subscriber->getSubscriberSegments())->arrayCount(2);
    verify($subscriber->getSubscriberTags())->arrayCount(2);
  }

  public function testItStripsClientSuppliedTrackingConsentProofFields(): void {
    // A client (admin/API) can change the consent state but must not be able to
    // forge the proof-of-consent method/copy; those are stamped server-side only.
    $subscriber = $this->saveController->save([
      'email' => 'consent-forge@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'tracking_consent' => SubscriberEntity::TRACKING_CONSENT_GRANTED,
      'tracking_consent_method' => SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'tracking_consent_copy' => 'Forged copy the subscriber never saw',
    ]);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    // Forged method/copy dropped: method falls back to the server-side ADMIN default, copy stays null.
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_ADMIN);
    verify($subscriber->getTrackingConsentCopy())->null();
  }

  public function testItRejectsInvalidTrackingConsentValue(): void {
    // The rejection moved from flush-time validation to the entity setter, so the
    // exception type changed with it. Both extend \Exception, and the public API's
    // createOrUpdate() calls already catch broadly, so callers are unaffected.
    $this->expectException(\InvalidArgumentException::class);
    $this->saveController->save([
      'email' => 'consent-bogus@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'tracking_consent' => 'bogus',
    ]);
  }

  public function testItLetsAnAdminSetAnyOfTheThreeValidTrackingConsentValues(): void {
    foreach (SubscriberEntity::TRACKING_CONSENT_VALUES as $index => $value) {
      $subscriber = $this->saveController->save([
        'email' => "consent-valid-{$index}@test.com",
        'status' => SubscriberEntity::STATUS_SUBSCRIBED,
        'tracking_consent' => $value,
      ]);
      verify($subscriber->getTrackingConsent())->equals($value);
    }
  }

  public function testItSavesSubscriberTimeZoneWhenCollectionIsEnabled(): void {
    $subscriber = $this->saveController->save([
      'email' => 'timezone-enabled@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::TIME_ZONE_FIELD_NAME => 'Europe/Prague',
    ]);

    verify($subscriber->getTimeZone())->equals('Europe/Prague');
    verify($subscriber->getTimeZoneSource())->equals(SubscriberEntity::TIME_ZONE_SOURCE_FORM);
    verify($subscriber->getTimeZoneConfidence())->equals(SubscriberEntity::TIME_ZONE_CONFIDENCE_BROWSER);
    verify($subscriber->getTimeZoneUpdatedAt())->notNull();
  }

  public function testItDoesNotSaveSubscriberTimeZoneWhenCollectionIsDisabled(): void {
    $this->settings->set('collect_subscriber_timezones', ['enabled' => '']);

    $subscriber = $this->saveController->save([
      'email' => 'timezone-disabled@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::TIME_ZONE_FIELD_NAME => 'Europe/Prague',
    ]);

    verify($subscriber->getTimeZone())->null();
    verify($subscriber->getTimeZoneSource())->null();
    verify($subscriber->getTimeZoneConfidence())->null();
    verify($subscriber->getTimeZoneUpdatedAt())->null();
  }

  public function testItSavesManuallyEditedTimeZoneEvenWhenCollectionIsDisabled(): void {
    $this->settings->set('collect_subscriber_timezones', ['enabled' => '']);

    $subscriber = $this->saveController->save([
      'email' => 'timezone-manual@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'timezone' => 'Europe/Prague',
    ]);

    verify($subscriber->getTimeZone())->equals('Europe/Prague');
    verify($subscriber->getTimeZoneSource())->equals(SubscriberEntity::TIME_ZONE_SOURCE_MANUAL);
    verify($subscriber->getTimeZoneConfidence())->equals(SubscriberEntity::TIME_ZONE_CONFIDENCE_MANUAL);
    verify($subscriber->getTimeZoneUpdatedAt())->notNull();
  }

  public function testItClearsTimeZoneWhenManualValueIsEmpty(): void {
    $subscriber = $this->saveController->save([
      'email' => 'timezone-clear@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'timezone' => 'Europe/Prague',
    ]);
    verify($subscriber->getTimeZone())->equals('Europe/Prague');

    $subscriber = $this->saveController->save([
      'id' => $subscriber->getId(),
      'timezone' => '',
    ]);

    verify($subscriber->getTimeZone())->null();
    verify($subscriber->getTimeZoneSource())->null();
    verify($subscriber->getTimeZoneConfidence())->null();
    verify($subscriber->getTimeZoneUpdatedAt())->null();
  }

  public function testItIgnoresInvalidManualTimeZone(): void {
    $subscriber = $this->saveController->save([
      'email' => 'timezone-invalid@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'timezone' => 'Invalid/Zone',
    ]);

    verify($subscriber->getTimeZone())->null();
    verify($subscriber->getTimeZoneSource())->null();
  }

  public function testItKeepsTimeZoneSourceWhenManualValueIsUnchanged(): void {
    $subscriber = $this->saveController->save([
      'email' => 'timezone-unchanged@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::TIME_ZONE_FIELD_NAME => 'Europe/Prague',
    ]);
    verify($subscriber->getTimeZoneSource())->equals(SubscriberEntity::TIME_ZONE_SOURCE_FORM);

    $subscriber = $this->saveController->save([
      'id' => $subscriber->getId(),
      'timezone' => 'Europe/Prague',
    ]);

    verify($subscriber->getTimeZone())->equals('Europe/Prague');
    verify($subscriber->getTimeZoneSource())->equals(SubscriberEntity::TIME_ZONE_SOURCE_FORM);
    verify($subscriber->getTimeZoneConfidence())->equals(SubscriberEntity::TIME_ZONE_CONFIDENCE_BROWSER);
  }

  public function testItCanUpdateASubscriber(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $data = [
      'id' => $subscriber->getId(),
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
      ],
      'tags' => [
        'First',
      ],
    ];

    $this->entityManager->clear();
    $subscriber = $this->saveController->save($data);
    verify($subscriber->getEmail())->equals('second@test.com');
    verify($subscriber->getStatus())->equals($data['status']);
    verify($subscriber->getFirstName())->equals($data['first_name']);
    verify($subscriber->getLastName())->equals($data['last_name']);
    verify($subscriber->getLastSubscribedAt())->notNull();
    verify($subscriber->getSegments())->arrayCount(1);
    verify($subscriber->getSubscriberSegments())->arrayCount(1);
    verify($subscriber->getSubscriberTags())->arrayCount(1);
    // Check exact tag name
    $tagNames = array_values(array_map(function (SubscriberTagEntity $subscriberTag): string {
      return ($tag = $subscriberTag->getTag()) ? $tag->getName() : '';
    }, $subscriber->getSubscriberTags()->toArray()));
    verify($data['tags'])->equals($tagNames);

    // Test updating tags
    $data['tags'] = [
      'Second',
      'Third',
    ];
    $subscriber = $this->saveController->save($data);
    verify($subscriber->getSubscriberTags())->arrayCount(2);
    $tagNames = array_values(array_map(function (SubscriberTagEntity $subscriberTag): string {
      return ($tag = $subscriberTag->getTag()) ? $tag->getName() : '';
    }, $subscriber->getSubscriberTags()->toArray()));
    verify($data['tags'])->equals($tagNames);
  }

  public function testItThrowsExceptionWhenCreatingSubscriberWithDuplicateEmail(): void {
    $subscriber = $this->createSubscriber('duplicate@test.com', SubscriberEntity::STATUS_UNCONFIRMED);

    $data = [
      'email' => $subscriber->getEmail(),
      'first_name' => 'Changed',
    ];

    $this->entityManager->clear();
    $this->expectException(ConflictException::class);
    $this->expectExceptionMessage('A subscriber with E-mail "' . $subscriber->getEmail() . '" already exists.');

    $this->saveController->save($data);
  }

  public function testItThrowsExceptionWhenUpdatingSubscriberEmailIfNotUnique(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $subscriber2 = $this->createSubscriber('third@test.com', SubscriberEntity::STATUS_UNCONFIRMED);

    $data = [
      'id' => $subscriber->getId(),
      'email' => $subscriber2->getEmail(),
    ];

    $this->entityManager->clear();
    $this->expectException(ConflictException::class);
    $this->expectExceptionMessage('A subscriber with E-mail "' . $subscriber2->getEmail() . '" already exists.');

    $this->saveController->save($data);
  }

  public function testItDeletesOrphanSubscriberSegmentsOnUpdate(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $segmentTwo = $this->segmentsRepository->createOrUpdate('Segment Two');

    // Create orphan record on SubscriberSegments
    $orphanSegment = $this->segmentsRepository->createOrUpdate('Orphan');
    $this->createSubscriberSegment($subscriber, $orphanSegment);
    $this->entityManager->remove($orphanSegment);
    $this->entityManager->flush();
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriber]);
    verify($subscriberSegments)->arrayCount(1);

    // Update subscriber with new segments
    $data = [
      'id' => $subscriber->getId(),
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
        $segmentTwo->getId(),
      ],
    ];

    $this->entityManager->clear();
    $subscriber = $this->saveController->save($data);
    // Check the $orphanSegment is gone
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriber]);
    verify($subscriberSegments)->arrayCount(2);
  }

  public function testItTriggersSegmentSubscribedHook(): void {
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $segmentTwo = $this->segmentsRepository->createOrUpdate('Segment Two');
    $data = [
      'email' => 'test@test.com',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [$segmentOne->getId(), $segmentTwo->getId()],
    ];

    $count = 0;
    $this->diContainer->get(WPFunctions::class)->addAction('mailpoet_segment_subscribed', function () use (&$count) {
      $count++;
    });

    // create subscriber with subscribed status
    $count = 0;
    $subscriber = $this->saveController->save($data);
    $this->assertSame(2, $count); // @phpstan-ignore-line -- PHPStan doesn't get the $count side effect

    // update subscriber to non-subscribed status
    $count = 0;
    $this->saveController->save(array_merge($data, [
      'id' => $subscriber->getId(),
      'status' => SubscriberEntity::STATUS_UNCONFIRMED,
    ]));
    $this->assertSame(0, $count); // @phpstan-ignore-line -- PHPStan doesn't get the $count side effect

    // update subscriber to subscribed status
    $count = 0;
    $this->saveController->save(array_merge($data, ['id' => $subscriber->getId()]));
    $this->assertSame(2, $count); // @phpstan-ignore-line -- PHPStan doesn't get the $count side effect
  }

  public function testItSanitizesFirstAndLastNameOnCreate(): void {
    $subscriber = $this->saveController->createOrUpdate([
      'email' => 'sanitize-name@example.com',
      'first_name' => '<script>alert(1)</script>John',
      'last_name' => '  Doe<img src=x>  ',
    ], null);

    verify($subscriber->getFirstName())->equals('John');
    verify($subscriber->getLastName())->equals('Doe');
  }

  public function testItPreservesLegitimateNameCharacters(): void {
    $subscriber = $this->saveController->createOrUpdate([
      'email' => 'legit-name@example.com',
      'first_name' => 'Tom & Jerry',
      'last_name' => "O'Brien-José",
    ], null);

    verify($subscriber->getFirstName())->equals('Tom & Jerry');
    verify($subscriber->getLastName())->equals("O'Brien-José");
  }

  private function createSubscriber(string $email, string $status): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSubscriberSegment(SubscriberEntity $subscriber, SegmentEntity $segment): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }
}
