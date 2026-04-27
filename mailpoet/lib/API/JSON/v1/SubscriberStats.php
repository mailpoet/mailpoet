<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\CustomFieldEntity;
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

    $statsMapper = function(SubscriberStatistics $statistics, string $key, string $timeframe) {
      return [
        'key' => $key,
        'timeframe' => $timeframe,
        'total_sent' => $statistics->getTotalSentCount(),
        'open' => $statistics->getOpenCount(),
        'machine_open' => $statistics->getMachineOpenCount(),
        'click' => $statistics->getClickCount(),
        'woocommerce' => $statistics->getWooCommerceRevenue() ? $statistics->getWooCommerceRevenue()->asArray() : null,
      ];
    };

    $now = Carbon::now();
    $periods = [
      [
        'key' => '7_days',
        'label' => __('7 days', 'mailpoet'),
        'start' => $now->copy()->subDays(7),
      ],
      [
        'key' => '30_days',
        'label' => __('30 days', 'mailpoet'),
        'start' => $now->copy()->subDays(30),
      ],
      [
        'key' => '3_months',
        'label' => __('3 months', 'mailpoet'),
        'start' => $now->copy()->subMonths(3),
      ],
      [
        'key' => '12_months',
        'label' => __('12 months', 'mailpoet'),
        'start' => $now->copy()->subMonths(12),
      ],
      [
        'key' => 'lifetime',
        'label' => __('Lifetime', 'mailpoet'),
        'start' => null,
      ],
    ];

    $lifetimeStats = $this->subscribersStatisticsRepository->getStatistics($subscriber);
    $response['periodic_stats'] = [];
    foreach ($periods as $period) {
      $periodStats = $period['key'] === 'lifetime'
        ? $lifetimeStats
        : $this->subscribersStatisticsRepository->getStatistics($subscriber, $period['start']);
      $response['periodic_stats'][] = $statsMapper($periodStats, $period['key'], $period['label']);
    }

    $response['profile'] = $this->getProfile($subscriber, $isWooActive);

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

  private function getProfile(SubscriberEntity $subscriber, bool $isWooActive): array {
    return [
      'first_name' => $subscriber->getFirstName(),
      'last_name' => $subscriber->getLastName(),
      'email' => $subscriber->getEmail(),
      'shipping_address' => $isWooActive ? $this->getShippingAddress($subscriber) : [],
      'tags' => $this->getTags($subscriber),
      'segments' => $this->getSegments($subscriber),
      'custom_fields' => $this->getCustomFields($subscriber),
    ];
  }

  private function getTags(SubscriberEntity $subscriber): array {
    $result = [];
    foreach ($subscriber->getSubscriberTags() as $subscriberTag) {
      $tag = $subscriberTag->getTag();
      if (!$tag) {
        continue;
      }
      $result[] = [
        'id' => (string)$subscriberTag->getId(),
        'subscriber_id' => (string)$subscriber->getId(),
        'tag_id' => (string)$tag->getId(),
        'name' => $tag->getName(),
      ];
    }
    return $result;
  }

  private function getSegments(SubscriberEntity $subscriber): array {
    $result = [];
    foreach ($subscriber->getSubscriberSegments(SubscriberEntity::STATUS_SUBSCRIBED) as $subscriberSegment) {
      $segment = $subscriberSegment->getSegment();
      if (!$segment) {
        continue;
      }
      $result[] = [
        'id' => (string)$segment->getId(),
        'name' => $segment->getName(),
      ];
    }
    return $result;
  }

  private function getCustomFields(SubscriberEntity $subscriber): array {
    $result = [];
    foreach ($subscriber->getSubscriberCustomFields() as $subscriberCustomField) {
      $customField = $subscriberCustomField->getCustomField();
      if (!$customField instanceof CustomFieldEntity) {
        continue;
      }
      $value = $subscriberCustomField->getValue();
      if ($value === '') {
        continue;
      }
      $result[] = [
        'id' => (string)$customField->getId(),
        'name' => $customField->getName(),
        'value' => $value,
      ];
    }
    return $result;
  }

  private function getShippingAddress(SubscriberEntity $subscriber): array {
    $address = [];
    $wpUserId = $subscriber->getWpUserId();
    if ($wpUserId) {
      $customer = $this->wooCommerceHelper->wcGetCustomer((int)$wpUserId);
      if ($customer) {
        $address = $this->getShippingAddressParts($customer);
      }
    }

    if (!$address) {
      $order = $this->getLatestWooCommerceOrderByEmail($subscriber->getEmail());
      if ($order) {
        $address = $this->getShippingAddressParts($order);
      }
    }

    return $this->formatShippingAddress($address);
  }

  private function getLatestWooCommerceOrderByEmail(string $email) {
    $orders = $this->wooCommerceHelper->wcGetOrders([
      'billing_email' => $email,
      'limit' => 1,
      'orderby' => 'date',
      'order' => 'DESC',
    ]);
    return $orders[0] ?? null;
  }

  private function getShippingAddressParts($source): array {
    $address = [];
    $hasAddress = false;
    foreach (['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'] as $field) {
      $method = 'get_shipping_' . $field;
      $address[$field] = method_exists($source, $method) ? trim((string)$source->$method()) : '';
      if (in_array($field, ['address_1', 'address_2', 'city', 'postcode'], true) && $address[$field] !== '') {
        $hasAddress = true;
      }
    }
    if (!$hasAddress) {
      return [];
    }
    return array_filter($address, function($value) {
      return $value !== '';
    });
  }

  private function formatShippingAddress(array $address): array {
    if (!$address) {
      return [];
    }

    $formatted = '';
    try {
      $formatted = (string)$this->wooCommerceHelper->WC()->countries->get_formatted_address($address);
    } catch (\Throwable $e) {
      $formatted = '';
    }

    if ($formatted !== '') {
      $formatted = str_replace(['<br/>', '<br />', '<br>'], "\n", $formatted);
      return array_values(array_filter(
        array_map('trim', explode("\n", strip_tags($formatted)))
      ));
    }

    $lines = [
      trim(implode(' ', array_filter([
        $address['first_name'] ?? '',
        $address['last_name'] ?? '',
      ]))),
      $address['company'] ?? '',
      $address['address_1'] ?? '',
      $address['address_2'] ?? '',
      trim(implode(' ', array_filter([
        $address['city'] ?? '',
        $address['state'] ?? '',
        $address['postcode'] ?? '',
      ]))),
      $address['country'] ?? '',
    ];
    return array_values(array_filter(array_map('trim', $lines)));
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
