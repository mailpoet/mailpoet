<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\Statistics\SubscriberStatistics;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscriberStats extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
  ];

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberStatisticsRepository */
  private $subscribersStatisticsRepository;

  /** @var Helper */
  private $wooCommerceHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SubscriberStatisticsRepository $subscribersStatisticsRepository,
    Helper $wooCommerceHelper,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersStatisticsRepository = $subscribersStatisticsRepository;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
  }

  public function get($data) {
    $subscriber = isset($data['subscriber_id'])
      ? $this->subscribersRepository->findOneById((int)$data['subscriber_id'])
      : null;
    if (!$subscriber instanceof SubscriberEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
    $isWooActive = $this->wooCommerceHelper->isWooCommerceActive();
    $isWoocommerceUser = (bool)$subscriber->getIsWoocommerceUser();
    $dateFormat = 'Y-m-d H:i:s';
    $subscribedAt = $subscriber->getLastSubscribedAt() ?: $subscriber->getCreatedAt();
    $response = [
      'email' => $subscriber->getEmail(),
      'engagement_score' => $subscriber->getEngagementScore(),
      'is_woo_active' => $isWooActive,
      'is_woocommerce_user' => $isWoocommerceUser,
      'avatar_url' => $this->wp->getAvatarUrl($subscriber->getEmail(), ['size' => 96]) ?: null,
      'subscribed_at' => $subscribedAt instanceof \DateTimeInterface ? $subscribedAt->format($dateFormat) : null,
      'source_label' => $this->getSourceLabel($subscriber->getSource()),
    ];

    $statsMapper = function(SubscriberStatistics $statistics, string $timeframe) {
      return [
        'timeframe' => $timeframe,
        'total_sent' => $statistics->getTotalSentCount(),
        'open' => $statistics->getOpenCount(),
        'machine_open' => $statistics->getMachineOpenCount(),
        'click' => $statistics->getClickCount(),
        'woocommerce' => $statistics->getWooCommerceRevenue() ? $statistics->getWooCommerceRevenue()->asArray() : null,
      ];
    };

    $lifetimeStats = $this->subscribersStatisticsRepository->getStatistics($subscriber);
    $oneYearStats = $this->subscribersStatisticsRepository->getStatistics($subscriber, Carbon::now()->subYear());
    $thirtyDaysStats = $this->subscribersStatisticsRepository->getStatistics($subscriber, Carbon::now()->subDays(30));

    $response['periodic_stats'] = [
      // translators: table header meaning 30 days
      $statsMapper($thirtyDaysStats, __('30(d)', 'mailpoet')),
      // translators: table header meaning 12 months
      $statsMapper($oneYearStats, __('12(m)', 'mailpoet')),
      $statsMapper($lifetimeStats, __('Lifetime', 'mailpoet')),
    ];

    if ($isWooActive && $isWoocommerceUser) {
      $lifetimeRevenue = $lifetimeStats->getWooCommerceRevenue();
      if ($lifetimeRevenue !== null) {
        $response['woocommerce_overview'] = [
          'orders_count' => $lifetimeRevenue->getOrdersCount(),
          'total_revenue_formatted' => $lifetimeRevenue->getFormattedValue(),
          'average_order_value_formatted' => $lifetimeRevenue->getFormattedAverageValue(),
          'orders_url' => $this->getCustomerOrdersUrl($subscriber),
        ];
      }
    }

    $lastEngagement = $subscriber->getLastEngagementAt();
    if ($lastEngagement instanceof \DateTimeInterface) {
      $response['last_engagement'] = $lastEngagement->format($dateFormat);
    }
    $lastClick = $subscriber->getLastClickAt();
    if ($lastClick instanceof \DateTimeInterface) {
      $response['last_click'] = $lastClick->format($dateFormat);
    }
    $lastOpen = $subscriber->getLastOpenAt();
    if ($lastOpen instanceof \DateTimeInterface) {
      $response['last_open'] = $lastOpen->format($dateFormat);
    }
    $lastPageView = $subscriber->getLastPageViewAt();
    if ($lastPageView instanceof \DateTimeInterface) {
      $response['last_page_view'] = $lastPageView->format($dateFormat);
    }
    $lastPurchase = $subscriber->getLastPurchaseAt();
    if ($lastPurchase instanceof \DateTimeInterface) {
      $response['last_purchase'] = $lastPurchase->format($dateFormat);
    }
    $lastSending = $subscriber->getLastSendingAt();
    if ($lastSending instanceof \DateTimeInterface) {
      $response['last_sending'] = $lastSending->format($dateFormat);
    }
    return $this->successResponse($response);
  }

  private function getSourceLabel(?string $source): ?string {
    switch ($source) {
      case Source::FORM:
        return __('MailPoet subscription form', 'mailpoet');
      case Source::IMPORTED:
        return __('import', 'mailpoet');
      case Source::ADMINISTRATOR:
        return __('admin', 'mailpoet');
      case Source::API:
        return __('API', 'mailpoet');
      case Source::WORDPRESS_USER:
        return __('WordPress user sync', 'mailpoet');
      case Source::WOOCOMMERCE_USER:
        return __('WooCommerce customer sync', 'mailpoet');
      case Source::WOOCOMMERCE_CHECKOUT:
        return __('WooCommerce checkout', 'mailpoet');
      default:
        return null;
    }
  }

  private function getCustomerOrdersUrl(SubscriberEntity $subscriber): string {
    $path = $this->wooCommerceHelper->isWooCommerceCustomOrdersTableEnabled()
      ? 'admin.php?page=wc-orders'
      : 'edit.php?post_type=shop_order';
    $wpUserId = $subscriber->getWpUserId();
    if ($wpUserId) {
      $path .= '&_customer_user=' . (int)$wpUserId;
    } else {
      $path .= '&s=' . rawurlencode($subscriber->getEmail());
    }
    return $this->wp->adminUrl($path);
  }
}
