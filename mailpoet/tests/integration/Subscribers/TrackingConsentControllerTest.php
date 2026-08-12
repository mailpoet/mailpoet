<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;

class TrackingConsentControllerTest extends \MailPoetTest {
  public function testItAllowsGrantedAndBlocksDenied() {
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity();

    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    $this->assertTrue($controller->isTrackingAllowed($subscriber));

    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);
    $this->assertFalse($controller->isTrackingAllowed($subscriber));
  }

  public function testUnknownFollowsTheSiteSetting() {
    $settings = $this->diContainer->get(SettingsController::class);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity(); // defaults to unknown

    // Default: existing sites keep tracking exactly as they do today.
    $this->assertTrue($controller->isTrackingAllowed($subscriber));

    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);
    $this->assertFalse($controller->isTrackingAllowed($subscriber));
  }

  public function testItHidesSubscriberControlsByDefault() {
    $controller = $this->diContainer->get(TrackingConsentController::class);

    verify($controller->getSubscriberChoice())->equals(TrackingConsentController::CHOICE_TRACK_ALL);
    verify($controller->areSubscriberControlsVisible())->false();
    verify($controller->shouldTrackUnknownConsent())->true();
  }

  public function testAskNewSubscribersShowsControlsButKeepsTrackingUnknown() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_NEW);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity(); // defaults to unknown

    verify($controller->areSubscriberControlsVisible())->true();
    verify($controller->shouldTrackUnknownConsent())->true();
    verify($controller->isTrackingAllowed($subscriber))->true();
  }

  public function testAskEveryoneShowsControlsAndStopsTrackingUnknown() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity(); // defaults to unknown

    verify($controller->areSubscriberControlsVisible())->true();
    verify($controller->shouldTrackUnknownConsent())->false();
    verify($controller->isTrackingAllowed($subscriber))->false();
  }

  public function testDeniedStaysUntrackedEvenWhenControlsAreHidden() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_TRACK_ALL);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity();
    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);

    verify($controller->areSubscriberControlsVisible())->false();
    verify($controller->isTrackingAllowed($subscriber))->false();
  }

  public function testAnUnrecognisedChoiceFallsBackToTrackingEveryone() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, 'nonsense');
    $controller = $this->diContainer->get(TrackingConsentController::class);

    verify($controller->getSubscriberChoice())->equals(TrackingConsentController::CHOICE_TRACK_ALL);
    verify($controller->areSubscriberControlsVisible())->false();
    verify($controller->shouldTrackUnknownConsent())->true();
  }

  public function testGlobalTrackingOffWinsOverIndividualConsent() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity();
    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);

    $this->assertFalse($controller->isTrackingAllowed($subscriber));
  }

  /**
   * The deliberate split between the two methods. isConsentGivenForTracking()
   * answers "did this person allow it?", which is what we stamp on a sent row;
   * isTrackingAllowed() folds in the site-wide switch, which must never be
   * baked into stored history — a site that turns tracking off for a month and
   * back on would otherwise freeze that month's campaigns at 0% coverage.
   */
  public function testConsentOnlyCheckIgnoresTheGlobalTrackingSwitch() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity();
    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);

    verify($controller->isTrackingAllowed($subscriber))->false();
    verify($controller->isConsentGivenForTracking($subscriber))->true();
  }

  public function testConsentOnlyCheckFollowsTheSubscriberChoiceForUnknown() {
    $settings = $this->diContainer->get(SettingsController::class);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $unknown = new SubscriberEntity(); // defaults to unknown
    $denied = new SubscriberEntity();
    $denied->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_DENIED);

    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_TRACK_ALL);
    verify($controller->isConsentGivenForTracking($unknown))->true();
    verify($controller->isConsentGivenForTracking($denied))->false();

    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_NEW);
    verify($controller->isConsentGivenForTracking($unknown))->true();

    $settings->set(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE, TrackingConsentController::CHOICE_ASK_ALL);
    verify($controller->isConsentGivenForTracking($unknown))->false();
    verify($controller->isConsentGivenForTracking($denied))->false();
  }
}
