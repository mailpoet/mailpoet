<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\FilterFactory;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTrackingConsent;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberTrackingConsentTest extends \MailPoetTest {

  /** @var SubscriberTrackingConsent */
  private $filter;

  public function _before() {
    parent::_before();
    $this->filter = $this->diContainer->get(SubscriberTrackingConsent::class);
    $this->createFixtures();
  }

  /**
   * Four subscribers: one per consent state, plus one created with no consent
   * call at all. The last one stands in for a row that existed before the
   * consent migration and pins the column default.
   */
  private function createFixtures(): void {
    (new Subscriber())
      ->withEmail('granted@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED)
      ->create();
    (new Subscriber())
      ->withEmail('denied@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED)
      ->create();
    (new Subscriber())
      ->withEmail('unknown@example.com')
      ->withTrackingConsent(SubscriberEntity::TRACKING_CONSENT_UNKNOWN)
      ->create();
    // Deliberately no withTrackingConsent() call — pins the migration default.
    (new Subscriber())
      ->withEmail('never-asked@example.com')
      ->create();
  }

  public function testItMatchesGrantedOnly(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS,
      SubscriberEntity::TRACKING_CONSENT_GRANTED
    );
    $this->assertEqualsCanonicalizing(['granted@example.com'], $matching);
  }

  public function testItMatchesDeniedOnly(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS,
      SubscriberEntity::TRACKING_CONSENT_DENIED
    );
    $this->assertEqualsCanonicalizing(['denied@example.com'], $matching);
  }

  public function testItMatchesUnknownIncludingSubscribersNeverAsked(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS,
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN
    );
    $this->assertEqualsCanonicalizing(
      ['unknown@example.com', 'never-asked@example.com'],
      $matching
    );
  }

  public function testIsNotDeniedReturnsEveryoneElse(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS_NOT,
      SubscriberEntity::TRACKING_CONSENT_DENIED
    );
    $this->assertEqualsCanonicalizing(
      ['granted@example.com', 'unknown@example.com', 'never-asked@example.com'],
      $matching
    );
  }

  public function testIsNotGrantedReturnsEveryoneElse(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS_NOT,
      SubscriberEntity::TRACKING_CONSENT_GRANTED
    );
    $this->assertEqualsCanonicalizing(
      ['denied@example.com', 'unknown@example.com', 'never-asked@example.com'],
      $matching
    );
  }

  public function testIsNotUnknownExcludesSubscribersNeverAsked(): void {
    $matching = $this->getMatchingEmails(
      DynamicSegmentFilterData::OPERATOR_IS_NOT,
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN
    );
    $this->assertEqualsCanonicalizing(
      ['granted@example.com', 'denied@example.com'],
      $matching
    );
  }

  /**
   * The filter reads the stored value, never effective trackability. Segment
   * membership must not move because someone edited the settings page.
   */
  public function testMembershipDoesNotChangeWithSubscriberChoiceSetting(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $choices = [
      TrackingConsentController::CHOICE_TRACK_ALL,
      TrackingConsentController::CHOICE_ASK_NEW,
      TrackingConsentController::CHOICE_ASK_ALL,
    ];
    foreach ($choices as $choice) {
      $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, $choice);
      $this->assertEqualsCanonicalizing(
        ['unknown@example.com', 'never-asked@example.com'],
        $this->getMatchingEmails(DynamicSegmentFilterData::OPERATOR_IS, SubscriberEntity::TRACKING_CONSENT_UNKNOWN),
        "'is unknown' changed under subscriber choice '$choice'"
      );
      $this->assertEqualsCanonicalizing(
        ['granted@example.com', 'unknown@example.com', 'never-asked@example.com'],
        $this->getMatchingEmails(DynamicSegmentFilterData::OPERATOR_IS_NOT, SubscriberEntity::TRACKING_CONSENT_DENIED),
        "'is not denied' changed under subscriber choice '$choice'"
      );
      $this->assertEqualsCanonicalizing(
        ['granted@example.com'],
        $this->getMatchingEmails(DynamicSegmentFilterData::OPERATOR_IS, SubscriberEntity::TRACKING_CONSENT_GRANTED),
        "'is granted' changed under subscriber choice '$choice'"
      );
    }
  }

  public function testItRejectsAValueOutsideTheThreeStates(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_VALUE);
    $this->getMatchingEmails(DynamicSegmentFilterData::OPERATOR_IS, 'GRANTED');
  }

  public function testItRejectsAnUnsupportedOperator(): void {
    $this->expectException(InvalidFilterException::class);
    $this->expectExceptionCode(InvalidFilterException::MISSING_OPERATOR);
    $this->getMatchingEmails(DynamicSegmentFilterData::OPERATOR_ANY, SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  /**
   * Without this the filter class can be perfect and the segment still ignores
   * it, because FilterFactory would fall through to the WordPress role filter.
   */
  public function testTheFilterFactoryResolvesTheTrackingConsentAction(): void {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTrackingConsent::TYPE, [
      'value' => SubscriberEntity::TRACKING_CONSENT_DENIED,
      'operator' => DynamicSegmentFilterData::OPERATOR_IS,
    ]);
    $segment = new SegmentEntity('tracking consent segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $filterEntity = new DynamicSegmentFilterEntity($segment, $data);
    $factory = $this->diContainer->get(FilterFactory::class);
    $this->assertInstanceOf(SubscriberTrackingConsent::class, $factory->getFilterForFilterEntity($filterEntity));
  }

  public function testItReturnsNoLookupData(): void {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTrackingConsent::TYPE, [
      'value' => SubscriberEntity::TRACKING_CONSENT_DENIED,
      'operator' => DynamicSegmentFilterData::OPERATOR_IS,
    ]);
    $this->assertSame([], $this->filter->getLookupData($data));
  }

  /**
   * @return string[]
   */
  private function getMatchingEmails(string $operator, string $value): array {
    $data = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTrackingConsent::TYPE, [
      'value' => $value,
      'operator' => $operator,
    ]);
    return $this->tester->getSubscriberEmailsMatchingDynamicFilter($data, $this->filter);
  }
}
