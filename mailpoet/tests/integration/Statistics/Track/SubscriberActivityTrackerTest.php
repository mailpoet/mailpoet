<?php declare(strict_types = 1);

namespace MailPoet\Statistics\Track;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\User;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
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

  /** @var int */
  private $backupUserId;

  public function _before() {
    parent::_before();
    $this->pageViewCookie = $this->createMock(PageViewCookie::class);
    $this->subscriberCookie = $this->createMock(SubscriberCookie::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->tracker = new SubscriberActivityTracker(
      $this->pageViewCookie,
      $this->subscriberCookie,
      $this->diContainer->get(SubscribersRepository::class),
      $this->wp,
      $this->diContainer->get(WooCommerceHelper::class),
      $this->diContainer->get(TrackingConfig::class)
    );
    $this->cleanUp();
    $this->backupUserId = $this->wp->getCurrentUserId();
  }

  public function testItUpdatesPageViewCookieAndSubscriberEngagement() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $this->wp->wpSetCurrentUser(0);
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

  public function testItFiresActionWhenActivityIsTracked() {
    $callbackSubscriber = null;
    $callback = function (SubscriberEntity $subscriberEntity) use (&$callbackSubscriber) {
      $callbackSubscriber = $subscriberEntity;
    };
    $this->tracker->registerCallback('mailpoet_test', $callback);
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $this->wp->wpSetCurrentUser(0);
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
    $this->assertInstanceOf(SubscriberEntity::class, $callbackSubscriber);
  }

  public function testItUpdatesPageViewCookieAndSubscriberEngagementForWpUser() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $wpUserEmail = 'pageview_track_user@test.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $user = (new User())->createUser('tracking_enabled', 'editor', $wpUserEmail);
    $this->wp->wpSetCurrentUser($user->ID);
    $oldPageViewTimestamp = $this->wp->currentTime('timestamp') - 180; // 3 minutes ago
    $this->setPageViewCookieTimestamp($oldPageViewTimestamp);
    $this->setSubscriberCookieSubscriber(null);
    $this->pageViewCookie
      ->expects($this->once())
      ->method('setPageViewTimestamp');
    $result = $this->tracker->trackActivity();
    expect($result)->true();
    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['wpUserId' => $user->ID]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getLastEngagementAt())->greaterThan(Carbon::now()->subMinute());
    expect($subscriber->getLastEngagementAt())->lessThan(Carbon::now()->addMinute());
  }

  /**
   * @group woo
   */
  public function testItUpdatesPageViewCookieAndSubscriberEngagementForSubscriberDetectedFromWooSession() {
    $subscriber = $this->createSubscriber();
    $customer = ['email' => $subscriber->getEmail()];
    $sessionMock = $this->createMock(\WC_Session::class);
    $sessionMock
      ->expects($this->once())
      ->method('get')
      ->willReturn($customer);

    $wooMock = new \stdClass();
    $wooMock->session = $sessionMock;
    $wooHelperMock = $this->createMock(WooCommerceHelper::class);
    $wooHelperMock->method('WC')->willReturn($wooMock);
    $wooHelperMock->method('isWooCommerceActive')->willReturn(true);

    $tracker = new SubscriberActivityTracker(
      $this->pageViewCookie,
      $this->subscriberCookie,
      $this->diContainer->get(SubscribersRepository::class),
      $this->wp,
      $wooHelperMock,
      $this->diContainer->get(TrackingConfig::class)
    );

    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_FULL);
    $this->setSubscriberCookieSubscriber(null);
    $this->pageViewCookie
      ->expects($this->once())
      ->method('setPageViewTimestamp');
    $result = $tracker->trackActivity();
    expect($result)->true();
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->greaterThan(Carbon::now()->subMinute());
    expect($subscriber->getLastEngagementAt())->lessThan(Carbon::now()->addMinute());
  }

  public function testItUpdatesSubscriberEngagementForWpUserEvenWithDisabledCookieTracking() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $wpUserEmail = 'pageview_track_user@test.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $user = (new User())->createUser('no_tracking', 'editor', $wpUserEmail);
    $this->wp->wpSetCurrentUser($user->ID);
    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['wpUserId' => $user->ID]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setLastEngagementAt(Carbon::now()->subMonth());
    $this->entityManager->flush();
    $this->setPageViewCookieTimestamp(null);
    $this->setSubscriberCookieSubscriber(null);
    $result = $this->tracker->trackActivity();
    expect($result)->true();
    $this->entityManager->refresh($subscriber);
    expect($subscriber->getLastEngagementAt())->greaterThan(Carbon::now()->subMinute());
    expect($subscriber->getLastEngagementAt())->lessThan(Carbon::now()->addMinute());
  }

  public function testItDoesntTrackWhenCookieTrackingIsDisabledAndThereInNoWPUser() {
    $this->diContainer->get(SettingsController::class)->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $this->wp->wpSetCurrentUser(0);
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

  public function testItDoesntTrackWhenCantDetermineSubscriber() {
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

  private function setPageViewCookieTimestamp(?int $timestamp) {
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
    $user = $this->wp->getUserBy('email', 'editoruser@test.com');
    if ($user) {
      wp_delete_user($user->ID);
    }
  }

  public function _after() {
    parent::_after();
    $this->cleanUp();
    $this->wp->wpSetCurrentUser($this->backupUserId);
  }
}
