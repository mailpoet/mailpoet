<?php declare(strict_types=1);

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class SubscriberActivityTrackerTest extends \MailPoetTest {
  /** @var SubscriberActivityTracker */
  private $tracker;

  /** @var SubscriberCookie & MockObject */
  private $subscriberCookie;

  /** @var PageViewCookie & MockObject */
  private $pageViewCookie;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->cleanUp();
    $this->pageViewCookie = $this->createMock(PageViewCookie::class);
    $this->subscriberCookie = $this->createMock(SubscriberCookie::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->tracker = new SubscriberActivityTracker(
      $this->pageViewCookie,
      $this->subscriberCookie,
      $this->diContainer->get(SubscribersRepository::class),
      $this->wp,
      $this->diContainer->get(TrackingConfig::class)
    );
  }

  public function testItUpdatesPageViewCookieAndSubscriberEngagement() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $subscriber = $this->createSubscriber();
    $oldEngagementTime = Carbon::now()->subMinutes(2);
    $subscriber->setLastEngagementAt($oldEngagementTime);
    $this->entityManager->flush();
    $oldPageViewTimestamp = $this->wp->currentTime('timestamp') - 180; // 3 minutes ago
    $this->setPageViewCookieTimestamp($oldPageViewTimestamp);
    $this->setSubscriberCookieSubscriber($subscriber);
    $this->pageViewCookie
      ->expects($this->once())
      ->method('setPageViewTimestamp');
    $result = $this->tracker->trackActivity();
    expect($result)->true();
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->greaterThan($oldEngagementTime);
  }

  public function testItDoesntTrackWhenCookieTrackingIsDisabled() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $result = $this->tracker->trackActivity();
    $subscriber = $this->createSubscriber();
    $oldPageViewTimestamp = $this->wp->currentTime('timestamp') - 180; // 3 minutes ago
    $this->setPageViewCookieTimestamp($oldPageViewTimestamp);
    $this->setSubscriberCookieSubscriber($subscriber);
    expect($result)->false();
  }

  public function testItDoesntTrackWhenCalledWithinAMinute() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $result = $this->tracker->trackActivity();
    $subscriber = $this->createSubscriber();
    $oldPageViewTimestamp = $this->wp->currentTime('timestamp') - 50; // 50 seconds  ago
    $this->setPageViewCookieTimestamp($oldPageViewTimestamp);
    $this->setSubscriberCookieSubscriber($subscriber);
    expect($result)->false();
  }

  public function testItDoesntTrackWhenSubscriberCookieIsNotSet() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $result = $this->tracker->trackActivity();
    $oldPageViewTimestamp = $this->wp->currentTime('timestamp') - 180; // 3 minutes  ago
    $this->setPageViewCookieTimestamp($oldPageViewTimestamp);
    $this->setSubscriberCookieSubscriber(null);
    expect($result)->false();
  }

  private function createSubscriber(): SubscriberEntity {
    return (new Subscriber())->create();
  }

  private function setPageViewCookieTimestamp(int $timestamp) {
    $this->pageViewCookie
      ->method('getPageViewTimestamp')
      ->willReturn($timestamp);
  }

  private function setSubscriberCookieSubscriber(?SubscriberEntity $subscriberEntity) {
    $this->subscriberCookie
      ->method('getSubscriberId')
      ->willReturn($subscriberEntity ? $subscriberEntity->getId() : null);
  }

  private function cleanUp() {
    $this->truncateEntity(SubscriberEntity::class);
  }

  public function _after() {
    $this->cleanUp();
  }
}
