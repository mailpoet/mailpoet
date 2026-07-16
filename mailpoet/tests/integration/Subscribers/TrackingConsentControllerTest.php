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

    $settings->set('tracking.consent.track_unknown', false);
    $this->assertFalse($controller->isTrackingAllowed($subscriber));
  }

  public function testGlobalTrackingOffWinsOverIndividualConsent() {
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $controller = $this->diContainer->get(TrackingConsentController::class);
    $subscriber = new SubscriberEntity();
    $subscriber->setTrackingConsent(SubscriberEntity::TRACKING_CONSENT_GRANTED);

    $this->assertFalse($controller->isTrackingAllowed($subscriber));
  }
}
