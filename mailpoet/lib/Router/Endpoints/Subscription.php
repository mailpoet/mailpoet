<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Subscription as UserSubscription;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const ENDPOINT = 'subscription';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  const ACTION_CONFIRM_UNSUBSCRIBE = 'confirmUnsubscribe';
  const ACTION_RE_ENGAGEMENT = 'reEngagement';

  public $allowedActions = [
    self::ACTION_CONFIRM,
    self::ACTION_MANAGE,
    self::ACTION_UNSUBSCRIBE,
    self::ACTION_CONFIRM_UNSUBSCRIBE,
    self::ACTION_RE_ENGAGEMENT,
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
    if ($this->request->isPost()) {
      $this->applyOneClickUnsubscribeStrategy($data);
      exit;
    }

    if ($enableUnsubscribeConfirmation) {
      $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM_UNSUBSCRIBE, $data);
    } else {
      $this->unsubscribe($data);
    }
  }

  public function manage($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_MANAGE, $data);
  }

  public function unsubscribe($data) {
    if ($this->request->isPost()) {
      $this->applyOneClickUnsubscribeStrategy($data);
      exit;
    } else {
      $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
      $subscription->unsubscribe(StatisticsUnsubscribeEntity::METHOD_LINK);
    }
  }

  public function reEngagement($data) {
    $this->initSubscriptionPage(UserSubscription\Pages::ACTION_RE_ENGAGEMENT, $data);
  }

  private function initSubscriptionPage($action, $data) {
    return $this->subscriptionPages->init($action, $data, true, true);
  }

  private function applyOneClickUnsubscribeStrategy($data): void {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE, $data);
    $subscription->unsubscribe(StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
  }
}
