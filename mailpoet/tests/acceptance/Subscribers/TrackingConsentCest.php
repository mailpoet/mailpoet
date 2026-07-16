<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

/**
 * @group frontend
 */
class TrackingConsentCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
    $this->settings->withTrackingEnabled();
  }

  public function footerTrackingOptOutLinkOptsSubscriberOut(\AcceptanceTester $i) {
    $i->wantTo('Verify a subscriber can opt out of activity tracking via the footer opt-out link');

    $segment = (new Segment())
      ->withName('Tracking consent list')
      ->create();
    $subscriber = (new Subscriber())
      ->withEmail('tracking-optout@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberId = (int)$subscriber->getId();

    $optOutUrl = SubscriptionUrlFactory::getInstance()->getTrackingOptOutUrl($subscriber);
    Assert::assertIsString($optOutUrl);

    $i->wantTo('Open the opt-out confirmation page from the footer link');
    $i->amOnUrl($optOutUrl);
    $i->waitForText('Opt out of email activity tracking');
    $i->see('you will keep receiving our emails');

    $i->wantTo('Confirm the opt-out and see the done state');
    $i->click('Stop tracking my activity');
    $i->waitForText('You have opted out of email activity tracking.');
    $i->see('We will no longer track when you open our emails');
    $i->seeNoJSErrors();

    $i->wantTo('Verify the subscriber consent is recorded as denied via the footer link');
    $reloaded = $this->reloadSubscriber($subscriberId);
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_DENIED, $reloaded->getTrackingConsent());
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK, $reloaded->getTrackingConsentMethod());
  }

  public function managePageCheckboxControlsTrackingConsent(\AcceptanceTester $i) {
    $i->wantTo('Verify the manage-subscription checkbox grants tracking consent, while an untouched save leaves an unknown subscriber unchanged');

    $segment = (new Segment())
      ->withName('Tracking consent list')
      ->create();
    $subscriber = (new Subscriber())
      ->withEmail('tracking-manage@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberId = (int)$subscriber->getId();
    // A freshly created subscriber has never been asked, so consent is unknown.
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_UNKNOWN, $subscriber->getTrackingConsent());

    $manageUrl = SubscriptionUrlFactory::getInstance()->getManageUrl($subscriber);
    Assert::assertIsString($manageUrl);
    $checkbox = 'input[data-parsley-group="custom_field_tracking_consent"]';
    $saveButton = '[data-automation-id="subscribe-submit-button"]';
    $successMessage = 'Your subscription settings have been saved.';
    $approximateSaveButtonHeight = 50; // scroll offset so the button is not hidden above the top fold

    $i->wantTo('See the tracking-consent checkbox rendered unchecked for an unknown subscriber');
    $i->amOnUrl($manageUrl);
    $i->waitForText('Email activity tracking');
    $i->see('Allow us to track when I open emails and which links I click');
    $i->dontSeeCheckboxIsChecked($checkbox);

    $i->wantTo('Save without touching the checkbox and verify consent stays unknown (not silently denied)');
    $i->scrollTo($saveButton, 0, -$approximateSaveButtonHeight);
    $i->click('Save changes');
    $i->waitForText($successMessage);
    $reloaded = $this->reloadSubscriber($subscriberId);
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_UNKNOWN, $reloaded->getTrackingConsent());

    $i->wantTo('Tick the checkbox and save to grant tracking consent');
    $i->checkOption($checkbox);
    $i->scrollTo($saveButton, 0, -$approximateSaveButtonHeight);
    $i->click('Save changes');
    $i->waitForText($successMessage);
    $i->seeCheckboxIsChecked($checkbox);
    $i->seeNoJSErrors();
    $reloaded = $this->reloadSubscriber($subscriberId);
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_GRANTED, $reloaded->getTrackingConsent());
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE, $reloaded->getTrackingConsentMethod());
  }

  private function reloadSubscriber(int $id): SubscriberEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    // Drop the identity map so we read the row the browser just wrote, not a cached copy.
    $entityManager->clear();
    $subscriber = $entityManager->find(SubscriberEntity::class, $id);
    Assert::assertInstanceOf(SubscriberEntity::class, $subscriber);
    return $subscriber;
  }
}
