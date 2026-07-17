<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscription as UserSubscription;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const ENDPOINT = 'subscription';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  const ACTION_UNSUBSCRIBE_REASON = 'unsubscribeReason';
  const ACTION_CONFIRM_UNSUBSCRIBE = 'confirmUnsubscribe';
  const ACTION_RE_ENGAGEMENT = 'reEngagement';
  const ACTION_TRACKING_OPT_OUT = 'trackingOptOut';

  public $allowedActions = [
    self::ACTION_CONFIRM,
    self::ACTION_MANAGE,
    self::ACTION_UNSUBSCRIBE,
    self::ACTION_UNSUBSCRIBE_REASON,
    self::ACTION_CONFIRM_UNSUBSCRIBE,
    self::ACTION_RE_ENGAGEMENT,
    self::ACTION_TRACKING_OPT_OUT,
  ];

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var UserSubscription\Pages */
  private $subscriptionPages;

  /** @var WPFunctions */
  private $wp;

  /*** @var Request */
  private $request;

  public function __construct(
    UserSubscription\Pages $subscriptionPages,
    WPFunctions $wp,
    Request $request
  ) {
    $this->subscriptionPages = $subscriptionPages;
    $this->wp = $wp;
    $this->request = $request;
  }

  public function confirm($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM, $data);
    $subscription->confirm();
  }

  public function confirmUnsubscribe($data) {
    $enableUnsubscribeConfirmation = $this->wp->applyFilters('mailpoet_unsubscribe_confirmation_enabled', true);
    if ($this->isPostRequest()) {
      $this->performUnsubscribe($data, StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
      exit;
    }

    if ($enableUnsubscribeConfirmation) {
      $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM_UNSUBSCRIBE, $data);
    } else {
      $this->performUnsubscribe($data, StatisticsUnsubscribeEntity::METHOD_LINK);
    }
  }

  public function manage($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_MANAGE, $data);
  }

  public function unsubscribe($data) {
    if ($this->isPostRequest()) {
      if ($this->request->getStringParam('type') === 'confirmation') {
        // POST from confirmation page
        $this->performUnsubscribe($data, StatisticsUnsubscribeEntity::METHOD_LINK);
      } else {
        // POST from one click unsubscribe
        $this->performUnsubscribe($data, StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
        exit;
      }
    } else {
      // For GET requests, we render the confirmUnsubscribe page, unless it is preview request of successful unsubscribe
      // or the subscriber is already unsubscribed.
      if (isset($data['preview']) && $data['preview'] && !isset($data['token'])) {
        $this->performUnsubscribe($data, StatisticsUnsubscribeEntity::METHOD_LINK);
      } elseif ($this->renderUnsubscribePageForAlreadyUnsubscribedSubscriber($data)) {
        return;
      } else {
        $this->confirmUnsubscribe($data);
      }
    }
  }

  public function unsubscribeReason($data) {
    if (!$this->request->isPost()) {
      $this->wp->wpSafeRedirect($this->wp->homeUrl());
      exit;
    }

    $nonce = $this->request->getStringParam('_wpnonce');
    if (!$this->wp->wpVerifyNonce($nonce, 'mailpoet_unsubscribe_reason')) {
      $this->wp->wpDie(__('Security check failed.', 'mailpoet'), '', ['response' => 403]);
      exit;
    }

    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    $reason = strtolower($this->wp->sanitizeKey((string)$this->request->getStringParam('reason')));
    $reasonText = $this->request->getTextareaParam('reason_text');

    $saved = $reason !== '' && $subscription->saveUnsubscribeReason($reason, $reasonText);
    $this->wp->wpSafeRedirect($subscription->getUnsubscribeReasonRedirectUrl($saved));
    exit;
  }

  public function reEngagement($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_RE_ENGAGEMENT, $data);
  }

  public function trackingOptOut($data) {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_TRACKING_OPT_OUT, $data);
    if ($this->isPostRequest()) {
      $nonce = $this->request->getStringParam('_wpnonce');
      if (!$this->wp->wpVerifyNonce($nonce, 'mailpoet_tracking_opt_out')) {
        $this->wp->wpDie(__('Security check failed.', 'mailpoet'), '', ['response' => 403]);
        exit;
      }
      $subscription->trackingOptOut(
        SubscriberEntity::TRACKING_CONSENT_METHOD_FOOTER_LINK,
        UserSubscription\Pages::getTrackingOptOutConsentCopy()
      );
    }
    // GET renders the confirmation page; after POST the same page renders
    // its "done" state via getTrackingOptOutContent().
  }

  private function initSubscriptionPage($action, $data) {
    return $this->subscriptionPages->init($action, $data, true, true);
  }

  private function renderUnsubscribePageForAlreadyUnsubscribedSubscriber($data): bool {
    $subscription = $this->subscriptionPages->init(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data, false, false);
    if (!$subscription->isSubscriberUnsubscribed()) {
      return false;
    }

    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    return true;
  }

  private function performUnsubscribe($data, string $method): void {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    $subscription->unsubscribe($method);
  }

  private function isPostRequest(): bool {
    if ($this->request->isPost()) {
      return true;
    }
    // For tracking redirects we store original method in the query string
    $requestMethod = $this->request->getStringParam('request_method');
    if (!$requestMethod) {
      return false;
    }
    return strtoupper($requestMethod) === 'POST';
  }
}
