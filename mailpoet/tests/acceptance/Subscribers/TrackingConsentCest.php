<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\TrackingConsentController;
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
    $i->see('you will keep receiving emails');

    $i->wantTo('Confirm the opt-out and see the done state');
    $i->click('Stop tracking my activity');
    $i->waitForText('You have opted out of email activity tracking.');
    $i->see('Tracking of email opens and link clicks is now off');
    $i->seeNoJSErrors();

    $i->wantTo('Verify the subscriber consent is recorded as denied via the footer link');
    $reloaded = $this->reloadSubscriber($subscriberId);
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_DENIED, $reloaded->getTrackingConsent());
    Assert::assertSame(SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK, $reloaded->getTrackingConsentMethod());
  }

  public function subscriberChoiceSettingGatesTheManagePageCheckbox(\AcceptanceTester $i) {
    $i->wantTo('Verify the manage-subscription tracking checkbox is hidden until the site asks subscribers to choose');

    $segment = (new Segment())
      ->withName('Tracking consent gating list')
      ->create();
    $subscriber = (new Subscriber())
      ->withEmail('tracking-gating@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $manageUrl = SubscriptionUrlFactory::getInstance()->getManageUrl($subscriber);
    Assert::assertIsString($manageUrl);

    $i->wantTo('See no tracking control at all on a site that tracks everyone without asking');
    $this->settings->withSubscriberChoice(TrackingConsentController::CHOICE_TRACK_ALL);
    $i->amOnUrl($manageUrl);
    // Wait for a field that is always present, so the absence check below runs
    // against a fully rendered form rather than an empty page.
    $i->waitForText('Email subscription status');
    $i->dontSee('Email activity tracking');
    $i->dontSee('Allow tracking of email opens and link clicks');
    $i->seeNoJSErrors();

    $i->wantTo('See the control appear once the site asks everyone');
    $this->settings->withSubscriberChoice(TrackingConsentController::CHOICE_ASK_ALL);
    $i->amOnUrl($manageUrl);
    $i->waitForText('Email activity tracking');
    $i->see('Allow tracking of email opens and link clicks');
    // Never pre-ticked: a pre-ticked consent box is not valid consent.
    $i->dontSeeCheckboxIsChecked('input[data-parsley-group="custom_field_tracking_consent"]');
    $i->seeNoJSErrors();
  }

  public function managePageCheckboxControlsTrackingConsent(\AcceptanceTester $i) {
    $i->wantTo('Verify the manage-subscription checkbox grants tracking consent, while an untouched save leaves an unknown subscriber unchanged');

    // The checkbox only renders once the site asks subscribers to choose.
    $this->settings->withSubscriberChoice(TrackingConsentController::CHOICE_ASK_ALL);

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
    $i->see('Allow tracking of email opens and link clicks');
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
