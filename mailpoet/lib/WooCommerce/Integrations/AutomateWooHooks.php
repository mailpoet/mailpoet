<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\Integrations;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class AutomateWooHooks {
  const AUTOMATE_WOO_PLUGIN_SLUG = 'automatewoo/automatewoo.php';

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->wp = $wp;
  }

  public function isAutomateWooActive(): bool {
    return $this->wp->isPluginActive(self::AUTOMATE_WOO_PLUGIN_SLUG);
  }

  public function areMethodsAvailable(): bool {
    // method_exists checks guard older AutomateWoo versions that may not ship these methods, even though current stubs declare them.
    // @phpstan-ignore-next-line function.alreadyNarrowedType
    return class_exists('AutomateWoo\Customer_Factory') && method_exists('AutomateWoo\Customer_Factory', 'get_by_email') &&
      // @phpstan-ignore-next-line function.alreadyNarrowedType
      class_exists('AutomateWoo\Customer') && method_exists('AutomateWoo\Customer', 'opt_out');
  }

  /**
   * Separate from areMethodsAvailable() on purpose: that one gates the existing status
   * sync, and tightening it would silently switch off working behaviour on an older
   * AutomateWoo. An AutomateWoo without the companion change simply does not get consent
   * forwarding, while its status sync keeps working.
   */
  public function areTrackingMethodsAvailable(): bool {
    // method_exists checks guard older AutomateWoo versions that may not ship these methods, even though current stubs declare them.
    // @phpstan-ignore-next-line function.alreadyNarrowedType
    return class_exists('AutomateWoo\Customer') && method_exists('AutomateWoo\Customer', 'opt_out_of_tracking') &&
      // @phpstan-ignore-next-line function.alreadyNarrowedType
      method_exists('AutomateWoo\Customer', 'opt_in_to_tracking');
  }

  public function isAutomateWooReady(): bool {
    return $this->isAutomateWooActive() && $this->areMethodsAvailable();
  }

  /**
   * @return \AutomateWoo\Customer|false
   */
  public function getAutomateWooCustomer(string $email) {
    // AutomateWoo\Customer_Factory::get_by_email() returns false if customer is not found
    // Second parameter is set to false to prevent creating new customer if not found
    return \AutomateWoo\Customer_Factory::get_by_email($email, false);
  }

  public function setup(): void {
    if (!$this->isAutomateWooReady()) {
      return;
    }
    $this->wp->addAction(SubscriberEntity::HOOK_SUBSCRIBER_STATUS_CHANGED, [$this, 'syncSubscriber'], 10, 1);
    $this->wp->addAction('mailpoet_segment_subscribed', [$this, 'maybeOptInSubscriber'], 10, 1);
    $this->wp->addAction(SubscriberEntity::HOOK_SUBSCRIBER_TRACKING_CONSENT_CHANGED, [$this, 'syncTrackingConsent'], 10, 3);
  }

  /**
   * Mirror a MailPoet tracking-consent change onto the AutomateWoo customer.
   *
   * Denied stops AutomateWoo tracking. Granted clears the flag, the way optInSubscriber()
   * reverses optOutSubscriber(); without that, somebody who changed their mind would be
   * permanently untracked in AutomateWoo with no way back from MailPoet. `unknown` is
   * left alone: nobody was asked, and that is not an answer. Anything else is a value
   * this plugin never writes, and is left alone too.
   */
  public function syncTrackingConsent(int $subscriberId, string $oldConsent, string $newConsent): void {
    if (!$this->isAutomateWooReady() || !$this->areTrackingMethodsAvailable()) {
      return;
    }

    if ($newConsent === SubscriberEntity::TRACKING_CONSENT_UNKNOWN || $newConsent === $oldConsent) {
      return;
    }

    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber || !$subscriber->getEmail()) {
      return;
    }

    $automateWooCustomer = $this->getAutomateWooCustomer($subscriber->getEmail());
    if (!$automateWooCustomer) {
      return;
    }

    // Explicit values only, failing closed on anything unrecognised, the same way
    // TrackingConsentController::isTrackingAllowed() does. Clearing an opt-out is the
    // consequential direction, so it needs a real `granted`, not just "not denied".
    if ($newConsent === SubscriberEntity::TRACKING_CONSENT_DENIED) {
      $automateWooCustomer->opt_out_of_tracking();
    } elseif ($newConsent === SubscriberEntity::TRACKING_CONSENT_GRANTED) {
      $automateWooCustomer->opt_in_to_tracking();
    }
  }

  public function optOutSubscriber($subscriber): void {
    if (!$this->isAutomateWooReady() || !$subscriber) {
      return;
    }

    $automateWooCustomer = $this->getAutomateWooCustomer($subscriber->getEmail());
    if (!$automateWooCustomer) {
      return;
    }

    $automateWooCustomer->opt_out();
  }

  public function optInSubscriber($subscriber): void {
    if (!$this->isAutomateWooReady() || !$subscriber) {
      return;
    }

    $automateWooCustomer = $this->getAutomateWooCustomer($subscriber->getEmail());
    if (!$automateWooCustomer) {
      return;
    }

    $automateWooCustomer->opt_in();
  }

  public function syncSubscriber(int $subscriberId): void {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber || !$subscriber->getEmail()) {
      return;
    }

    if ($this->isWooCommerceSubscribed($subscriber)) {
      $this->optInSubscriber($subscriber);
    } else {
      $this->optOutSubscriber($subscriber);
    }
  }

  /**
   * Opt-In the subscriber in AW only if the subscriber belongs to WooCommerce list.
   */
  public function maybeOptInSubscriber(SubscriberSegmentEntity $subscriberSegment) {
    if ($subscriberSegment->getSegment() && $subscriberSegment->getSegment()->getType() === SegmentEntity::TYPE_WC_USERS) {
      $this->optInSubscriber($subscriberSegment->getSubscriber());
    }
  }

  private function isWooCommerceSubscribed(SubscriberEntity $subscriber) {
    return $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED
      && $this->subscribersRepository->getWooCommerceSegmentSubscriber($subscriber->getEmail());
  }
}
