<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Router\Endpoints\Subscription as SubscriptionEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\MailPoetPageResolver;
use MailPoet\Settings\Pages as SettingsPages;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionUrlFactory {

  /** @var SubscriptionUrlFactory */
  private static $instance;

  /** @var WPFunctions */
  private $wp;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var MailPoetPageResolver */
  private $pageResolver;

  public function __construct(
    WPFunctions $wp,
    LinkTokens $linkTokens,
    MailPoetPageResolver $pageResolver
  ) {
    $this->wp = $wp;
    $this->linkTokens = $linkTokens;
    $this->pageResolver = $pageResolver;
  }

  public function getConfirmationUrl(?SubscriberEntity $subscriber = null, ?int $confirmationPageId = null) {
    $post = $confirmationPageId ? $this->pageResolver->getPublishedPage($confirmationPageId) : null;
    $post = $post ?? $this->getPage('subscription.pages.confirmation');
    return $this->getSubscriptionUrl($post, 'confirm', $subscriber);
  }

  public function getConfirmUnsubscribeUrl(?SubscriberEntity $subscriber = null, ?int $queueId = null) {
    $post = $this->getPage('subscription.pages.confirm_unsubscribe');
    $data = $queueId && $subscriber ? ['queueId' => $queueId] : null;
    return $this->getSubscriptionUrl($post, 'confirm_unsubscribe', $subscriber, $data);
  }

  public function getManageUrl(?SubscriberEntity $subscriber = null) {
    $post = $this->getPage('subscription.pages.manage');
    return $this->getSubscriptionUrl($post, 'manage', $subscriber);
  }

  public function getUnsubscribeUrl(?SubscriberEntity $subscriber = null, ?int $queueId = null) {
    $post = $this->getPage('subscription.pages.unsubscribe');
    $data = $queueId && $subscriber ? ['queueId' => $queueId] : null;
    return $this->getSubscriptionUrl($post, 'unsubscribe', $subscriber, $data);
  }

  public function getUnsubscribeReasonUrl(?SubscriberEntity $subscriber = null, ?int $queueId = null) {
    $post = $this->getPage('subscription.pages.unsubscribe');
    $data = $queueId && $subscriber ? ['queueId' => $queueId] : null;
    return $this->getSubscriptionUrl($post, 'unsubscribe_reason', $subscriber, $data);
  }

  public function getTrackingOptOutUrl(?SubscriberEntity $subscriber = null) {
    $post = $this->getPage('subscription.pages.manage');
    return $this->getSubscriptionUrl($post, 'tracking_opt_out', $subscriber);
  }

  public function getReEngagementUrl(?SubscriberEntity $subscriber = null) {
    $post = $this->getPage('reEngagement.page');
    return $this->getSubscriptionUrl($post, 're_engagement', $subscriber);
  }

  public function getSubscriptionUrl(
    $post = null,
    $action = null,
    ?SubscriberEntity $subscriber = null,
    $data = null
  ) {
    if ($action === null) return;

    $url = $this->pageResolver->getPermalinkOrHome($post);
    if ($subscriber !== null) {
      $subscriberData = [
        'token' => $this->linkTokens->getToken($subscriber),
        'email' => $subscriber->getEmail(),
      ];
      $data = array_merge($data ?? [], $subscriberData);
    } elseif (is_null($data)) {
      $data = [
        'preview' => 1,
      ];
    }

    $params = [
      Router::NAME,
      'endpoint=' . SubscriptionEndpoint::ENDPOINT,
      'action=' . $action,
      'data=' . Router::encodeRequestData($data),
    ];

    // add parameters
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);

    $urlParams = parse_url($url);
    if (!is_array($urlParams) || empty($urlParams['scheme'])) {
      $url = $this->wp->getBloginfo('url') . $url;
    }

    return $url;
  }

  /**
   * @return SubscriptionUrlFactory
   */
  public static function getInstance() {
    if (!self::$instance instanceof SubscriptionUrlFactory) {
      $linkTokens = ContainerWrapper::getInstance()->get(LinkTokens::class);
      $pageResolver = ContainerWrapper::getInstance()->get(MailPoetPageResolver::class);
      self::$instance = new SubscriptionUrlFactory(new WPFunctions, $linkTokens, $pageResolver);
    }
    return self::$instance;
  }

  private function getPage(string $settingKey): ?\WP_Post {
    return $this->pageResolver->getPage($settingKey, SettingsPages::PAGE_SUBSCRIPTIONS);
  }
}
